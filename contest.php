<?php
// ============================================================
//  CODE ARENA — Contest Detail Page
// ============================================================
require_once 'includes/session.php';
require_once 'config/db.php';
require_once 'includes/contest.php';

$id = (int) ($_GET['id'] ?? 0);
if (!$id) { header('Location: /code-arena/contests.php'); exit; }
syncContestStatuses($pdo);

$stmt = $pdo->prepare(
    'SELECT c.*, u.username AS author
     FROM contests c JOIN users u ON u.id = c.created_by
     WHERE c.id = ?'
);
$stmt->execute([$id]);
$contest = $stmt->fetch();
if (!$contest) { header('Location: /code-arena/contests.php'); exit; }

$canPreviewOrgContest = false;
if (isLoggedIn() && currentRole() === 'org_admin' && !empty($contest['org_id'])) {
    $orgPreviewStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE id = ? AND org_id = ? AND COALESCE(is_deleted, 0) = 0');
    $orgPreviewStmt->execute([currentUserId(), (int)$contest['org_id']]);
    $canPreviewOrgContest = (bool)$orgPreviewStmt->fetchColumn();
}
$contestVisible = (int)($contest['is_published'] ?? 1) === 1
    && in_array($contest['visibility'] ?? 'public', ['public', 'org'], true)
    && !in_array($contest['org_status'] ?? 'scheduled', ['draft', 'archived'], true);
if (!$contestVisible && !isAdmin() && !$canPreviewOrgContest) {
    header('Location: /code-arena/contests.php');
    exit;
}

// Problems
$pStmt = $pdo->prepare(
    'SELECT p.id, COALESCE(op.title, p.title) AS title, p.slug,
            COALESCE(op.difficulty, p.difficulty) AS difficulty, cp.points, cp.order_index
     FROM contest_problems cp
     JOIN problems p ON p.id = cp.problem_id
     LEFT JOIN org_problems op ON op.id = cp.org_problem_id AND op.org_id = ?
     WHERE cp.contest_id = ?
     ORDER BY cp.order_index'
);
$pStmt->execute([(int)($contest['org_id'] ?? 0), $id]);
$problems = $pStmt->fetchAll();

// Participant count
$pcStmt = $pdo->prepare('SELECT COUNT(*) FROM contest_participants WHERE contest_id = ?');
$pcStmt->execute([$id]);
$participantCount = (int) $pcStmt->fetchColumn();

// Is current user registered?
$registered = false;
if (isLoggedIn()) {
    $regStmt = $pdo->prepare('SELECT COUNT(*) FROM contest_participants WHERE contest_id = ? AND user_id = ?');
    $regStmt->execute([$id, currentUserId()]);
    $registered = (bool) $regStmt->fetchColumn();
}

// Leaderboard (top 20 for ended contests)
$leaderboard = [];
if ($contest['status'] === 'ended' || $contest['status'] === 'active') {
    $lbStmt = $pdo->prepare(
        "SELECT u.username, cp.score, cp.penalty_minutes
         FROM contest_participants cp
         JOIN users u ON u.id = cp.user_id
         WHERE cp.contest_id = ? AND u.role != 'admin'
         ORDER BY cp.score DESC, cp.penalty_minutes ASC
         LIMIT 20"
    );
    $lbStmt->execute([$id]);
    $leaderboard = $lbStmt->fetchAll();
}

$start    = new DateTime($contest['start_time']);
$end      = new DateTime($contest['end_time']);
$durMins  = (int) ($start->diff($end)->days * 1440 + $start->diff($end)->h * 60 + $start->diff($end)->i);
$durLabel = $durMins >= 60 ? round($durMins / 60, 1) . 'h' : $durMins . 'min';

$statusLabels = ['upcoming' => 'Upcoming', 'active' => 'Live', 'ended' => 'Ended'];
$statusLabel  = $statusLabels[$contest['status']] ?? $contest['status'];
$diffClass    = ['Easy' => 'badge-easy', 'Medium' => 'badge-medium', 'Hard' => 'badge-hard'];

