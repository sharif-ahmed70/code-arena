<?php
require_once 'includes/session.php';
require_once 'config/db.php';
$postId = (int) ($_GET['id'] ?? 0);
if (!$postId) { header('Location: /code-arena/discuss.php'); exit; }
$loggedIn = isLoggedIn();
$uid      = currentUserId();
$isAdmin  = isAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discuss — Code Arena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css?v=20260615-ui2">
    <style>
        .back-link { display:inline-flex; align-items:center; gap:6px; color:var(--text-muted);
                     font-size:.88rem; margin-bottom:20px; }
        .back-link:hover { color:var(--accent); }

        .post-header { margin-bottom:20px; }
        .post-header h1 { font-size:1.5rem; line-height:1.3; margin-bottom:10px; }
        .post-meta { display:flex; gap:10px; flex-wrap:wrap; align-items:center;
                     font-size:.82rem; color:var(--text-muted); margin-bottom:12px; }
        .post-tags-wrap { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:16px; }
        .dtag { font-size:.72rem; padding:2px 9px; border-radius:100px;
                background:var(--bg-card2); color:var(--text-muted); border:1px solid var(--border); }

        .cat-badge { font-size:.72rem; padding:2px 9px; border-radius:4px; font-weight:600; }
        .cat-General   { background:rgba(148,148,180,.12); color:#9494b4; }
        .cat-Career    { background:rgba(0,255,136,.1);    color:var(--accent); }
        .cat-Contest   { background:rgba(255,209,102,.12); color:var(--yellow); }
        .cat-Feedback  { background:rgba(79,126,248,.12);  color:var(--blue); }
        .cat-Interview { background:rgba(255,79,79,.12);   color:var(--red); }

        .post-content { line-height:1.75; color:var(--text-dim); font-size:.95rem;
                        white-space:pre-wrap; word-break:break-word; }
        .problem-link-card { display:flex; justify-content:space-between; gap:12px; align-items:center;
                             padding:12px 14px; border-radius:var(--radius-sm);
                             border:1px solid rgba(108,160,255,.28); background:var(--blue-dim);
                             margin-bottom:16px; }
        .problem-link-card a { color:var(--blue); font-weight:700; }
        .problem-link-card span { color:var(--text-muted); font-size:.8rem; }

        .vote-row { display:flex; align-items:center; gap:14px; margin-top:20px; padding-top:16px;
                    border-top:1px solid var(--border); }
        .vote-btn { display:flex; align-items:center; gap:6px; padding:7px 14px;
                    border-radius:100px; border:1px solid var(--border); background:var(--bg-card2);
                    color:var(--text-muted); font-size:.85rem; font-weight:500; cursor:pointer;
                    transition:all .15s; }
        .vote-btn:hover                      { border-color:var(--accent); color:var(--accent); }
        .vote-btn.voted-up                   { border-color:var(--accent); color:var(--accent); background:var(--accent-dim); }
        .vote-btn.voted-down                 { border-color:var(--red);    color:var(--red);    background:var(--red-dim); }
        .vote-btn[data-vote="-1"]:hover      { border-color:var(--red);    color:var(--red); }
        .delete-btn { margin-left:auto; color:var(--text-muted); font-size:.82rem; cursor:pointer;
                      background:none; border:none; padding:6px 10px; border-radius:var(--radius-sm); }
        .delete-btn:hover { color:var(--red); background:var(--red-dim); }

        /* Comments */
        .comments-header { display:flex; justify-content:space-between; align-items:center;
                           margin-bottom:16px; }
        .comments-header h3 { font-size:1rem; font-weight:600; }
        .sort-tabs { display:flex; gap:4px; }
        .sort-tab { padding:5px 12px; border-radius:100px; border:1px solid var(--border);
                    font-size:.78rem; cursor:pointer; color:var(--text-muted); background:none; }
        .sort-tab.active { border-color:var(--accent); color:var(--accent); }

        .comment-card { padding:14px 0; border-bottom:1px solid var(--border); }
        .comment-card:last-child { border-bottom:none; }
        .comment-author { font-size:.8rem; font-weight:600; color:var(--text); margin-bottom:6px; }
        .comment-author span { font-weight:400; color:var(--text-muted); margin-left:6px; }
        .comment-content { font-size:.9rem; color:var(--text-dim); line-height:1.65;
                           white-space:pre-wrap; word-break:break-word; }
        .comment-actions { display:flex; align-items:center; gap:10px; margin-top:8px; }
        .c-vote-btn { display:flex; align-items:center; gap:4px; padding:3px 10px;
                      border-radius:100px; border:1px solid transparent; font-size:.78rem;
                      cursor:pointer; background:none; color:var(--text-muted);
                      transition:all .15s; }
        .c-vote-btn:hover                  { color:var(--accent); border-color:var(--accent); }
        .c-vote-btn.voted-up               { color:var(--accent); border-color:var(--accent); }
        .c-vote-btn[data-vote="-1"]:hover  { color:var(--red); border-color:var(--red); }
        .c-vote-btn.voted-down             { color:var(--red); border-color:var(--red); }
        .c-delete-btn { margin-left:auto; font-size:.75rem; color:var(--text-muted); cursor:pointer;
                        background:none; border:none; padding:2px 6px; border-radius:4px; }
        .c-delete-btn:hover { color:var(--red); }

        .comment-form textarea { width:100%; min-height:90px; resize:vertical; }
        .no-comments { color:var(--text-muted); font-size:.88rem; text-align:center; padding:24px 0; }
    </style>
</head>
<body>
<?php require_once 'includes/navbar.php'; ?>

<div class="page">
<div class="container" style="max-width:800px">

    <a href="/code-arena/discuss.php" class="back-link fade-up">← Back to Discuss</a>

    <!-- Post -->
    <div class="card fade-up fade-up-1" id="post-card">
        <div style="color:var(--text-muted);font-size:.9rem">Loading…</div>
    </div>

    <!-- Comments -->
    <div class="card fade-up fade-up-2" style="margin-top:20px">
        <div class="comments-header">
            <h3 id="comment-count-heading">Comments</h3>
            <div class="sort-tabs">
                <button class="sort-tab active" data-sort="best">Best</button>
                <button class="sort-tab" data-sort="newest">Newest</button>
            </div>
        </div>
        <div id="comments-body"><p class="no-comments">Loading…</p></div>

        <?php if ($loggedIn): ?>
        <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border)">
            <div class="form-group">
                <textarea id="new-comment" class="form-input comment-form" rows="3"
                          placeholder="Write a comment…"></textarea>
            </div>
            <button class="btn-primary" onclick="submitComment()" style="margin-top:8px">Post Comment</button>
        </div>
        <?php else: ?>
        <p style="margin-top:16px;font-size:.88rem;color:var(--text-muted)">
            <a href="/code-arena/login.php" style="color:var(--accent)">Login</a> to leave a comment.
        </p>
        <?php endif; ?>
    </div>

