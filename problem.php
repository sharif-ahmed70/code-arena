<?php
// ============================================================
//  CODE ARENA — Problem Solver Page
// ============================================================
require_once 'includes/session.php';
require_once 'config/db.php';
require_once 'includes/contest.php';

$slug            = trim($_GET['slug'] ?? '');
$practiceContest = (int) ($_GET['practice_contest'] ?? 0); // set when arriving from practice mode
$contestId       = (int) ($_GET['contest_id'] ?? 0); // set when arriving from live/regular contest
if (!$slug) { header('Location: /code-arena/problems.php'); exit; }
syncContestStatuses($pdo);

$stmt = $pdo->prepare(
    'SELECT id, title, slug, difficulty, description, examples, constraints,
            tags, roadmap_day, hint_tier1, hint_tier2, hint_tier3,
            total_submissions, total_accepted, time_limit_ms
     FROM problems WHERE slug = ? AND is_public = 1 AND COALESCE(is_deleted, 0) = 0'
);
$stmt->execute([$slug]);
$problem = $stmt->fetch();
if (!$problem) { header('Location: /code-arena/problems.php'); exit; }

$userId = currentUserId();

if ($contestId) {
    if (!$userId) { header('Location: /code-arena/login.php'); exit; }
    $accessStmt = $pdo->prepare(
        'SELECT c.id, c.status
         FROM contests c
         JOIN contest_problems cp ON cp.contest_id = c.id AND cp.problem_id = ?
         JOIN contest_participants part ON part.contest_id = c.id AND part.user_id = ?
         WHERE c.id = ?'
    );
    $accessStmt->execute([$problem['id'], $userId, $contestId]);
    $contestAccess = $accessStmt->fetch();
    if (!$contestAccess || $contestAccess['status'] !== 'active') {
        header('Location: /code-arena/contest.php?id=' . $contestId);
        exit;
    }
}

$hasHint1 = !empty($problem['hint_tier1']);
$hasHint2 = !empty($problem['hint_tier2']);
$hasHint3 = !empty($problem['hint_tier3']);
$diffClass = ['Easy'=>'badge-easy','Medium'=>'badge-medium','Hard'=>'badge-hard'][$problem['difficulty']] ?? '';
$accRate = $problem['total_submissions'] > 0
    ? round($problem['total_accepted'] / $problem['total_submissions'] * 100) : 0;

