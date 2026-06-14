<?php
require_once 'includes/session.php';
require_once 'config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discuss — Code Arena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css">
    <style>
        .discuss-header { display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:28px; }
        .discuss-header p { color:var(--text-muted); font-size:.9rem; margin-top:4px; }

        .discuss-toolbar { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:20px; align-items:center; }
        .cat-tabs { display:flex; gap:6px; flex-wrap:wrap; flex:1; }
        .cat-tab { padding:6px 14px; border-radius:100px; border:1px solid var(--border);
                   font-size:.82rem; font-weight:500; cursor:pointer; color:var(--text-muted);
                   background:var(--bg-card); transition:all .15s; white-space:nowrap; }
        .cat-tab:hover   { border-color:var(--accent); color:var(--accent); }
        .cat-tab.active  { background:var(--accent); border-color:var(--accent); color:#0a0a0f; }
        .sort-select { padding:8px 12px; background:var(--bg-card); border:1px solid var(--border);
                       border-radius:var(--radius-sm); color:var(--text); font-size:.85rem; outline:none; cursor:pointer; }
        .search-wrap { flex:1; min-width:200px; max-width:280px; }

        .post-card { padding:18px 20px; border-bottom:1px solid var(--border); transition:background .15s; }
        .post-card:hover { background:var(--bg-card2); }
        .post-card:last-child { border-bottom:none; }
        .post-top { display:flex; align-items:flex-start; gap:14px; }
        .vote-col { display:flex; flex-direction:column; align-items:center; gap:4px;
                    min-width:38px; padding-top:2px; }
        .vote-count { font-size:.85rem; font-weight:600; color:var(--text-dim); line-height:1; }
        .post-body { flex:1; min-width:0; }
        .post-title { font-size:1rem; font-weight:600; color:var(--text); margin-bottom:6px; line-height:1.35; }
        .post-title a { color:inherit; }
        .post-title a:hover { color:var(--accent); }
        .post-meta { display:flex; gap:10px; flex-wrap:wrap; align-items:center; font-size:.78rem; color:var(--text-muted); }
        .post-tags-wrap { display:flex; gap:5px; flex-wrap:wrap; margin-top:7px; }
        .dtag { font-size:.7rem; padding:2px 8px; border-radius:100px;
                background:var(--bg-card2); color:var(--text-muted); border:1px solid var(--border); }
        .problem-pill { font-size:.72rem; padding:2px 8px; border-radius:100px;
                        background:var(--blue-dim); color:var(--blue);
                        border:1px solid rgba(108,160,255,.28); }

        .pin-badge  { font-size:.68rem; font-weight:700; padding:2px 7px; border-radius:100px;
                      background:var(--yellow); color:#0a0a0f; text-transform:uppercase; letter-spacing:.04em; }
        .team-badge { font-size:.68rem; font-weight:700; padding:2px 7px; border-radius:100px;
                      background:var(--blue-dim); color:var(--blue); border:1px solid rgba(79,126,248,.3);
                      text-transform:uppercase; letter-spacing:.04em; }

        .cat-badge { font-size:.7rem; padding:2px 8px; border-radius:4px; font-weight:600; }
        .cat-General   { background:rgba(148,148,180,.12); color:#9494b4; }
        .cat-Career    { background:rgba(0,255,136,.1);    color:var(--accent); }
        .cat-Contest   { background:rgba(255,209,102,.12); color:var(--yellow); }
        .cat-Feedback  { background:rgba(79,126,248,.12);  color:var(--blue); }
        .cat-Interview { background:rgba(255,79,79,.12);   color:var(--red); }

        .meta-dot { color:var(--border); }
        .stat-item { display:flex; align-items:center; gap:4px; }

        .empty-discuss { text-align:center; padding:60px 20px; color:var(--text-muted); }
        .empty-discuss p { margin-top:8px; font-size:.9rem; }

        .pagination { display:flex; gap:8px; justify-content:center; margin-top:24px; }
        .page-btn { padding:7px 14px; border-radius:var(--radius-sm); border:1px solid var(--border);
                    background:var(--bg-card); color:var(--text-dim); font-size:.85rem; cursor:pointer; }
        .page-btn:hover, .page-btn.active { border-color:var(--accent); color:var(--accent); }
    </style>
</head>
<body>
<?php require_once 'includes/navbar.php'; ?>

<div class="page">
<div class="container">

    <div class="discuss-header fade-up">
        <div>
            <h1>Discuss</h1>
            <p>Ask questions, share solutions, find your contest team.</p>
        </div>
        <?php if (isLoggedIn()): ?>
        <a href="/code-arena/discuss_create.php" class="btn-primary">+ New Post</a>
        <?php else: ?>
        <a href="/code-arena/login.php" class="btn-outline">Login to post</a>
        <?php endif; ?>
    </div>

    <div class="discuss-toolbar fade-up fade-up-1">
        <div class="cat-tabs">
            <button class="cat-tab active" data-cat="">All</button>
            <button class="cat-tab" data-cat="General">General</button>
            <button class="cat-tab" data-cat="Career">Career</button>
            <button class="cat-tab" data-cat="Contest">Contest</button>
            <button class="cat-tab" data-cat="Feedback">Feedback</button>
            <button class="cat-tab" data-cat="Interview">Interview</button>
        </div>
        <input type="text" id="search" class="form-input search-wrap" placeholder="Search posts…" oninput="debounceLoad()">
        <select id="sort" class="sort-select" onchange="loadPosts()">
            <option value="newest">Newest</option>
            <option value="votes">Most Voted</option>
            <option value="views">Most Viewed</option>
        </select>
    </div>

    <div class="card" style="padding:0;overflow:hidden" id="posts-wrap">
        <div id="posts-body"><div class="empty-discuss"><p>Loading…</p></div></div>
    </div>
    <div class="pagination" id="pagination"></div>

</div>
</div>

<script src="/code-arena/assets/js/main.js"></script>
<script>
let currentPage = 1;
let currentCat  = '';
let debTimer;
const initialParams = new URLSearchParams(window.location.search);
const PROBLEM_ID = initialParams.get('problem_id') || '';

function debounceLoad() {
    clearTimeout(debTimer);
    debTimer = setTimeout(loadPosts, 350);
}

document.querySelectorAll('.cat-tab').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.cat-tab').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentCat = btn.dataset.cat;
        loadPosts();
    });
});