</div>
</div>

<script src="/code-arena/assets/js/main.js"></script>
<script>
const POST_ID    = <?= $postId ?>;
const IS_LOGGED  = <?= $loggedIn ? 'true' : 'false' ?>;
const IS_ADMIN   = <?= $isAdmin  ? 'true' : 'false' ?>;
const MY_ID      = <?= $uid ?? 'null' ?>;
let currentSort  = 'best';

document.querySelectorAll('.sort-tab').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.sort-tab').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentSort = btn.dataset.sort;
        loadPost();
    });
});

async function loadPost() {
    const { ok, data } = await api(`/code-arena/api/discuss/post.php?id=${POST_ID}&sort=${currentSort}`);
    if (!ok || !data.success) {
        document.getElementById('post-card').innerHTML = '<p style="color:var(--red)">Post not found.</p>';
        return;
    }
    renderPost(data.data.post);
    renderComments(data.data.comments);
}

function renderPost(p) {
    const tags = (p.tags || '').split(',').filter(Boolean).map(t =>
        `<span class="dtag">${escHtml(t.trim())}</span>`).join('');
    const upCls   = p.user_vote === 1  ? 'voted-up'   : '';
    const downCls = p.user_vote === -1 ? 'voted-down' : '';
    const canDel  = IS_ADMIN || MY_ID === p.author_id;
    document.title = p.title + ' — Discuss — Code Arena';

    document.getElementById('post-card').innerHTML = `
        <div class="post-header">
            <h1>${escHtml(p.title)}</h1>
            ${p.problem_id ? `
            <div class="problem-link-card">
                <div>
                    <span>Linked Problem</span><br>
                    <a href="/code-arena/problem.php?slug=${encodeURIComponent(p.problem_slug)}">${escHtml(p.problem_title)}</a>
                </div>
                ${difficultyBadge(p.problem_difficulty)}
            </div>` : ''}
            <div class="post-meta">
                <span class="cat-badge cat-${p.category}">${p.category}</span>
                ${p.is_pinned    ? '<span style="font-size:.7rem;font-weight:700;color:var(--yellow)">📌 Pinned</span>' : ''}
                ${p.is_team_post ? '<span style="font-size:.7rem;font-weight:600;color:var(--blue)">Looking for Team</span>' : ''}
                <span>by <strong>${escHtml(p.author)}</strong></span>
                <span>·</span>
                <span>${timeAgo(p.created_at)}</span>
                <span>·</span>
                <span>👁 ${p.views} views</span>
            </div>
            ${tags ? `<div class="post-tags-wrap">${tags}</div>` : ''}
        </div>
        <div class="post-content">${escHtml(p.content)}</div>
        <div class="vote-row">
            <button class="vote-btn ${upCls}" data-vote="1"
                    onclick="votePost(1, this)" id="btn-up"
                    ${IS_LOGGED ? '' : 'disabled title="Login to vote"'}>
                ▲ <span id="post-upvotes">${p.upvotes}</span>
            </button>
            <button class="vote-btn ${downCls}" data-vote="-1"
                    onclick="votePost(-1, this)" id="btn-down"
                    ${IS_LOGGED ? '' : 'disabled title="Login to vote"'}>
                ▼ <span id="post-downvotes">${p.downvotes}</span>
            </button>
            ${canDel ? `<button class="delete-btn" onclick="deletePost()">🗑 Delete</button>` : ''}
        </div>`;
}

