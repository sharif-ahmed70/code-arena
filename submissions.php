<?php
// ============================================================
//  CODE ARENA — Submissions Page
// ============================================================
require_once 'includes/session.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submissions — Code Arena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css?v=20260615-ui5">
    <style>
        .filters { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:24px; align-items:center; }
        .filter-select { padding:9px 14px; background:var(--bg-card); border:1px solid var(--border);
                         border-radius:var(--radius-sm); color:var(--text); font-size:.88rem; cursor:pointer;
                         outline:none; }
        .filter-select:focus { border-color:var(--accent); }
        .pagination { display:flex; gap:8px; justify-content:center; margin-top:24px; }
        .page-btn { padding:7px 14px; border-radius:var(--radius-sm); border:1px solid var(--border);
                    background:var(--bg-card); color:var(--text-dim); font-size:.85rem; cursor:pointer;
                    transition:border-color .2s,color .2s; }
        .page-btn:hover,.page-btn.active { border-color:var(--accent); color:var(--accent); }
        .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.7); z-index:200;
                         display:flex; align-items:center; justify-content:center; padding:24px; }
        .modal { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius);
                 max-width:860px; width:100%; max-height:85vh; overflow-y:auto; }
        .modal-header { display:flex; align-items:center; justify-content:space-between;
                        padding:18px 24px; border-bottom:1px solid var(--border); position:sticky; top:0;
                        background:var(--bg-card); z-index:2; }
        .modal-body { padding:24px; }
        .close-btn { background:none; border:none; color:var(--text-muted); font-size:1.3rem;
                     cursor:pointer; line-height:1; }
        .close-btn:hover { color:var(--red); }
        .code-block { background:var(--bg); border:1px solid var(--border); border-radius:var(--radius-sm);
                      padding:16px; font-family:'JetBrains Mono',monospace; font-size:.82rem;
                      color:var(--text-dim); overflow-x:auto; white-space:pre; }
        .test-case { background:var(--bg-card2); border:1px solid var(--border);
                     border-radius:var(--radius-sm); padding:12px; margin-bottom:8px; }
        .test-case.pass { border-color:rgba(0,255,136,.25); }
        .test-case.fail { border-color:rgba(255,79,79,.25); }
        .test-io { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; margin-top:8px; }
        .test-io-cell { background:var(--bg); padding:8px; border-radius:4px; }
        .test-io-cell .lbl { font-size:.72rem; color:var(--text-muted); margin-bottom:3px; }
        .test-io-cell pre { font-family:'JetBrains Mono',monospace; font-size:.78rem; color:var(--text-dim);
                            white-space:pre-wrap; word-break:break-all; }
    </style>
</head>
<body>
<?php require_once 'includes/navbar.php'; ?>

<div class="page">
<div class="container">
    <div class="page-header fade-up">
        <h1>Submissions</h1>
        <p>Your submission history.</p>
    </div>

    <div class="filters fade-up fade-up-1">
        <select id="filter-status" class="filter-select" onchange="loadSubmissions()">
            <option value="">All Statuses</option>
            <option>Accepted</option>
            <option value="Wrong Answer">Wrong Answer</option>
            <option value="Runtime Error">Runtime Error</option>
            <option value="Time Limit Exceeded">TLE</option>
        </select>
        <select id="filter-lang" class="filter-select" onchange="loadSubmissions()">
            <option value="">All Languages</option>
            <option value="javascript">JavaScript</option>
            <option value="python">Python</option>
            <option value="cpp">C++</option>
            <option value="java">Java</option>
            <option value="go">Go</option>
            <option value="rust">Rust</option>
        </select>
        <?php if (isAdmin()): ?>
        <label style="font-size:.88rem;color:var(--text-muted);display:flex;align-items:center;gap:6px;">
            <input type="checkbox" id="show-all" onchange="loadSubmissions()"> Show all users
        </label>
        <?php endif; ?>
    </div>

    <div class="table-wrap fade-up fade-up-2">
        <table>
            <thead><tr>
                <th>#</th><th>Problem</th><th>Status</th>
                <th>Language</th><th>Runtime</th><th>Hints</th><th>When</th><th></th>
            </tr></thead>
            <tbody id="submissions-body">
                <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:40px">Loading…</td></tr>
            </tbody>
        </table>
    </div>

    <div class="pagination" id="pagination"></div>
</div>
</div>

<div id="modal" style="display:none" class="modal-overlay" onclick="if(event.target===this)closeModal()">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modal-title">Submission</h3>
            <button class="close-btn" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body" id="modal-body"></div>
    </div>
</div>

<script src="/code-arena/assets/js/main.js?v=20260615-ui5"></script>
<script>
let currentPage = 1;

