<?php
// ============================================================
//  CODE ARENA — Problems List
// ============================================================
require_once 'includes/session.php';
require_once 'config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Problems — Code Arena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css?v=20260615-ui2">
    <style>
        .filters { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:24px; align-items:center; }
        .filter-input { flex:1; min-width:200px; }
        .filter-select { padding:10px 14px; background:var(--bg-card); border:1px solid var(--border);
                         border-radius:var(--radius-sm); color:var(--text); font-size:.9rem;
                         cursor:pointer; outline:none; min-width:130px; }
        .filter-select:focus { border-color:var(--accent); }
        .problems-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; }
        .problems-header span { color:var(--text-muted); font-size:.9rem; }
        .prob-row { display:grid; grid-template-columns: 40px 1fr 100px 100px 80px 80px; gap:16px;
                    align-items:center; padding:14px 16px; border-bottom:1px solid var(--border);
                    transition:background .15s; }
        .prob-row:hover { background:var(--bg-card2); }
        .prob-row:last-child { border-bottom:none; }
        .prob-row.header { background:var(--bg-card2); font-size:.8rem; font-weight:600;
                           color:var(--text-muted); text-transform:uppercase; letter-spacing:.05em;
                           border-radius:var(--radius) var(--radius) 0 0; }
        .prob-num { color:var(--text-muted); font-size:.85rem; }
        .prob-title a { color:var(--text); font-weight:500; font-size:.95rem; }
        .prob-title a:hover { color:var(--accent); }
        .prob-tags { display:flex; gap:6px; flex-wrap:wrap; margin-top:4px; }
        .tag { font-size:.72rem; padding:2px 8px; border-radius:100px;
               background:var(--bg-card2); color:var(--text-muted); border:1px solid var(--border); }
        .prob-acc { font-size:.85rem; color:var(--text-muted); }
        .acc-pct  { display:block; font-size:.75rem; color:var(--text-muted); margin-top:2px; }
        .status-dot { width:10px; height:10px; border-radius:50%; margin:0 auto; }
        .dot-solved   { background:var(--accent); }
        .dot-attempted{ background:var(--yellow); }
        .dot-unsolved { background:var(--border); }
        .saved-toggle { display:flex; align-items:center; gap:6px; white-space:nowrap; font-size:.86rem;
                        color:var(--text-dim); cursor:pointer; user-select:none; }
        .saved-toggle input { accent-color:var(--accent); }
        .bookmark-mark { color:var(--yellow); font-size:.9rem; margin-left:6px; }
        .pagination { display:flex; gap:8px; justify-content:center; margin-top:24px; }
        .page-btn { padding:7px 14px; border-radius:var(--radius-sm); border:1px solid var(--border);
                    background:var(--bg-card); color:var(--text-dim); font-size:.85rem; cursor:pointer;
                    transition:border-color .2s,color .2s; }
        .page-btn:hover, .page-btn.active { border-color:var(--accent); color:var(--accent); }
        .page-btn:disabled { opacity:.4; cursor:default; }
    </style>
</head>
<body>
<?php require_once 'includes/navbar.php'; ?>

<div class="page">
<div class="container">
    <div class="page-header fade-up">
        <h1>Problems</h1>
        <p>Browse, filter and solve algorithmic challenges.</p>
    </div>

    <!-- Filters -->
    <div class="filters fade-up fade-up-1">
        <input type="text" id="search" class="form-input filter-input"
               placeholder="Search by title or tag…" oninput="debounce(loadProblems,350)()">
        <select id="difficulty" class="filter-select" onchange="loadProblems()">
            <option value="">All Difficulties</option>
            <option>Easy</option>
            <option>Medium</option>
            <option>Hard</option>
        </select>
        <select id="company" class="filter-select" onchange="loadProblems()">
            <option value="">All Companies</option>
            <option value="google">Google</option>
            <option value="meta">Meta</option>
            <option value="amazon">Amazon</option>
            <option value="microsoft">Microsoft</option>
            <option value="apple">Apple</option>
            <option value="netflix">Netflix</option>
        </select>
        <select id="sort" class="filter-select" onchange="loadProblems()">
            <option value="id">Default</option>
            <option value="difficulty">Difficulty</option>
            <option value="title">Title A-Z</option>
            <option value="total_accepted">Most Solved</option>
        </select>
        <?php if (isLoggedIn()): ?>
        <label class="saved-toggle">
            <input type="checkbox" id="saved-only" onchange="loadProblems()">
            Saved only
        </label>
        <?php endif; ?>
        <?php if (isInstructor()): ?>
        <a href="/code-arena/instructor.php" class="btn-outline" style="white-space:nowrap">+ New Problem</a>
        <?php endif; ?>
    </div>

    <!-- Table -->
    <div class="card" style="padding:0; overflow:hidden" id="problems-wrap">
        <div class="prob-row header">
            <div>#</div>
            <div>Title</div>
            <div>Difficulty</div>
            <div>Accepted</div>
            <div>Rate</div>
            <div>Status</div>
        </div>
        <div id="problems-body">
            <div class="empty-state"><p>Loading…</p></div>
        </div>
    </div>

    <div class="pagination" id="pagination"></div>