function renderComments(comments) {
    const heading = document.getElementById('comment-count-heading');
    heading.textContent = `${comments.length} Comment${comments.length !== 1 ? 's' : ''}`;
    const body = document.getElementById('comments-body');
    if (!comments.length) {
        body.innerHTML = '<p class="no-comments">No comments yet. Be the first!</p>';
        return;
    }
    body.innerHTML = comments.map(c => {
        const upCls   = c.user_vote === 1  ? 'voted-up'   : '';
        const downCls = c.user_vote === -1 ? 'voted-down' : '';
        const canDel  = IS_ADMIN || MY_ID === c.author_id;
        return `
        <div class="comment-card" id="comment-${c.id}">
            <div class="comment-author">${escHtml(c.author)}<span>${timeAgo(c.created_at)}</span></div>
            <div class="comment-content">${escHtml(c.content)}</div>
            <div class="comment-actions">
                <button class="c-vote-btn ${upCls}" data-cid="${c.id}" data-vote="1"
                        onclick="voteComment(${c.id}, 1, this)"
                        ${IS_LOGGED ? '' : 'disabled'}>
                    ▲ <span class="cup-${c.id}">${c.upvotes}</span>
                </button>
                <button class="c-vote-btn ${downCls}" data-cid="${c.id}" data-vote="-1"
                        onclick="voteComment(${c.id}, -1, this)"
                        ${IS_LOGGED ? '' : 'disabled'}>
                    ▼ <span class="cdn-${c.id}">${c.downvotes}</span>
                </button>
                ${canDel ? `<button class="c-delete-btn" onclick="deleteComment(${c.id}, ${c.post_id})">Delete</button>` : ''}
            </div>
        </div>`;
    }).join('');
}