async function loadSubmissions(page = 1) {
    currentPage = page;
    const status = document.getElementById('filter-status').value;
    const lang   = document.getElementById('filter-lang').value;
    const allCb  = document.getElementById('show-all');
    const all    = allCb && allCb.checked;

    const params = new URLSearchParams({ page });
    if (status) params.set('status', status);
    if (lang)   params.set('language', lang);
    if (all)    params.set('all', '1');

    const { ok, data } = await api(`/code-arena/api/submissions/verdict.php?${params}`);
    const tbody = document.getElementById('submissions-body');

    if (!ok || !data.success) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;color:var(--red);padding:40px">${data.message||'Failed'}</td></tr>`;
        return;
    }

    const subs = data.data.submissions;
    if (!subs.length) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:40px">No submissions found.</td></tr>`;
        renderPagination(data.data);
        return;
    }

    tbody.innerHTML = subs.map(s => `
        <tr style="cursor:pointer" onclick="viewSubmission(${s.id})">
            <td class="mono" style="font-size:.82rem">#${s.id}</td>
            <td>
                <a href="/code-arena/problem.php?slug=${s.problem_slug}" onclick="event.stopPropagation()">${s.problem_title}</a>
                ${s.username ? `<br><small style="color:var(--text-muted)">${s.username}</small>` : ''}
            </td>
            <td>${statusBadge(s.status)}</td>
            <td class="mono" style="font-size:.82rem">${langName(s.language)}</td>
            <td style="font-size:.82rem;color:var(--text-muted)">${s.runtime_ms ? s.runtime_ms+'ms' : '—'}</td>
            <td style="font-size:.82rem;color:var(--text-muted)">${s.hints_used||0}</td>
            <td style="font-size:.82rem;color:var(--text-muted)">${timeAgo(s.submitted_at)}</td>
            <td>${s.is_practice ? '<span class="badge" style="background:rgba(108,160,255,.12);color:var(--blue);font-size:.68rem">Practice</span>' : ''}</td>
        </tr>`).join('');

    renderPagination(data.data);
}

function renderPagination({ page, pages }) {
    const el = document.getElementById('pagination');
    if (pages <= 1) { el.innerHTML = ''; return; }
    let html = '';
    if (page > 1) html += `<button class="page-btn" onclick="loadSubmissions(${page-1})">← Prev</button>`;
    for (let p = Math.max(1,page-2); p <= Math.min(pages,page+2); p++) {
        html += `<button class="page-btn ${p===page?'active':''}" onclick="loadSubmissions(${p})">${p}</button>`;
    }
    if (page < pages) html += `<button class="page-btn" onclick="loadSubmissions(${page+1})">Next →</button>`;
    el.innerHTML = html;
}

async function viewSubmission(id) {
    const { ok, data } = await api(`/code-arena/api/submissions/verdict.php?id=${id}`);
    if (!ok || !data.success) { toast(data.message||'Error', 'error'); return; }

    const s = data.data;
    document.getElementById('modal-title').textContent = `#${s.id} — ${s.problem_title}`;

    const tests = s.test_results || [];
    let testHtml = '';
    if (tests.length) {
        testHtml = `<div style="margin-top:20px">
            <div style="font-size:.8rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;margin-bottom:10px">Test Cases</div>`
            + tests.map(tc => `
            <div class="test-case ${tc.passed?'pass':'fail'}">
                <div style="display:flex;align-items:center;gap:8px;font-size:.85rem;font-weight:600">
                    <span>${tc.passed?'✓':'✗'}</span> Test ${tc.test}
                    ${tc.error?`<span style="color:var(--red);font-size:.78rem">${tc.error}</span>`:''}
                </div>
                ${!tc.passed ? `
                <div class="test-io">
                    <div class="test-io-cell"><div class="lbl">Input</div><pre>${escHtml(tc.input)}</pre></div>
                    <div class="test-io-cell"><div class="lbl">Expected</div><pre>${escHtml(tc.expected)}</pre></div>
                    <div class="test-io-cell"><div class="lbl">Got</div><pre>${escHtml(tc.got)}</pre></div>
                </div>` : ''}
            </div>`).join('') + '</div>';
    }

    document.getElementById('modal-body').innerHTML = `
        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:16px">
            ${statusBadge(s.status)}
            ${s.is_practice ? '<span class="badge" style="background:rgba(108,160,255,.12);color:var(--blue)">Practice</span>' : ''}
            <span class="badge" style="background:var(--bg-card2);color:var(--text-muted)">${langName(s.language)}</span>
            ${s.runtime_ms ? `<span class="badge" style="background:var(--bg-card2);color:var(--text-muted)">${s.runtime_ms}ms</span>` : ''}
            <span class="badge" style="background:var(--bg-card2);color:var(--text-muted)">${s.hints_used||0} hints</span>
        </div>
        <div style="font-size:.8rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;margin-bottom:6px">Code</div>
        <div class="code-block">${escHtml(s.code)}</div>
        ${testHtml}`;

    document.getElementById('modal').style.display = 'flex';
}

function closeModal() { document.getElementById('modal').style.display = 'none'; }

function escHtml(s) {
    return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

loadSubmissions();
</script>
</body>
</html>
