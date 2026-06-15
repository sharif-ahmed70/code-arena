<?php
require_once 'includes/session.php';
require_once 'config/db.php';
requireLogin();
$isAdmin = isAdmin();
$linkedProblem = null;
$problemId = (int)($_GET['problem_id'] ?? 0);
if ($problemId) {
    $stmt = $pdo->prepare('SELECT id, title, slug, difficulty, tags FROM problems WHERE id = ? AND is_public = 1 AND COALESCE(is_deleted, 0) = 0');
    $stmt->execute([$problemId]);
    $linkedProblem = $stmt->fetch() ?: null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Post — Discuss — Code Arena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css?v=20260615-ui2">
    <style>
        .create-wrap { max-width:720px; margin:0 auto; }
        .back-link { display:inline-flex; align-items:center; gap:6px; color:var(--text-muted);
                     font-size:.88rem; margin-bottom:20px; }
        .back-link:hover { color:var(--accent); }

        .cat-grid { display:flex; gap:8px; flex-wrap:wrap; }
        .cat-opt  { padding:7px 16px; border-radius:100px; border:1px solid var(--border);
                    font-size:.85rem; cursor:pointer; color:var(--text-muted); background:var(--bg-card2);
                    transition:all .15s; }
        .cat-opt:hover  { border-color:var(--accent); color:var(--accent); }
        .cat-opt.selected { background:var(--accent); border-color:var(--accent); color:#0a0a0f; font-weight:600; }

        .tag-input-wrap { position:relative; }
        .tag-pills { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:8px; }
        .tag-pill { display:flex; align-items:center; gap:4px; padding:3px 10px; border-radius:100px;
                    background:var(--bg-card2); border:1px solid var(--border); font-size:.78rem; color:var(--text-dim); }
        .tag-pill button { background:none; border:none; color:var(--text-muted); cursor:pointer;
                           font-size:.85rem; padding:0 2px; line-height:1; }
        .tag-pill button:hover { color:var(--red); }

        .team-toggle { display:flex; align-items:center; gap:10px; cursor:pointer; }
        .team-toggle input[type=checkbox] { width:16px; height:16px; accent-color:var(--accent); }

        .content-area { min-height:200px; resize:vertical; font-family:'JetBrains Mono', monospace; font-size:.88rem; }
        .char-count { font-size:.75rem; color:var(--text-muted); text-align:right; margin-top:4px; }
        .linked-problem { padding:12px 14px; border:1px solid rgba(108,160,255,.28);
                          background:var(--blue-dim); border-radius:var(--radius-sm);
                          color:var(--text-dim); font-size:.88rem; margin-bottom:18px; }
        .linked-problem a { color:var(--blue); font-weight:600; }
    </style>
</head>
<body>
<?php require_once 'includes/navbar.php'; ?>

<div class="page">
<div class="container">
<div class="create-wrap">

    <a href="/code-arena/discuss.php" class="back-link fade-up">← Back to Discuss</a>

    <div class="card fade-up fade-up-1">
        <h2 style="margin-bottom:24px">New Post</h2>

        <?php if ($linkedProblem): ?>
        <div class="linked-problem">
            Asking about
            <a href="/code-arena/problem.php?slug=<?= htmlspecialchars($linkedProblem['slug']) ?>">
                <?= htmlspecialchars($linkedProblem['title']) ?>
            </a>
            <span style="color:var(--text-muted)">· <?= htmlspecialchars($linkedProblem['difficulty']) ?></span>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label class="form-label">Title <span style="color:var(--red)">*</span></label>
            <input type="text" id="title" class="form-input" placeholder="What's your question or topic?" maxlength="200"
                   value="<?= $linkedProblem ? htmlspecialchars('Help with ' . $linkedProblem['title']) : '' ?>">
        </div>

        <div class="form-group">
            <label class="form-label">Category</label>
            <div class="cat-grid" id="cat-grid">
                <?php foreach (['General','Career','Contest','Feedback','Interview'] as $cat): ?>
                <span class="cat-opt <?= $cat === ($linkedProblem ? 'Interview' : 'General') ? 'selected' : '' ?>"
                      data-cat="<?= $cat ?>"><?= $cat ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Tags <span style="color:var(--text-muted);font-weight:400;font-size:.8rem">(optional — press Enter or comma to add)</span></label>
            <div class="tag-pills" id="tag-pills"></div>
            <input type="text" id="tag-input" class="form-input" placeholder="Add a tag…" maxlength="40">
        </div>

        <div class="form-group">
            <label class="form-label">Content <span style="color:var(--red)">*</span></label>
            <textarea id="content" class="form-input content-area" rows="10"
                      placeholder="Write your post here. Share details, code snippets, links…" maxlength="10000"><?= $linkedProblem ? htmlspecialchars("I am stuck on " . $linkedProblem['title'] . ".\n\nWhat I tried:\n\nWhere I am confused:\n\nMy code or idea:\n") : '' ?></textarea>
            <div class="char-count"><span id="char-count">0</span> / 10000</div>
        </div>

        <div class="form-group">
            <label class="team-toggle">
                <input type="checkbox" id="is-team">
                <span>Looking for Team <span style="font-size:.8rem;color:var(--text-muted)">(for upcoming contests)</span></span>
            </label>
        </div>

        <?php if ($isAdmin): ?>
        <div class="form-group">
            <label class="team-toggle">
                <input type="checkbox" id="is-pinned">
                <span style="color:var(--yellow)">📌 Pin this post <span style="font-size:.8rem;color:var(--text-muted)">(admin only)</span></span>
            </label>
        </div>
        <?php endif; ?>

        <div style="display:flex;gap:12px;margin-top:8px">
            <button class="btn-primary" onclick="submitPost()" id="submit-btn">Publish Post</button>
            <a href="/code-arena/discuss.php" class="btn-outline">Cancel</a>
        </div>
    </div>

</div>
</div>
</div>

<script src="/code-arena/assets/js/main.js"></script>
<script>
const IS_ADMIN = <?= $isAdmin ? 'true' : 'false' ?>;
const LINKED_PROBLEM_ID = <?= $linkedProblem ? (int)$linkedProblem['id'] : 'null' ?>;
let selectedCat = '<?= $linkedProblem ? 'Interview' : 'General' ?>';
let tags = <?= json_encode($linkedProblem && !empty($linkedProblem['tags'])
    ? array_values(array_filter(array_map('trim', explode(',', $linkedProblem['tags']))))
    : []) ?>;

// Category selection
document.querySelectorAll('.cat-opt').forEach(el => {
    el.addEventListener('click', () => {
        document.querySelectorAll('.cat-opt').forEach(e => e.classList.remove('selected'));
        el.classList.add('selected');
        selectedCat = el.dataset.cat;
    });
});

// Tag input
const tagInput = document.getElementById('tag-input');
tagInput.addEventListener('keydown', e => {
    if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        addTag(tagInput.value.trim().replace(/,/g,''));
    }
});
tagInput.addEventListener('blur', () => addTag(tagInput.value.trim().replace(/,/g,'')));

function addTag(val) {
    val = val.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
    if (!val || tags.includes(val) || tags.length >= 10) { tagInput.value = ''; return; }
    tags.push(val);
    tagInput.value = '';
    renderTags();
}

function removeTag(t) {
    tags = tags.filter(x => x !== t);
    renderTags();
}

function renderTags() {
    document.getElementById('tag-pills').innerHTML = tags.map(t =>
        `<span class="tag-pill">${t}<button onclick="removeTag('${t}')" type="button">×</button></span>`
    ).join('');
}

// Char count
document.getElementById('content').addEventListener('input', function() {
    document.getElementById('char-count').textContent = this.value.length;
});

async function submitPost() {
    const title   = document.getElementById('title').value.trim();
    const content = document.getElementById('content').value.trim();
    const isTeam  = document.getElementById('is-team').checked;
    const isPinned = IS_ADMIN && document.getElementById('is-pinned')?.checked;

    if (!title)   { toast('Title is required', 'warn'); return; }
    if (!content) { toast('Content is required', 'warn'); return; }

    const btn = document.getElementById('submit-btn');
    btn.disabled = true; btn.textContent = 'Publishing…';

    const body = { title, content, category: selectedCat, tags: tags.join(','), is_team_post: isTeam };
    if (LINKED_PROBLEM_ID) body.problem_id = LINKED_PROBLEM_ID;
    if (isPinned) body.is_pinned = true;

    const { ok, data } = await api('/code-arena/api/discuss/posts.php', {
        method: 'POST', body: JSON.stringify(body)
    });

    btn.disabled = false; btn.textContent = 'Publish Post';
    if (!ok || !data.success) { toast(data.message || 'Failed to create post', 'error'); return; }

    toast('Post published!', 'success');
    setTimeout(() => window.location = `/code-arena/discuss_post.php?id=${data.data.id}`, 600);
}

renderTags();
document.getElementById('char-count').textContent = document.getElementById('content').value.length;
</script>
</body>
</html>