async function votePost(vote, btn) {
    if (!IS_LOGGED) { toast('Login to vote', 'warn'); return; }
    const { ok, data } = await api('/code-arena/api/discuss/vote.php', {
        method: 'POST',
        body: JSON.stringify({ target_type: 'post', target_id: POST_ID, vote })
    });
    if (!ok || !data.success) { toast(data.message || 'Vote failed', 'error'); return; }
    const { user_vote, upvotes, downvotes } = data.data;
    document.getElementById('post-upvotes').textContent   = upvotes;
    document.getElementById('post-downvotes').textContent = downvotes;
    document.getElementById('btn-up').className   = 'vote-btn' + (user_vote === 1  ? ' voted-up'   : '');
    document.getElementById('btn-down').className = 'vote-btn' + (user_vote === -1 ? ' voted-down' : '');
}

async function voteComment(cid, vote, btn) {
    if (!IS_LOGGED) { toast('Login to vote', 'warn'); return; }
    const { ok, data } = await api('/code-arena/api/discuss/vote.php', {
        method: 'POST',
        body: JSON.stringify({ target_type: 'comment', target_id: cid, vote })
    });
    if (!ok || !data.success) { toast(data.message || 'Vote failed', 'error'); return; }
    const { user_vote, upvotes, downvotes } = data.data;
    document.querySelector(`.cup-${cid}`).textContent = upvotes;
    document.querySelector(`.cdn-${cid}`).textContent = downvotes;
    // Reset both buttons for this comment then set correct class
    document.querySelectorAll(`[data-cid="${cid}"]`).forEach(b => {
        b.className = 'c-vote-btn';
        const bv = parseInt(b.dataset.vote);
        if (user_vote === 1  && bv === 1)  b.className += ' voted-up';
        if (user_vote === -1 && bv === -1) b.className += ' voted-down';
    });
}

async function submitComment() {
    const content = document.getElementById('new-comment').value.trim();
    if (!content) { toast('Write something first', 'warn'); return; }
    const { ok, data } = await api('/code-arena/api/discuss/comment.php', {
        method: 'POST',
        body: JSON.stringify({ post_id: POST_ID, content })
    });
    if (!ok || !data.success) { toast(data.message || 'Failed to post comment', 'error'); return; }
    document.getElementById('new-comment').value = '';
    toast('Comment posted!', 'success');
    loadPost();
}

async function deletePost() {
    if (!confirm('Delete this post and all its comments?')) return;
    const { ok, data } = await api(`/code-arena/api/discuss/post.php?id=${POST_ID}`, { method: 'DELETE' });
    if (!ok || !data.success) { toast(data.message || 'Delete failed', 'error'); return; }
    toast('Post deleted', 'success');
    setTimeout(() => window.location = '/code-arena/discuss.php', 800);
}

async function deleteComment(cid) {
    if (!confirm('Delete this comment?')) return;
    const { ok, data } = await api(`/code-arena/api/discuss/comment.php?id=${cid}`, { method: 'DELETE' });
    if (!ok || !data.success) { toast(data.message || 'Delete failed', 'error'); return; }
    document.getElementById(`comment-${cid}`)?.remove();
    toast('Comment deleted', 'success');
}

function escHtml(s) {
    const d = document.createElement('div'); d.textContent = s; return d.innerHTML;
}

loadPost();
</script>
</body>
</html>