// Practice mode: only for ended contests, logged-in users
$isPractice = isset($_GET['practice']) && $contest['status'] === 'ended' && isLoggedIn();
// If ?practice=1 on a non-ended contest, strip the flag silently (guard)
if (isset($_GET['practice']) && $contest['status'] !== 'ended') {
    header("Location: /code-arena/contest.php?id=$id");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($contest['title']) ?> — Code Arena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css?v=20260615-ui2">
    <style>
        .contest-hero {
            background: var(--bg-card); border-bottom: 1px solid var(--border);
            padding: 32px 0;
            margin-top: 64px; /* clear the fixed navbar */
        }
        .contest-hero-inner { max-width: 900px; margin: 0 auto; padding: 0 24px; }
        .contest-status-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 12px; border-radius: 100px;
            font-size: .75rem; font-weight: 600; margin-bottom: 14px;
        }
        .csb-active   { background: rgba(0,232,122,.1);   color: var(--accent); border: 1px solid rgba(0,232,122,.28); }
        .csb-upcoming { background: rgba(108,160,255,.1); color: var(--blue);  border: 1px solid rgba(108,160,255,.28); }
        .csb-ended    { background: rgba(143,143,170,.1); color: var(--text-muted); border: 1px solid var(--border); }
        .contest-hero h1 {
            font-size: clamp(1.6rem, 3vw, 2.2rem); margin-bottom: 16px;
            line-height: 1.25; overflow: visible;
        }
        .contest-meta-row {
            display: flex; gap: 20px; flex-wrap: wrap;
            font-size: .875rem; color: var(--text-muted); margin-bottom: 24px;
        }
        .contest-meta-row span { display: flex; align-items: center; gap: 5px; }

        .two-col { display: grid; grid-template-columns: 1fr 320px; gap: 28px; align-items: start; }
        @media (max-width: 780px) { .two-col { grid-template-columns: 1fr; } }

        .section-card {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius); overflow: hidden; margin-bottom: 20px;
        }
        .section-card-header {
            padding: 14px 20px; border-bottom: 1px solid var(--border);
            font-size: .8rem; font-weight: 600; color: var(--text-muted);
            text-transform: uppercase; letter-spacing: .06em;
        }
        .section-card-body { padding: 20px; }

        .prob-row {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 0; border-bottom: 1px solid var(--border);
            font-size: .9rem;
        }
        .prob-row:last-child { border-bottom: none; }
        .prob-row a { color: var(--text); font-weight: 500; flex: 1; }
        .prob-row a:hover { color: var(--accent); }
        .prob-points { font-family: 'JetBrains Mono', monospace; font-size: .8rem; color: var(--text-muted); }

        .lb-row {
            display: grid; grid-template-columns: 32px 1fr 80px 80px;
            gap: 8px; align-items: center; padding: 10px 0;
            border-bottom: 1px solid var(--border); font-size: .88rem;
        }
        .lb-row:last-child { border-bottom: none; }
        .lb-rank { font-family: 'JetBrains Mono', monospace; font-size: .78rem; color: var(--text-muted); text-align: center; }
        .lb-rank.gold   { color: #ffd700; }
        .lb-rank.silver { color: #c0c0c0; }
        .lb-rank.bronze { color: #cd7f32; }
        .lb-score { text-align: right; color: var(--accent); font-weight: 600; }
        .lb-penalty { text-align: right; color: var(--text-muted); font-size: .8rem; }
        .scoreboard-wrap { overflow-x: auto; }
        .scoreboard-table { width: 100%; border-collapse: collapse; min-width: 620px; }
        .scoreboard-table th,
        .scoreboard-table td {
            padding: 10px 12px; border-bottom: 1px solid var(--border);
            font-size: .85rem; text-align: left; white-space: nowrap;
        }
        .scoreboard-table th {
            color: var(--text-muted); font-size: .72rem; text-transform: uppercase;
            letter-spacing: .06em; font-weight: 700; background: var(--bg-card2);
        }
        .scoreboard-table .num { text-align: right; font-family: 'JetBrains Mono', monospace; }
        .scoreboard-table .rank { color: var(--yellow); font-weight: 700; }
        .score-cell { text-align: center; font-family: 'JetBrains Mono', monospace; }
        .score-cell.accepted { color: var(--accent); }
        .score-cell.attempted { color: var(--yellow); }
        .score-cell.none { color: var(--text-muted); opacity: .65; }
        .scoreboard-note { color: var(--text-muted); font-size: .8rem; line-height: 1.6; margin-top: 10px; }

        .info-row { display: flex; justify-content: space-between; padding: 10px 0;
                    border-bottom: 1px solid var(--border); font-size: .875rem; }
        .info-row:last-child { border-bottom: none; }
        .info-row span:first-child { color: var(--text-muted); }
        .info-row span:last-child  { font-weight: 500; }

        .register-btn { width: 100%; justify-content: center; }
        .already-registered {
            text-align: center; padding: 12px;
            background: rgba(0,232,122,.08); border: 1px solid rgba(0,232,122,.22);
            border-radius: var(--radius-sm); font-size: .88rem; color: var(--accent);
        }

        /* ── Practice mode styles ── */
        .practice-banner {
            margin-top: 64px; background: var(--bg-card);
            border-bottom: 2px solid rgba(108,160,255,.35);
            padding: 18px 0;
        }
        .practice-banner-inner {
            max-width: 900px; margin: 0 auto; padding: 0 24px;
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 14px;
        }
        .practice-label {
            display: inline-flex; align-items: center; gap: 7px;
            font-size: .78rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .06em; color: var(--blue);
            background: rgba(108,160,255,.1); border: 1px solid rgba(108,160,255,.28);
            padding: 4px 12px; border-radius: 100px;
        }
        .practice-timer {
            font-family: 'JetBrains Mono', monospace; font-size: 1.6rem;
            font-weight: 700; letter-spacing: .04em; color: var(--text);
            min-width: 90px; text-align: center;
        }
        .practice-timer.warning { color: var(--yellow); }
        .practice-timer.expired { color: var(--red); }
        .practice-info { font-size: .82rem; color: var(--text-muted); }
        .practice-no-lb {
            text-align: center; padding: 28px 20px; color: var(--text-muted);
            font-size: .88rem;
        }
        .expired-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,.75);
            z-index: 500; display: flex; align-items: center; justify-content: center;
        }
        .expired-card {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 40px; text-align: center;
            max-width: 400px;
        }
        .expired-card h2 { margin-bottom: 10px; }
        .expired-card p { color: var(--text-muted); font-size: .9rem; margin-bottom: 20px; }
    </style>
</head>
<body>
<?php require_once 'includes/navbar.php'; ?>

<?php if ($isPractice): ?>
<!-- ══════════════ PRACTICE MODE BANNER ══════════════ -->
<div class="practice-banner">
    <div class="practice-banner-inner">
        <div>
            <span class="practice-label">⚡ Practice Mode</span>
            <div style="margin-top:6px;font-size:1rem;font-weight:700;color:var(--text)">
                <?= htmlspecialchars($contest['title']) ?>
            </div>
            <div class="practice-info">Results are not ranked · Rating is not affected</div>
        </div>
        <div style="text-align:center">
            <div class="practice-timer" id="practice-timer">—:——:——</div>
            <div style="font-size:.72rem;color:var(--text-muted);margin-top:2px">remaining</div>
        </div>
        <a href="/code-arena/contest.php?id=<?= $id ?>" class="btn-outline" style="font-size:.82rem">
            ← Back to Contest
        </a>
    </div>
</div>

<?php else: ?>
<!-- ══════════════ NORMAL CONTEST HERO ══════════════ -->
<div class="contest-hero">
    <div class="contest-hero-inner">
        <div class="contest-status-badge csb-<?= $contest['status'] ?>">
            <?php if ($contest['status'] === 'active'): ?>
            <span style="width:7px;height:7px;border-radius:50%;background:var(--accent)"></span>
            <?php endif; ?>
            <?= $statusLabel ?>
        </div>

        <h1><?= htmlspecialchars($contest['title']) ?></h1>

        <div class="contest-meta-row">
            <span>by <?= htmlspecialchars($contest['author']) ?></span>
            <span>📅 <?= $start->format('M j, Y · H:i') ?></span>
            <span>⏱ <?= $durLabel ?> duration</span>
            <span>👥 <?= $participantCount ?> participants</span>
            <?php if ($contest['is_rated']): ?>
            <span style="color:var(--accent)">★ Rated</span>
            <?php endif; ?>
        </div>

        <?php if ($contest['description']): ?>
        <p style="color:var(--text-muted);font-size:.93rem;line-height:1.7;max-width:640px">
            <?= nl2br(htmlspecialchars($contest['description'])) ?>
        </p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div class="page" style="padding-top:36px">
<div class="container" style="max-width:900px">
<div class="two-col">

    <!-- Left: problems + leaderboard -->
    <div>
        <!-- Problems -->
        <div class="section-card">
            <div class="section-card-header">Problems (<?= count($problems) ?>)</div>
            <div class="section-card-body" style="padding:0 20px">
                <?php if ($problems): ?>
                    <?php foreach ($problems as $p): ?>
                    <div class="prob-row">
                        <?php if ($isPractice): ?>
                            <!-- In practice mode: link carries practice_contest param -->
                            <a href="/code-arena/problem.php?slug=<?= htmlspecialchars($p['slug']) ?>&practice_contest=<?= $id ?>">
                                <?= htmlspecialchars($p['title']) ?>
                            </a>
                        <?php elseif ($contest['status'] === 'active' && $registered): ?>
                            <a href="/code-arena/problem.php?slug=<?= htmlspecialchars($p['slug']) ?>&contest_id=<?= $id ?>">
                                <?= htmlspecialchars($p['title']) ?>
                            </a>
                        <?php else: ?>
                            <span style="flex:1;color:var(--text-muted)">
                                <?= htmlspecialchars($p['title']) ?>
                            </span>
                        <?php endif; ?>
                        <span class="badge <?= $diffClass[$p['difficulty']] ?? '' ?>"><?= $p['difficulty'] ?></span>
                        <span class="prob-points"><?= $p['points'] ?>pts</span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="padding:20px 0;color:var(--text-muted);font-size:.88rem">No problems added yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$isPractice && $contest['status'] !== 'upcoming'): ?>
        <div class="section-card">
            <div class="section-card-header">Live Scoreboard</div>
            <div class="section-card-body">
                <div class="scoreboard-wrap" id="scoreboard-wrap">
                    <p style="color:var(--text-muted);font-size:.88rem">Loading scoreboard...</p>
                </div>
                <p class="scoreboard-note">
                    Score uses first accepted submission per problem. Penalty is solve time plus 20 minutes for each failed attempt before accept.
                </p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Leaderboard (hidden in practice mode) -->
        <?php if ($isPractice): ?>
        <div class="section-card">
            <div class="section-card-header">Leaderboard</div>
            <div class="section-card-body">
                <p class="practice-no-lb">⚡ Practice mode — results are not ranked.</p>
            </div>
        </div>
        <?php elseif (false && $leaderboard): ?>
        <div class="section-card">
            <div class="section-card-header">Leaderboard</div>
            <div class="section-card-body" style="padding:0 20px">
                <div class="lb-row" style="font-size:.75rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em">
                    <div></div><div>User</div><div style="text-align:right">Score</div><div style="text-align:right">Penalty</div>
                </div>
                <?php foreach ($leaderboard as $i => $row): ?>
                <?php $rankCls = match($i) { 0 => 'gold', 1 => 'silver', 2 => 'bronze', default => '' }; ?>
                <div class="lb-row">
                    <div class="lb-rank <?= $rankCls ?>">#<?= $i + 1 ?></div>
                    <div><a href="/code-arena/profile.php?user=<?= htmlspecialchars($row['username']) ?>"
                            style="color:var(--text)"><?= htmlspecialchars($row['username']) ?></a></div>
                    <div class="lb-score"><?= $row['score'] ?></div>
                    <div class="lb-penalty"><?= $row['penalty_minutes'] ?>m</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php elseif (false && $contest['status'] === 'ended'): ?>
        <div class="section-card">
            <div class="section-card-header">Leaderboard</div>
            <div class="section-card-body">
                <p style="color:var(--text-muted);font-size:.88rem">No participants.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right: info + register/practice -->
    <div>
        <div class="section-card" style="margin-bottom:20px">
            <div class="section-card-body">
                <?php if ($isPractice): ?>
                    <div class="already-registered" style="background:rgba(108,160,255,.08);border-color:rgba(108,160,255,.22);color:var(--blue)">
                        ⚡ Practice session active
                    </div>
                    <p style="font-size:.8rem;color:var(--text-muted);margin-top:10px;text-align:center">
                        Submissions won't affect your rating.
                    </p>
                <?php elseif (!isLoggedIn()): ?>
                    <a href="/code-arena/login.php" class="btn-primary register-btn">Login to Register</a>
                <?php elseif ($contest['status'] === 'ended'): ?>
                    <p style="text-align:center;color:var(--text-muted);font-size:.88rem;margin-bottom:14px">This contest has ended.</p>
                    <a href="/code-arena/contest.php?id=<?= $id ?>&practice=1"
                       class="btn-outline register-btn" id="practice-btn"
                       onclick="return startPractice(event, <?= $id ?>)">
                        ⚡ Practice this Contest
                    </a>
                <?php elseif ($registered): ?>
                    <div class="already-registered">✓ You are registered</div>
                    <?php if ($contest['status'] === 'active'): ?>
                    <a href="#scoreboard-wrap" class="btn-primary register-btn" style="margin-top:12px">
                        Enter Contest
                    </a>
                    <?php endif; ?>
                <?php else: ?>
                    <button class="btn-primary register-btn" onclick="joinContest(<?= $id ?>)">
                        <?= $contest['status'] === 'active' ? 'Join & Enter' : 'Register' ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Admin controls -->
        <?php if (isAdmin() && $contest['status'] !== 'active'): ?>
        <div class="section-card" style="margin-bottom:20px;border-color:rgba(224,5,5,.25)">
            <div class="section-card-header" style="color:var(--red,#e05)">Admin</div>
            <div class="section-card-body">
                <a class="btn-outline" href="/code-arena/contest_manage.php?id=<?= $id ?>"
                   style="width:100%;justify-content:center;margin-bottom:10px">
                    Manage Setup
                </a>
                <button class="btn-outline" id="del-btn"
                        style="width:100%;justify-content:center;color:var(--red,#e05);border-color:var(--red,#e05)"
                        onclick="openDeleteModal()">
                    Delete Contest
                </button>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isInstructor() && !isAdmin()): ?>
        <div class="section-card" style="margin-bottom:20px">
            <div class="section-card-header">Instructor</div>
            <div class="section-card-body">
                <a class="btn-outline" href="/code-arena/contest_manage.php?id=<?= $id ?>" style="width:100%;justify-content:center">
                    Manage Setup
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Contest info -->
        <div class="section-card">
            <div class="section-card-header">Details</div>
            <div class="section-card-body" style="padding:0 20px">
                <div class="info-row"><span>Status</span><span><?= $statusLabel ?></span></div>
                <div class="info-row"><span>Start</span><span><?= $start->format('M j, Y H:i') ?></span></div>
                <div class="info-row"><span>End</span><span><?= $end->format('M j, Y H:i') ?></span></div>
                <div class="info-row"><span>Duration</span><span><?= $durLabel ?></span></div>
                <div class="info-row"><span>Participants</span><span><?= $participantCount ?></span></div>
                <div class="info-row"><span>Problems</span><span><?= count($problems) ?></span></div>
                <div class="info-row"><span>Rated</span><span><?= $contest['is_rated'] ? 'Yes' : 'No' ?></span></div>
                <div class="info-row"><span>Created by</span><span><?= htmlspecialchars($contest['author']) ?></span></div>
            </div>
        </div>
    </div>