$isBookmarked = false;
$hasSolved = false;
$editorial = null;
$relatedDiscussions = [];
if ($userId) {
    $bmStmt = $pdo->prepare('SELECT COUNT(*) FROM problem_bookmarks WHERE user_id = ? AND problem_id = ?');
    $bmStmt->execute([$userId, $problem['id']]);
    $isBookmarked = (bool) $bmStmt->fetchColumn();

    $solvedStmt = $pdo->prepare(
        'SELECT COUNT(*) FROM submissions
         WHERE user_id = ? AND problem_id = ? AND status = "Accepted" AND is_practice = 0'
    );
    $solvedStmt->execute([$userId, $problem['id']]);
    $hasSolved = (bool) $solvedStmt->fetchColumn();

    if ($hasSolved) {
        $edStmt = $pdo->prepare('SELECT approach, complexity, reference_solution FROM problem_editorials WHERE problem_id = ?');
        $edStmt->execute([$problem['id']]);
        $editorial = $edStmt->fetch();
    }
}
$discStmt = $pdo->prepare(
    'SELECT p.id, p.title, p.comment_count, p.created_at, u.username AS author
     FROM discuss_posts p
     JOIN users u ON u.id = p.user_id AND COALESCE(u.is_deleted, 0) = 0
     WHERE p.problem_id = ?
     ORDER BY p.comment_count DESC, p.created_at DESC
     LIMIT 5'
);
$discStmt->execute([$problem['id']]);
$relatedDiscussions = $discStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($problem['title']) ?> — Code Arena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css?v=20260615-ui5">
    <style>
        body { overflow: hidden; }
        .solve-layout {
            display: grid;
            grid-template-columns: 420px 1fr;
            height: calc(100vh - 64px);
            margin-top: 64px;
        }
        /* ── Left Panel ── */
        .left-panel {
            overflow-y: auto;
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column;
        }
        .problem-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            background: var(--bg-card);
            position: sticky; top: 0; z-index: 5;
        }
        .problem-header h2 { font-size: 1.1rem; margin-bottom: 6px; }
        .problem-meta { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
        .problem-meta span { font-size:.8rem; color:var(--text-muted); }
        .mode-menu { position:relative; display:inline-flex; align-items:center; margin-left:2px; }
        .mode-trigger {
            border:0; background:transparent; padding:0; color:var(--text-muted);
            font-size:.8rem; cursor:pointer; transition:color .18s ease;
        }
        .mode-trigger:hover, .mode-trigger.open { color:var(--accent); }
        .mode-dropdown {
            position:absolute; top:calc(100% + 8px); left:0; min-width:132px; z-index:12;
            padding:6px; border:1px solid var(--border); border-radius:var(--radius-sm);
            background:var(--bg-card); box-shadow:0 16px 36px rgba(0,0,0,.28);
            opacity:0; transform:translateY(-4px); pointer-events:none;
            transition:opacity .16s ease, transform .16s ease;
        }
        .mode-dropdown.open { opacity:1; transform:translateY(0); pointer-events:auto; }
        .mode-option {
            display:block; width:100%; border:0; background:transparent; text-align:left;
            padding:8px 9px; border-radius:var(--radius-sm); color:var(--text-dim);
            font-size:.8rem; cursor:pointer; transition:background .16s ease, color .16s ease;
        }
        .mode-option:hover, .mode-option.active {
            background:rgba(0,232,122,.08); color:var(--accent);
        }
        .problem-body { padding: 24px; flex: 1; }
        .section-title { font-size:.8rem; font-weight:600; color:var(--text-muted);
                         text-transform:uppercase; letter-spacing:.06em; margin-bottom:10px; margin-top:24px; }
        .section-title:first-child { margin-top:0; }
        .problem-desc  { color:var(--text-dim); font-size:.92rem; line-height:1.8; white-space:pre-wrap; }
        .example-block { background:var(--bg-card2); border:1px solid var(--border);
                         border-radius:var(--radius-sm); padding:14px; margin-bottom:12px; }
        .example-label { font-size:.8rem; color:var(--text-muted); margin-bottom:8px; font-weight:600; }
        .example-io    { font-family:'JetBrains Mono',monospace; font-size:.82rem; color:var(--text-dim); }
        .example-io strong { color:var(--text-dim); font-weight:500; display:block; margin-bottom:3px; }

        /* hints */
        .hint-btn {
            display:flex; align-items:center; gap:8px; padding:10px 14px;
            background:var(--bg-card2); border:1px solid var(--border); border-radius:var(--radius-sm);
            color:var(--text-dim); font-size:.88rem; cursor:pointer; margin-bottom:8px;
            transition:border-color .2s; width:100%;
        }
        .hint-btn:hover { border-color:var(--yellow); color:var(--yellow); }
        .hint-btn.unlocked { border-color:rgba(255,209,102,.3); color:var(--yellow); }
        .hint-content { background:rgba(255,209,102,.05); border:1px solid rgba(255,209,102,.2);
                        border-radius:var(--radius-sm); padding:12px 14px; margin-top:4px;
                        font-size:.88rem; color:var(--text-dim); line-height:1.7;
                        display:none; white-space:pre-wrap; }
        .hint-content.show { display:block; }

        /* ── Right Panel ── */
        .right-panel { display:flex; flex-direction:column; overflow:hidden; }
        .editor-toolbar {
            display:flex; align-items:center; gap:12px; padding:10px 16px;
            border-bottom:1px solid var(--border); background:var(--bg-card);
            flex-shrink:0;
        }
        .lang-select { padding:6px 12px; background:var(--bg); border:1px solid var(--border);
                       border-radius:var(--radius-sm); color:var(--text); font-size:.85rem;
                       font-family:'JetBrains Mono',monospace; cursor:pointer; outline:none; }
        .lang-select:focus { border-color:var(--accent); }
        .editor-actions { margin-left:auto; display:flex; gap:8px; }
        #editor-container { flex:1; overflow:hidden; }

        /* ── Results Panel ── */
        .results-panel {
            height: 220px; min-height: 120px; max-height: 50vh;
            border-top:1px solid var(--border); background:var(--bg-card2);
            overflow-y:auto; resize:vertical; flex-shrink:0;
        }
        .results-header { display:flex; align-items:center; gap:10px; padding:10px 16px;
                          border-bottom:1px solid var(--border); position:sticky; top:0;
                          background:var(--bg-card2); z-index:2; }
        .results-header span { font-size:.85rem; font-weight:600; color:var(--text-dim); }
        .results-body { padding:16px; }

        .verdict-banner { border-radius:var(--radius-sm); padding:12px 16px; margin-bottom:12px; font-weight:600; }
        .verdict-Accepted    { background:rgba(0,255,136,.08);  border:1px solid rgba(0,255,136,.3);  color:var(--accent); }
        .verdict-Wrong       { background:rgba(255,79,79,.08);  border:1px solid rgba(255,79,79,.3);  color:var(--red); }
        .verdict-Runtime     { background:rgba(255,79,79,.08);  border:1px solid rgba(255,79,79,.3);  color:var(--red); }
        .verdict-TLE         { background:rgba(255,209,102,.08);border:1px solid rgba(255,209,102,.3);color:var(--yellow); }
        .verdict-Compilation { background:rgba(255,79,79,.08);  border:1px solid rgba(255,79,79,.3);  color:var(--red); }

        .test-results { display:flex; flex-direction:column; gap:8px; }
        .test-case { background:var(--bg-card); border:1px solid var(--border);
                     border-radius:var(--radius-sm); padding:10px 12px; font-size:.82rem; }
        .test-case.pass { border-color:rgba(0,255,136,.25); }
        .test-case.fail { border-color:rgba(255,79,79,.25); }
        .test-label { font-weight:600; margin-bottom:6px; display:flex; align-items:center; gap:6px; }
        .test-label .icon { font-size:1rem; }
        .test-io { display:grid; grid-template-columns:1fr 1fr 1fr; gap:8px; margin-top:6px; }
        .test-io-cell { background:var(--bg); border-radius:4px; padding:6px 8px; }
        .test-io-cell .lbl { font-size:.72rem; color:var(--text-muted); margin-bottom:3px; }
        .test-io-cell pre { font-family:'JetBrains Mono',monospace; font-size:.78rem;
                            color:var(--text-dim); white-space:pre-wrap; word-break:break-all; }
        .run-output { font-family:'JetBrains Mono',monospace; font-size:.82rem; color:var(--text-dim);
                      white-space:pre-wrap; background:var(--bg); padding:12px; border-radius:var(--radius-sm); }

        /* stdin */
        .stdin-row { display:flex; align-items:center; gap:8px; padding:0 16px 10px; flex-shrink:0; }
        .stdin-input { flex:1; font-family:'JetBrains Mono',monospace; font-size:.82rem;
                       padding:7px 10px; background:var(--bg); border:1px solid var(--border);
                       border-radius:var(--radius-sm); color:var(--text); resize:none; height:36px;
                       transition:border-color .2s; outline:none; }
        .stdin-input:focus { border-color:var(--accent); height:80px; }

        /* rating preview */
        .rating-preview { font-size:.8rem; color:var(--text-muted); display:flex; gap:16px; }
        .rating-preview span { color:var(--text-dim); }
        .rp-val { font-weight:600; }
        .rp-hc  { color:var(--red); }
        .rp-lr  { color:var(--blue); }
        .bookmark-btn {
            padding:5px 10px; border:1px solid var(--border); border-radius:var(--radius-sm);
            background:var(--bg-card2); color:var(--text-dim); font-size:.78rem; cursor:pointer;
        }
        .bookmark-btn.active { border-color:rgba(255,201,77,.45); color:var(--yellow); }
        .editorial-card {
            background:rgba(108,160,255,.06); border:1px solid rgba(108,160,255,.22);
            border-radius:var(--radius-sm); padding:14px; color:var(--text-dim);
            font-size:.9rem; line-height:1.75; white-space:pre-wrap;
        }
        .editorial-card pre {
            margin-top:10px; padding:12px; background:var(--bg); border-radius:var(--radius-sm);
            overflow:auto; white-space:pre;
        }
        .discussion-row {
            display:block; padding:10px 12px; border:1px solid var(--border);
            border-radius:var(--radius-sm); background:var(--bg-card2); margin-bottom:8px;
        }
        .discussion-row:hover { border-color:rgba(108,160,255,.38); }
        .discussion-row strong { display:block; color:var(--text); font-size:.88rem; }
        .discussion-row span { color:var(--text-muted); font-size:.76rem; }
        .discussion-actions { display:flex; gap:8px; flex-wrap:wrap; margin-top:10px; }
    </style>
</head>
<body>
<?php require_once 'includes/navbar.php'; ?>

<div class="solve-layout">

    <!-- ── LEFT: Problem description ────────────────────────── -->
    <div class="left-panel">
        <div class="problem-header">
            <h2><?= htmlspecialchars($problem['title']) ?></h2>
            <div class="problem-meta">
                <span class="badge <?= $diffClass ?>"><?= $problem['difficulty'] ?></span>
                <span><?= $accRate ?>% acceptance</span>
                <span><?= number_format($problem['total_submissions']) ?> submissions</span>
                <?php if (isLoggedIn() && !isAdmin()): ?>
                <button class="bookmark-btn <?= $isBookmarked ? 'active' : '' ?>" id="bookmark-btn" onclick="toggleBookmark()">
                    <?= $isBookmarked ? '★ Saved' : '☆ Save' ?>
                </button>
                <a class="bookmark-btn" href="/code-arena/discuss_create.php?problem_id=<?= (int)$problem['id'] ?>">Ask Help</a>
                <?php endif; ?>
                <?php if ($problem['time_limit_ms']): ?>
                <span>⏱ <?= $problem['time_limit_ms'] ?>ms</span>
                <?php endif; ?>
                <div class="mode-menu" aria-label="Solve mode selector">
                    <button type="button" class="mode-trigger" id="mode-trigger" onclick="toggleModeDropdown(event)">Mode: <span>(optional)</span></button>
                    <div class="mode-dropdown" id="mode-dropdown">
                        <button type="button" class="mode-option" data-solve-mode="hardcore" onclick="selectProblemMode('hardcore')">Hardcore 🔥</button>
                        <button type="button" class="mode-option" data-solve-mode="practice" onclick="selectProblemMode('practice')">Practice 📘</button>
                    </div>
                    <input type="hidden" id="solve-mode-input" value="hardcore">
                </div>
            </div>
        </div>

        <div class="problem-body">
            <div class="section-title">Description</div>
            <div class="problem-desc"><?= htmlspecialchars($problem['description']) ?></div>

            <?php if ($problem['examples']): ?>
            <div class="section-title">Examples</div>
            <?php
            $examples = $problem['examples'];
            // Try JSON first, fallback to plain text
            $exArr = json_decode($examples, true);
            if (is_array($exArr)):
                foreach ($exArr as $idx => $ex):
            ?>
            <div class="example-block">
                <div class="example-label">Example <?= $idx + 1 ?></div>
                <div class="example-io">
                    <?php if (!empty($ex['input'])): ?>
                    <strong>Input:</strong><?= htmlspecialchars($ex['input']) ?>
                    <?php endif; ?>
                    <?php if (!empty($ex['output'])): ?>
                    <strong>Output:</strong><?= htmlspecialchars($ex['output']) ?>
                    <?php endif; ?>
                    <?php if (!empty($ex['explanation'])): ?>
                    <strong>Explanation:</strong><?= htmlspecialchars($ex['explanation']) ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; else: ?>
            <div class="example-block">
                <div class="example-io"><?= htmlspecialchars($examples) ?></div>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <?php if ($problem['constraints']): ?>
            <div class="section-title">Constraints</div>
            <div class="problem-desc"><?= htmlspecialchars($problem['constraints']) ?></div>
            <?php endif; ?>

            <?php if ($editorial): ?>
            <div class="section-title">Editorial</div>
            <div class="editorial-card" id="editorial-card">
<strong>Approach</strong>
<?= htmlspecialchars($editorial['approach']) ?>
<?php if (!empty($editorial['complexity'])): ?>

<strong>Complexity</strong>
<?= htmlspecialchars($editorial['complexity']) ?>
<?php endif; ?>
<?php if (!empty($editorial['reference_solution'])): ?>

<strong>Reference Solution</strong>
<pre><?= htmlspecialchars($editorial['reference_solution']) ?></pre>
<?php endif; ?>
            </div>
            <?php elseif (isLoggedIn() && !isAdmin()): ?>
            <div class="section-title">Editorial</div>
            <div class="editorial-card" id="editorial-card" style="display:none"></div>
            <div id="editorial-locked" style="font-size:.86rem;color:var(--text-muted);line-height:1.7;">
                Solve this problem to unlock the editorial.
            </div>
            <?php endif; ?>

            <?php if (!isAdmin() && ($hasHint1 || $hasHint2 || $hasHint3)): ?>
            <div class="section-title">Hints</div>
            <div id="hints-section">
                <?php if ($hasHint1): ?>
                <button class="hint-btn" id="hint-btn-1" onclick="unlockHint(1)">
                    <span>💡</span> Hint 1 — <em>click to unlock</em>
                </button>
                <div class="hint-content" id="hint-content-1"></div>
                <?php endif; ?>
                <?php if ($hasHint2): ?>
                <button class="hint-btn" id="hint-btn-2" onclick="unlockHint(2)" style="display:none">
                    <span>💡</span> Hint 2 — <em>click to unlock</em>
                </button>
                <div class="hint-content" id="hint-content-2"></div>
                <?php endif; ?>
                <?php if ($hasHint3): ?>
                <button class="hint-btn" id="hint-btn-3" onclick="unlockHint(3)" style="display:none">
                    <span>💡</span> Hint 3 — <em>click to unlock</em>
                </button>
                <div class="hint-content" id="hint-content-3"></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="section-title">Discussions</div>
            <?php if ($relatedDiscussions): ?>
                <?php foreach ($relatedDiscussions as $thread): ?>
                <a class="discussion-row" href="/code-arena/discuss_post.php?id=<?= (int)$thread['id'] ?>">
                    <strong><?= htmlspecialchars($thread['title']) ?></strong>
                    <span><?= htmlspecialchars($thread['author']) ?> · <?= (int)$thread['comment_count'] ?> comments</span>
                </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="color:var(--text-muted);font-size:.86rem;line-height:1.7;">
                    No discussions for this problem yet.
                </div>
            <?php endif; ?>
            <div class="discussion-actions">
                <?php if (isLoggedIn() && !isAdmin()): ?>
                <a class="btn-outline" href="/code-arena/discuss_create.php?problem_id=<?= (int)$problem['id'] ?>">Ask for Help</a>
                <?php endif; ?>
                <a class="btn-outline" href="/code-arena/discuss.php?problem_id=<?= (int)$problem['id'] ?>">View Discussions</a>
            </div>
        </div>
    </div>

    <!-- ── RIGHT: Editor + Results ──────────────────────────── -->
    <?php if (isAdmin()): ?>
    <div class="right-panel" style="display:flex;align-items:center;justify-content:center;padding:40px;">
        <div style="text-align:center;color:var(--text-muted);max-width:360px;">
            <div style="font-size:2rem;margin-bottom:16px;">🔒</div>
            <div style="font-size:1rem;font-weight:600;color:var(--text-dim);margin-bottom:8px;">Admin view — solving disabled</div>
            <div style="font-size:.88rem;line-height:1.7;">Admins can read problem content for moderation but cannot submit solutions or earn ratings.</div>
        </div>
    </div>
    <?php else: ?>
    <div class="right-panel">
        <div class="editor-toolbar">
            <select id="lang-select" class="lang-select" onchange="changeLanguage()">
                <option value="javascript">JavaScript</option>
                <option value="python">Python</option>
                <option value="cpp">C++</option>
                <option value="c">C</option>
                <option value="java">Java</option>
                <option value="go">Go</option>
                <option value="rust">Rust</option>
                <option value="typescript">TypeScript</option>
            </select>
            <div class="rating-preview" id="rating-preview">
                <span>Skill <span class="rp-val rp-lr" id="rp-skill">+0</span></span>
                <span id="rp-mode">Hardcore mode</span>
            </div>
            <div class="editor-actions">
                <button class="btn-outline" id="run-btn" onclick="runCode()">▶ Run</button>
                <?php if (isLoggedIn()): ?>
                <button class="btn-primary" id="submit-btn" onclick="submitCode()">Submit</button>
                <?php else: ?>
                <a href="/code-arena/login.php" class="btn-primary">Login to Submit</a>
                <?php endif; ?>
            </div>
        </div>

        <div id="editor-container"></div>

        <div class="stdin-row">
            <textarea id="stdin" class="stdin-input" placeholder="Custom stdin (for Run)…" rows="1"></textarea>
        </div>

        <div class="results-panel" id="results-panel">
            <div class="results-header">
                <span>Output</span>
                <span id="results-status" style="margin-left:auto; font-size:.8rem; color:var(--text-muted)"></span>
            </div>
            <div class="results-body" id="results-body">
                <p style="color:var(--text-muted);font-size:.88rem">Run or submit your code to see results.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (!isAdmin()): ?>
<!-- Monaco -->
<script src="https://cdn.jsdelivr.net/npm/monaco-editor@0.44.0/min/vs/loader.js"></script>
<?php endif; ?>
<script src="/code-arena/assets/js/main.js?v=2"></script>
<?php if (isAdmin()): ?>
<script>/* Admin view — editor and solving disabled */</script>
<?php else: ?>
<script>
const PROBLEM_ID       = <?= (int) $problem['id'] ?>;
const DIFFICULTY       = '<?= $problem['difficulty'] ?>';
const BASE_DELTAS      = {
    hardcore: { Easy:18, Medium:32, Hard:50 },
    practice: { Easy:9, Medium:16, Hard:28 },
};
const CONTEST_ID       = <?= $contestId ?: 'null' ?>;
const PRACTICE_CONTEST = <?= $practiceContest ?: 'null' ?>; // non-null when in practice mode

let editor;
let hintsUsed = 0;
const hintContent = {};

function getProblemMode() {
    const storedMode = localStorage.getItem('problem_mode');
    if (storedMode === null) {
        localStorage.setItem('problem_mode', 'hardcore');
        return 'hardcore';
    }
    return storedMode === 'practice' ? 'practice' : 'hardcore';
}

function renderProblemMode() {
    const currentMode = getProblemMode();
    const input = document.getElementById('solve-mode-input');
    if (input) input.value = currentMode;
    document.querySelectorAll('[data-solve-mode]').forEach(option => {
        option.classList.toggle('active', option.dataset.solveMode === currentMode);
    });
}

function closeModeDropdown() {
    document.getElementById('mode-trigger')?.classList.remove('open');
    document.getElementById('mode-dropdown')?.classList.remove('open');
}

function toggleModeDropdown(event) {
    event.stopPropagation();
    const trigger = document.getElementById('mode-trigger');
    const dropdown = document.getElementById('mode-dropdown');
    const isOpen = dropdown?.classList.contains('open');
    trigger?.classList.toggle('open', !isOpen);
    dropdown?.classList.toggle('open', !isOpen);
}

function selectProblemMode(mode) {
    const nextMode = mode === 'practice' ? 'practice' : 'hardcore';
    localStorage.setItem('problem_mode', nextMode);
    renderProblemMode();
    updateRatingPreview();
    closeModeDropdown();
}

renderProblemMode();
document.addEventListener('click', closeModeDropdown);

async function toggleBookmark() {
    const btn = document.getElementById('bookmark-btn');
    if (!btn) return;
    btn.disabled = true;

    const { ok, data } = await api('/code-arena/api/problems/bookmark.php', {
        method: 'POST',
        body: JSON.stringify({ problem_id: PROBLEM_ID }),
    });

    btn.disabled = false;
    if (!ok || !data.success) {
        toast(data.message || 'Bookmark update failed', 'error');
        return;
    }

    const saved = data.data.bookmarked;
    btn.classList.toggle('active', saved);
    btn.textContent = saved ? '★ Saved' : '☆ Save';
    toast(saved ? 'Problem saved' : 'Bookmark removed', saved ? 'success' : 'info');
}

// Monaco boilerplate starters per language
const STARTERS = {
    javascript: '// Write your solution here\n\nfunction solution(input) {\n    \n}\n\n// Read stdin\nconst lines = require("fs").readFileSync("/dev/stdin","utf8").trim().split("\\n");\nconsole.log(solution(lines));\n',
    python:     '# Write your solution here\nimport sys\n\ndef solution(lines):\n    pass\n\nlines = sys.stdin.read().strip().split("\\n")\nprint(solution(lines))\n',
    cpp:        '#include <bits/stdc++.h>\nusing namespace std;\n\nint main() {\n    // your code\n    return 0;\n}\n',
    c:          '#include <stdio.h>\n\nint main() {\n    // your code\n    return 0;\n}\n',
    java:       'import java.util.*;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        // your code\n    }\n}\n',
    go:         'package main\n\nimport "fmt"\n\nfunc main() {\n    // your code\n    fmt.Println("")\n}\n',
    rust:       'use std::io::{self, BufRead};\n\nfn main() {\n    let stdin = io::stdin();\n    for line in stdin.lock().lines() {\n        // process line.unwrap()\n    }\n}\n',
    typescript: '// Write your solution here\nprocess.stdin.resume();\nprocess.stdin.setEncoding("utf8");\nlet input = "";\nprocess.stdin.on("data", d => input += d);\nprocess.stdin.on("end", () => {\n    const lines = input.trim().split("\\n");\n    console.log(lines[0]);\n});\n',
};

const MONACO_LANG = {
    javascript:'javascript', python:'python', cpp:'cpp', c:'c', java:'java',
    go:'go', rust:'rust', typescript:'typescript',
};

require.config({ paths: { vs: 'https://cdn.jsdelivr.net/npm/monaco-editor@0.44.0/min/vs' } });
require(['vs/editor/editor.main'], function () {
    monaco.editor.defineTheme('code-arena', {
        base: 'vs-dark',
        inherit: true,
        rules: [],
        colors: {
            'editor.background':          '#0a0a0f',
            'editor.lineHighlightBackground': '#111118',
            'editorLineNumber.foreground':'#3a3a5a',
            'editorIndentGuide.background':'#1e1e2e',
        },
    });

    editor = monaco.editor.create(document.getElementById('editor-container'), {
        value: STARTERS.javascript,
        language: 'javascript',
        theme: 'code-arena',
        fontSize: 14,
        fontFamily: "'JetBrains Mono', monospace",
        minimap: { enabled: false },
        scrollBeyondLastLine: false,
        lineNumbers: 'on',
        automaticLayout: true,
        tabSize: 4,
        wordWrap: 'off',
        padding: { top: 12 },
    });
});

function changeLanguage() {
    const lang = document.getElementById('lang-select').value;
    if (!editor) return;
    monaco.editor.setModelLanguage(editor.getModel(), MONACO_LANG[lang] || 'plaintext');
    editor.setValue(STARTERS[lang] || '// Write your solution here\n');
    updateRatingPreview();
}

function updateRatingPreview() {
    const mode = getProblemMode();
    const base = BASE_DELTAS[mode]?.[DIFFICULTY] || 0;
    const hintPenalty = mode === 'practice' ? 3 : 10;
    const delta = Math.max(1, base - (hintsUsed * hintPenalty));
    document.getElementById('rp-skill').textContent = `+${delta}`;
    document.getElementById('rp-mode').textContent = mode === 'hardcore'
        ? 'Hardcore mode'
        : 'Practice mode';
}

// ── Hints ────────────────────────────────────────────────────
async function unlockHint(tier) {
    <?php if (!isLoggedIn()): ?>
    window.location.href = '/code-arena/login.php';
    return;
    <?php endif; ?>

    if (hintContent[tier]) {
        toggleHint(tier);
        return;
    }

    const btn = document.getElementById(`hint-btn-${tier}`);
    btn.textContent = '⏳ Unlocking…';
    btn.disabled = true;

    const { ok, data } = await api('/code-arena/api/problems/hint.php', {
        method: 'POST',
        body: JSON.stringify({ problem_id: PROBLEM_ID, tier }),
    });

    btn.disabled = false;

    if (!ok || !data.success) {
        toast(data.message || 'Failed to unlock hint', 'error');
        btn.innerHTML = `<span>💡</span> Hint ${tier} — <em>click to unlock</em>`;
        return;
    }

    hintContent[tier] = data.data.hint;
    hintsUsed = data.data.hints_used;
    updateRatingPreview();

    const contentEl = document.getElementById(`hint-content-${tier}`);
    contentEl.textContent = data.data.hint;
    contentEl.classList.add('show');

    btn.classList.add('unlocked');
    btn.innerHTML = `<span>💡</span> Hint ${tier} — <em>click to hide</em>`;

    // Reveal next hint button
    const next = document.getElementById(`hint-btn-${tier + 1}`);
    if (next) next.style.display = 'flex';

    toast(`Hint ${tier} unlocked. Rating impact updated.`, 'warn');
}

function toggleHint(tier) {
    const contentEl = document.getElementById(`hint-content-${tier}`);
    const btn = document.getElementById(`hint-btn-${tier}`);
    const visible = contentEl.classList.toggle('show');
    btn.innerHTML = `<span>💡</span> Hint ${tier} — <em>${visible ? 'click to hide' : 'click to show'}</em>`;
}

// ── Run ───────────────────────────────────────────────────────
async function runCode() {
    if (!editor) return;
    const code     = editor.getValue();
    const language = document.getElementById('lang-select').value;
    const stdin    = document.getElementById('stdin').value;
    const btn      = document.getElementById('run-btn');

    btn.disabled = true;
    btn.textContent = '⏳ Running…';
    setResultStatus('Running…', 'info');

    const { ok, data } = await api('/code-arena/api/submissions/run.php', {
        method: 'POST',
        body: JSON.stringify({ code, language, stdin }),
    });

    btn.disabled = false;
    btn.textContent = '▶ Run';

    if (!ok || !data.success) {
        showRunError(data.message || data.data?.error || 'Run failed');
        return;
    }

    const r = data.data;
    const body = document.getElementById('results-body');

    if (r.verdict === 'OK') {
        setResultStatus('Run complete', 'success');
        body.innerHTML = `<div class="run-output">${escHtml(r.output || '(no output)')}</div>`;
    } else {
        setResultStatus(r.verdict, 'error');
        body.innerHTML = `
            <div class="verdict-banner verdict-Runtime">${r.verdict}</div>
            <div class="run-output">${escHtml(r.stderr || r.error || 'Unknown error')}</div>`;
    }
}

// ── Submit ────────────────────────────────────────────────────
async function submitCode() {
    if (!editor) return;
    const code     = editor.getValue();
    const language = document.getElementById('lang-select').value;
    const btn      = document.getElementById('submit-btn');

    if (!code.trim()) { toast('Write some code first', 'warn'); return; }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Judging…';
    setResultStatus('Judging…', 'info');

    const submitPayload = {
        problem_id: PROBLEM_ID,
        code,
        language,
        hints_used: hintsUsed,
        solve_mode: getProblemMode(),
    };
    if (PRACTICE_CONTEST) {
        submitPayload.contest_id  = PRACTICE_CONTEST;
        submitPayload.is_practice = true;
    } else if (CONTEST_ID) {
        submitPayload.contest_id = CONTEST_ID;
    }
    const { ok, data } = await api('/code-arena/api/submissions/submit.php', {
        method: 'POST',
        body: JSON.stringify(submitPayload),
    });

    btn.disabled = false;
    btn.textContent = 'Submit';

    if (!ok || !data.success) {
        toast(data.message || 'Submit failed', 'error');
        setResultStatus('Error', 'error');
        return;
    }

    const r = data.data;
    renderVerdict(r);
}

function renderVerdict(r) {
    const isAC = r.verdict === 'Accepted';
    const cls  = {
        'Accepted':              'verdict-Accepted',
        'Wrong Answer':          'verdict-Wrong',
        'Runtime Error':         'verdict-Runtime',
        'Time Limit Exceeded':   'verdict-TLE',
        'Compilation Error':     'verdict-Compilation',
    }[r.verdict] || 'verdict-Wrong';

    setResultStatus(`${r.passed}/${r.total} passed`, isAC ? 'success' : 'error');

    let html = `<div class="verdict-banner ${cls}">
        ${r.verdict} — ${r.passed}/${r.total} test cases passed
        ${r.runtime_ms ? ` — ${r.runtime_ms}ms` : ''}
    </div>`;

    if (isAC && Number(r.rating_delta?.skill || 0) > 0) {
        const modeLabel = r.rating_delta.mode === 'learning' ? 'Learning' : 'Hardcore';
        html += `<div style="padding:10px 0;font-size:.85rem;color:var(--text-muted)">
            Skill Rating: <span style="color:var(--accent)">+${r.rating_delta.skill}</span>
            <span style="margin-left:8px">${modeLabel}</span>
        </div>`;
        toast(`Accepted! Skill +${r.rating_delta.skill}`, 'success', 5000);
    } else if (isAC) {
        toast('Accepted! (Already solved — no rating change)', 'success');
    } else {
        toast(r.verdict, 'error');
    }

    if (isAC) {
        const locked = document.getElementById('editorial-locked');
        const card = document.getElementById('editorial-card');
        if (locked && card && !card.textContent.trim()) {
            locked.style.display = 'none';
            card.style.display = 'block';
            card.innerHTML = '<strong>Editorial unlocked.</strong> Refresh the page to read the full explanation.';
        }
    }

    if (r.error && !isAC) {
        html += `<div class="run-output" style="margin-bottom:12px">${escHtml(r.error)}</div>`;
    }

    if (r.results && r.results.length > 0) {
        html += '<div class="test-results">';
        r.results.forEach(tc => {
            html += `
            <div class="test-case ${tc.passed ? 'pass' : 'fail'}">
                <div class="test-label">
                    <span class="icon">${tc.passed ? '✓' : '✗'}</span>
                    Test ${tc.test}
                    ${tc.error ? `<span style="color:var(--red);font-size:.78rem">${escHtml(tc.error)}</span>` : ''}
                </div>
                ${!tc.passed ? `
                <div class="test-io">
                    <div class="test-io-cell"><div class="lbl">Input</div><pre>${escHtml(tc.input ?? '')}</pre></div>
                    <div class="test-io-cell"><div class="lbl">Expected</div><pre>${escHtml(tc.expected ?? '')}</pre></div>
                    <div class="test-io-cell"><div class="lbl">Got</div><pre>${escHtml(tc.got ?? '')}</pre></div>
                </div>` : ''}
            </div>`;
        });
        html += '</div>';
    }

    document.getElementById('results-body').innerHTML = html;
}

function showRunError(msg) {
    setResultStatus('Error', 'error');
    document.getElementById('results-body').innerHTML =
        `<div class="run-output" style="color:var(--red)">${escHtml(msg)}</div>`;
}

function setResultStatus(msg, type) {
    const el = document.getElementById('results-status');
    const colors = { success:'var(--accent)', error:'var(--red)', info:'var(--text-muted)' };
    el.textContent = msg;
    el.style.color = colors[type] || colors.info;
}

function escHtml(s) {
    return String(s ?? '')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

updateRatingPreview();
</script>
<?php endif; ?>
</body>
</html>