</div>
</div>

<script src="/code-arena/assets/js/main.js"></script>
<script>
let currentPage = 1;

async function loadProblems(page = 1) {
    currentPage = page;
    const search     = document.getElementById('search').value.trim();
    const difficulty = document.getElementById('difficulty').value;
    const company    = document.getElementById('company').value;
    const sort       = document.getElementById('sort').value;
    const savedOnly  = document.getElementById('saved-only')?.checked;

    const params = new URLSearchParams({ page, sort });
    if (search)     params.set('search', search);
    if (difficulty) params.set('difficulty', difficulty);
    if (company)    params.set('company', company);
    if (savedOnly)  params.set('saved', '1');

    const { ok, data } = await api(`/code-arena/api/problems/index.php?${params}`);
    const body = document.getElementById('problems-body');

    if (!ok || !data.success) {
        body.innerHTML = `<div class="empty-state"><p>Failed to load problems.</p></div>`;
        return;
    }

    const problems = data.data.problems;
    if (!problems.length) {
        body.innerHTML = `<div class="empty-state"><p>No problems found.</p></div>`;
        renderPagination(data.data);
        return;
    }

    body.innerHTML = problems.map((p, i) => {
        const acc = p.total_submissions > 0
            ? Math.round(p.total_accepted / p.total_submissions * 100) : 0;
        const dotCls = { solved:'dot-solved', attempted:'dot-attempted', unsolved:'dot-unsolved' }[p.user_status] || 'dot-unsolved';
        const tags = p.tags ? p.tags.split(',').map(t =>
            `<span class="tag">${t.trim()}</span>`).join('') : '';
        const num = (page - 1) * 20 + i + 1;
        return `
        <div class="prob-row">
            <div class="prob-num">${num}</div>
            <div class="prob-title">
                <a href="/code-arena/problem.php?slug=${p.slug}">${p.title}</a>
                ${p.bookmarked ? '<span class="bookmark-mark" title="Saved">★</span>' : ''}
                <div class="prob-tags">${tags}</div>
            </div>
            <div>${difficultyBadge(p.difficulty)}</div>
            <div class="prob-acc">
                ${p.total_accepted.toLocaleString()}
                <span class="acc-pct">${acc}%</span>
            </div>
            <div><span class="text-muted text-sm">${acc}%</span></div>
            <div><div class="status-dot ${dotCls}" title="${p.user_status}"></div></div>
        </div>`;
    }).join('');

    renderPagination(data.data);
}

function renderPagination({ page, pages }) {
    const el = document.getElementById('pagination');
    if (pages <= 1) { el.innerHTML = ''; return; }
    let html = '';
    if (page > 1) html += `<button class="page-btn" onclick="loadProblems(${page-1})">← Prev</button>`;
    for (let p = Math.max(1, page-2); p <= Math.min(pages, page+2); p++) {
        html += `<button class="page-btn ${p===page?'active':''}" onclick="loadProblems(${p})">${p}</button>`;
    }
    if (page < pages) html += `<button class="page-btn" onclick="loadProblems(${page+1})">Next →</button>`;
    el.innerHTML = html;
}

function debounce(fn, delay) {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), delay); };
}

const initialParams = new URLSearchParams(window.location.search);
if (initialParams.get('saved') === '1' && document.getElementById('saved-only')) {
    document.getElementById('saved-only').checked = true;
}

loadProblems();
</script>
</body>
</html>
