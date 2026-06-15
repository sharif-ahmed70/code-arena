<?php
// ============================================================
//  CODE ARENA - Editorial Manager
// ============================================================
require_once 'includes/session.php';
requireInstructor();

$problemId = (int)($_GET['problem_id'] ?? 0);
if (!$problemId) { header('Location: /code-arena/instructor.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editorial Manager - Code Arena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css?v=20260615-ui5">
    <style>
        .editorial-layout { display:grid; grid-template-columns: 1fr 320px; gap:24px; align-items:start; }
        @media(max-width:900px){ .editorial-layout { grid-template-columns:1fr; } }
        .editorial-textarea { min-height:220px; font-size:.92rem; line-height:1.65; }
        .solution-textarea { min-height:260px; font-family:'JetBrains Mono',monospace; font-size:.84rem; }
        .preview-box {
            background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius);
            padding:18px; color:var(--text-dim); line-height:1.75; white-space:pre-wrap;
        }
        .preview-box pre {
            background:var(--bg); padding:14px; border-radius:var(--radius-sm); overflow:auto;
            white-space:pre; margin-top:10px;
        }
        .meta-row { display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid var(--border); font-size:.88rem; }
        .meta-row span:first-child { color:var(--text-muted); }
    </style>
</head>
<body>
<?php require_once 'includes/navbar.php'; ?>

<div class="page">
<div class="container">
    <div class="page-header fade-up" style="display:flex;justify-content:space-between;align-items:flex-end;gap:16px">
        <div>
            <h1>Editorial Manager</h1>
            <p id="problem-subtitle">Loading problem...</p>
        </div>
        <a class="btn-outline" id="view-problem-link" href="/code-arena/problems.php">View Problem</a>
    </div>

    <div class="editorial-layout">
        <div class="card fade-up fade-up-1">
            <div class="form-group">
                <label class="form-label">Approach *</label>
                <textarea id="ed-approach" class="form-input editorial-textarea"
                          placeholder="Explain the reasoning, key observation, and algorithm."></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Complexity</label>
                <input id="ed-complexity" class="form-input" placeholder="Time: O(n), Space: O(1)">
            </div>
            <div class="form-group">
                <label class="form-label">Reference Solution</label>
                <textarea id="ed-solution" class="form-input solution-textarea"
                          placeholder="// Optional reference solution"></textarea>
            </div>
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                <button class="btn-primary" onclick="saveEditorial()">Save Editorial</button>
                <button class="btn-outline" onclick="renderPreview()">Preview</button>
                <span id="save-msg" style="font-size:.86rem;color:var(--text-muted)"></span>
            </div>
        </div>

        <div>
            <div class="card fade-up fade-up-2" style="margin-bottom:18px">
                <h3 style="margin-bottom:14px">Problem</h3>
                <div class="meta-row"><span>ID</span><span id="meta-id">-</span></div>
                <div class="meta-row"><span>Difficulty</span><span id="meta-difficulty">-</span></div>
                <div class="meta-row"><span>Last Updated</span><span id="meta-updated">-</span></div>
            </div>
            <div class="preview-box fade-up fade-up-3" id="preview-box">
                Preview will appear here.
            </div>
        </div>
    </div>
</div>
</div>

<script src="/code-arena/assets/js/main.js?v=20260615-ui5"></script>
<script>
const PROBLEM_ID = <?= $problemId ?>;
let currentProblem = null;

async function loadEditorial() {
    const { ok, data } = await api(`/code-arena/api/problems/editorial.php?problem_id=${PROBLEM_ID}`);
    if (!ok || !data.success) {
        toast(data.message || 'Failed to load editorial', 'error');
        return;
    }

    currentProblem = data.data.problem;
    const editorial = data.data.editorial;

    document.getElementById('problem-subtitle').textContent = `${currentProblem.title} (${currentProblem.difficulty})`;
    document.getElementById('view-problem-link').href = `/code-arena/problem.php?slug=${currentProblem.slug}`;
    document.getElementById('meta-id').textContent = currentProblem.id;
    document.getElementById('meta-difficulty').textContent = currentProblem.difficulty;
    document.getElementById('meta-updated').textContent = editorial.updated_at || 'Never';

    document.getElementById('ed-approach').value = editorial.approach || '';
    document.getElementById('ed-complexity').value = editorial.complexity || '';
    document.getElementById('ed-solution').value = editorial.reference_solution || '';
    renderPreview();
}

async function saveEditorial() {
    const payload = {
        problem_id: PROBLEM_ID,
        approach: document.getElementById('ed-approach').value.trim(),
        complexity: document.getElementById('ed-complexity').value.trim(),
        reference_solution: document.getElementById('ed-solution').value.trim(),
    };

    const msg = document.getElementById('save-msg');
    msg.textContent = 'Saving...';
    const { ok, data } = await api('/code-arena/api/problems/editorial.php', {
        method: 'POST',
        body: JSON.stringify(payload),
    });

    if (ok && data.success) {
        msg.textContent = 'Saved';
        toast('Editorial saved', 'success');
        renderPreview();
        loadEditorial();
    } else {
        msg.textContent = data.message || 'Failed';
        toast(data.message || 'Failed to save editorial', 'error');
    }
}

function renderPreview() {
    const approach = document.getElementById('ed-approach').value.trim();
    const complexity = document.getElementById('ed-complexity').value.trim();
    const solution = document.getElementById('ed-solution').value.trim();

    document.getElementById('preview-box').innerHTML = `
<strong>Approach</strong>
${escHtml(approach || 'No approach written yet.')}
${complexity ? `\n\n<strong>Complexity</strong>\n${escHtml(complexity)}` : ''}
${solution ? `\n\n<strong>Reference Solution</strong><pre>${escHtml(solution)}</pre>` : ''}
    `;
}

function escHtml(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

loadEditorial();
</script>
</body>
</html>