</div>
</div>
</div>

<?php if (isAdmin() && $contest['status'] !== 'active'): ?>
<!-- Delete confirm modal -->
<div class="expired-overlay" id="del-overlay" style="display:none" onclick="closeDeleteModal(event)">
    <div class="expired-card">
        <h2>Delete Contest?</h2>
        <p>This cannot be undone. All problems, registrations, and results for this contest will be removed.</p>
        <div style="display:flex;gap:12px;justify-content:center">
            <button class="btn-outline" onclick="closeDeleteModal()">Cancel</button>
            <button class="btn-primary" id="del-confirm-btn"
                    style="background:var(--red,#e05);border-color:var(--red,#e05)"
                    onclick="confirmDelete()">Delete</button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($isPractice): ?>
<!-- Expired overlay (shown by JS when timer hits 0) -->
<div class="expired-overlay" id="expired-overlay" style="display:none">
    <div class="expired-card">
        <h2>⏱ Time's Up!</h2>
        <p>Your practice session has ended. Submissions are now locked for this session.</p>
        <a href="/code-arena/contest.php?id=<?= $id ?>" class="btn-primary">Back to Contest</a>
    </div>
</div>
<?php endif; ?>

<script src="/code-arena/assets/js/main.js"></script>
<script>
const CONTEST_ID = <?= $id ?>;
const CONTEST_STATUS = '<?= $contest['status'] ?>';
const IS_PRACTICE = <?= $isPractice ? 'true' : 'false' ?>;