async function loadPosts(page = 1) {
    currentPage = page;
    const search = document.getElementById('search').value.trim();
    const sort   = document.getElementById('sort').value;
    const params = new URLSearchParams({ page, sort });
    if (currentCat) params.set('category', currentCat);
    if (search)     params.set('search', search);
    if (PROBLEM_ID) params.set('problem_id', PROBLEM_ID);

    const { ok, data } = await api(`/code-arena/api/discuss/posts.php?${params}`);
    const body = document.getElementById('posts-body');
    if (!ok || !data.success) {
        body.innerHTML = `<div class="empty-discuss"><p>Failed to load posts.</p></div>`;
        return;
    }

    const posts = data.data.posts;
    if (!posts.length) {
        body.innerHTML = `<div class="empty-discuss"><p>No posts yet. <a href="/code-arena/discuss_create.php" style="color:var(--accent)">Start the conversation →</a></p></div>`;
        renderPagination(data.data);
        return;
    }

    body.innerHTML = posts.map(p => {
        const tags = (p.tags || '').split(',').filter(Boolean).map(t =>
            `<span class="dtag">${t.trim()}</span>`).join('');
        const problemPill = p.problem_id
            ? `<a class="problem-pill" href="/code-arena/problem.php?slug=${encodeURIComponent(p.problem_slug)}">${escHtml(p.problem_title)}</a>`
            : '';
        const pinBadge  = p.is_pinned    ? '<span class="pin-badge">📌 Pinned</span>' : '';
        const teamBadge = p.is_team_post ? '<span class="team-badge">Looking for Team</span>' : '';
        const net = p.upvotes - p.downvotes;
        return `
        <div class="post-card">
            <div class="post-top">
                <div class="vote-col">
                    <span class="vote-count" style="color:${net>0?'var(--accent)':net<0?'var(--red)':'var(--text-muted)'}">${net}</span>
                    <span style="font-size:.65rem;color:var(--text-muted)">votes</span>
                </div>
                <div class="post-body">
                    <div class="post-title">
                        ${pinBadge}${teamBadge}
                        <a href="/code-arena/discuss_post.php?id=${p.id}">${escHtml(p.title)}</a>
                    </div>
                    <div class="post-meta">
                        <span class="cat-badge cat-${p.category}">${p.category}</span>
                        <span class="meta-dot">·</span>
                        <span>by <strong>${escHtml(p.author)}</strong></span>
                        <span class="meta-dot">·</span>
                        <span class="stat-item">💬 ${p.comment_count}</span>
                        <span class="meta-dot">·</span>
                        <span class="stat-item">👁 ${p.views}</span>
                        <span class="meta-dot">·</span>
                        <span>${timeAgo(p.created_at)}</span>
                        ${problemPill}
                    </div>
                    ${tags ? `<div class="post-tags-wrap">${tags}</div>` : ''}
                </div>
            </div>
        </div>`;
    }).join('');

    renderPagination(data.data);
}

function renderPagination({ page, pages }) {
    const el = document.getElementById('pagination');
    if (pages <= 1) { el.innerHTML = ''; return; }
    let html = '';
    if (page > 1) html += `<button class="page-btn" onclick="loadPosts(${page-1})">← Prev</button>`;
    for (let p = Math.max(1, page-2); p <= Math.min(pages, page+2); p++) {
        html += `<button class="page-btn ${p===page?'active':''}" onclick="loadPosts(${p})">${p}</button>`;
    }
    if (page < pages) html += `<button class="page-btn" onclick="loadPosts(${page+1})">Next →</button>`;
    el.innerHTML = html;
}

function escHtml(s) {
    const d = document.createElement('div'); d.textContent = s; return d.innerHTML;
}

loadPosts();
</script>
</body>
</html>