async function loadScoreboard() {
    const wrap = document.getElementById('scoreboard-wrap');
    if (!wrap) return;

    const { ok, data } = await api(`/code-arena/api/contests/scoreboard.php?contest_id=${CONTEST_ID}`);
    if (!ok || !data.success) {
        wrap.innerHTML = `<p style="color:var(--red);font-size:.88rem">${escHtml(data.message || 'Failed to load scoreboard.')}</p>`;
        return;
    }

    const { problems, rows } = data.data;
    if (!rows.length) {
        wrap.innerHTML = '<p style="color:var(--text-muted);font-size:.88rem">No participants yet.</p>';
        return;
    }

    const problemHeaders = problems.map((p, i) =>
        `<th title="${escHtml(p.title)}">P${i + 1}<br><span style="font-weight:500;color:var(--text-muted)">${p.points}</span></th>`
    ).join('');

    const bodyRows = rows.map(row => {
        const cells = row.problems.map(cell => {
            const cls = cell.status === 'accepted' ? 'accepted' : (cell.status === 'attempted' ? 'attempted' : 'none');
            const label = cell.status === 'accepted'
                ? `+${cell.points}<br><span style="font-size:.72rem">${cell.solved_at_minutes}m</span>`
                : (cell.attempts > 0 ? `-${cell.failed_attempts}` : '-');
            return `<td class="score-cell ${cls}">${label}</td>`;
        }).join('');

        return `
            <tr>
                <td class="rank">#${row.rank}</td>
                <td><a href="/code-arena/profile.php?id=${row.user_id}" style="color:var(--text);font-weight:600">${escHtml(row.username)}</a></td>
                <td class="num" style="color:var(--accent);font-weight:700">${row.score}</td>
                <td class="num">${row.penalty_minutes}</td>
                ${cells}
            </tr>
        `;
    }).join('');

    wrap.innerHTML = `
        <table class="scoreboard-table">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>User</th>
                    <th class="num">Score</th>
                    <th class="num">Penalty</th>
                    ${problemHeaders}
                </tr>
            </thead>
            <tbody>${bodyRows}</tbody>
        </table>
    `;
}

function escHtml(s) {
    return String(s ?? '')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

if (!IS_PRACTICE && CONTEST_STATUS !== 'upcoming') {
    loadScoreboard();
    if (CONTEST_STATUS === 'active') setInterval(loadScoreboard, 30000);
}

// ── Contest deletion (admin) ──────────────────────────────────
<?php if (isAdmin() && $contest['status'] !== 'active'): ?>
function openDeleteModal() {
    document.getElementById('del-overlay').style.display = 'flex';
}
function closeDeleteModal(e) {
    if (e && e.target !== document.getElementById('del-overlay')) return;
    document.getElementById('del-overlay').style.display = 'none';
}
async function confirmDelete() {
    const btn = document.getElementById('del-confirm-btn');
    btn.disabled = true;
    btn.textContent = 'Deleting…';
    const { ok, data } = await api('/code-arena/api/contests/delete.php', {
        method: 'POST',
        body: JSON.stringify({ contest_id: <?= $id ?> }),
    });
    if (ok && data.success) {
        toast('Contest deleted', 'success');
        setTimeout(() => { window.location = '/code-arena/contests.php'; }, 900);
    } else {
        toast(data.message || 'Deletion failed', 'error');
        btn.disabled = false;
        btn.textContent = 'Delete';
    }
}
<?php endif; ?>

// ── Normal contest registration ───────────────────────────────
async function joinContest(id) {
    const btn = event.target;
    btn.disabled = true;
    btn.textContent = 'Registering…';
    const { ok, data } = await api('/code-arena/api/contests/index.php?action=join', {
        method: 'POST',
        body: JSON.stringify({ contest_id: id }),
    });
    if (ok && data.success) {
        toast('Registered!', 'success');
        setTimeout(() => location.reload(), 800);
    } else {
        toast(data.message || 'Failed', 'error');
        btn.disabled = false;
        btn.textContent = 'Register';
    }
}

// ── Practice: start session then navigate ────────────────────
async function startPractice(e, contestId) {
    e.preventDefault();
    const btn = document.getElementById('practice-btn');
    btn.textContent = 'Starting…';
    btn.style.pointerEvents = 'none';
    const { ok, data } = await api('/code-arena/api/practice/session.php', {
        method: 'POST',
        body: JSON.stringify({ contest_id: contestId }),
    });
    if (!ok || !data.success) {
        toast(data.message || 'Could not start practice session', 'error');
        btn.textContent = '⚡ Practice this Contest';
        btn.style.pointerEvents = '';
        return false;
    }
    window.location = `/code-arena/contest.php?id=${contestId}&practice=1`;
    return false;
}

// ── Practice mode timer ───────────────────────────────────────
<?php if ($isPractice): ?>
(async function initPracticeTimer() {
    const CONTEST_ID = <?= $id ?>;
    const { ok, data } = await api(`/code-arena/api/practice/session.php?contest_id=${CONTEST_ID}`);
    if (!ok || !data.success) {
        toast('Could not load practice session', 'error');
        return;
    }
    const sess = data.data;
    if (sess.expired) {
        document.getElementById('expired-overlay').style.display = 'flex';
        return;
    }

    let remaining = sess.seconds_remaining;
    const timerEl = document.getElementById('practice-timer');

    function fmt(s) {
        const h = Math.floor(s / 3600);
        const m = Math.floor((s % 3600) / 60);
        const sec = s % 60;
        return `${h}:${String(m).padStart(2,'0')}:${String(sec).padStart(2,'0')}`;
    }

    function tick() {
        timerEl.textContent = fmt(remaining);
        timerEl.className = 'practice-timer' +
            (remaining <= 300 ? ' warning' : '') +
            (remaining === 0  ? ' expired' : '');

        if (remaining <= 0) {
            document.getElementById('expired-overlay').style.display = 'flex';
            return;
        }
        remaining--;
        setTimeout(tick, 1000);
    }
    tick();
})();
<?php endif; ?>
</script>
</body>
</html>
