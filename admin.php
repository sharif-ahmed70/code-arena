<?php
// ============================================================
//  CODE ARENA — Admin Dashboard
// ============================================================
require_once 'includes/session.php';
requireAdmin();
require_once 'config/db.php';

$totalUsers    = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalProblems = (int) $pdo->query('SELECT COUNT(*) FROM problems')->fetchColumn();
$totalSubs     = (int) $pdo->query('SELECT COUNT(*) FROM submissions')->fetchColumn();
$totalContests = (int) $pdo->query('SELECT COUNT(*) FROM contests')->fetchColumn();
$currentAdminId = currentUserId();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Code Arena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css">
    <style>
        .tabs { display:flex; gap:4px; margin-bottom:28px; border-bottom:1px solid var(--border); padding-bottom:0; }
        .tab-btn { padding:10px 20px; background:none; border:none; border-bottom:2px solid transparent;
                   color:var(--text-muted); font-family:inherit; font-size:.9rem; font-weight:500;
                   cursor:pointer; margin-bottom:-1px; transition:color .2s,border-color .2s; }
        .tab-btn.active { color:var(--accent); border-bottom-color:var(--accent); }
        .tab-pane { display:none; }
        .tab-pane.active { display:block; }

        /* Users table */
        .role-select { padding:4px 8px; background:var(--bg-card2); border:1px solid var(--border);
                       border-radius:4px; color:var(--text); font-size:.82rem; cursor:pointer; }

        /* Problem form */
        .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        @media(max-width:700px) { .form-grid { grid-template-columns:1fr; } }
        .form-textarea { min-height:80px; }
        .tc-list { display:flex; flex-direction:column; gap:8px; margin-bottom:12px; }
        .tc-item { display:grid; grid-template-columns:1fr 1fr auto; gap:8px; align-items:start; }
        .tc-item textarea { font-family:'JetBrains Mono',monospace; font-size:.82rem; resize:vertical; min-height:52px; }
        .btn-sm { padding:6px 12px; font-size:.82rem; }

        .search-row { display:flex; gap:10px; margin-bottom:16px; }
        .search-row input { flex:1; }
    </style>
</head>
<body>
<?php require_once 'includes/navbar.php'; ?>

<div class="page">
<div class="container">
    <div class="page-header fade-up">
        <h1>Admin Panel</h1>
        <p>Manage users, problems, and platform settings.</p>
    </div>

    <div class="stats-row fade-up fade-up-1">
        <div class="stat-card"><div class="stat-value"><?= $totalUsers ?></div><div class="stat-label">Users</div></div>
        <div class="stat-card"><div class="stat-value"><?= $totalProblems ?></div><div class="stat-label">Problems</div></div>
        <div class="stat-card"><div class="stat-value"><?= $totalSubs ?></div><div class="stat-label">Submissions</div></div>
        <div class="stat-card"><div class="stat-value"><?= $totalContests ?></div><div class="stat-label">Contests</div></div>
    </div>

    <div class="tabs fade-up fade-up-2">
        <button class="tab-btn active" onclick="switchTab('users',this)">Users</button>
        <button class="tab-btn" onclick="switchTab('problems',this)">Problems</button>
        <button class="tab-btn" onclick="switchTab('submissions',this)">Submissions</button>
    </div>

    <!-- USERS TAB -->
    <div class="tab-pane active" id="tab-users">
        <div class="search-row">
            <input type="text" id="user-search" class="form-input" placeholder="Search users…"
                   oninput="debounce(loadUsers,350)()">
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr>
                    <th>ID</th><th>Username</th><th>Email</th><th>Role</th>
                    <th>HC Rating</th><th>LR Rating</th><th>Joined</th><th>Action</th>
                </tr></thead>
                <tbody id="users-body">
                    <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted)">Loading…</td></tr>
                </tbody>
            </table>
        </div>
        <div class="pagination" id="users-pagination"></div>
    </div>

    <!-- PROBLEMS TAB -->
    <div class="tab-pane" id="tab-problems">
        <button class="btn-primary" style="margin-bottom:20px" onclick="showProblemForm()">+ New Problem</button>
        <div class="table-wrap" id="problems-table-wrap">
            <table>
                <thead><tr>
                    <th>ID</th><th>Title</th><th>Difficulty</th><th>Submissions</th><th>Public</th><th>Actions</th>
                </tr></thead>
                <tbody id="problems-body">
                    <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted)">Loading…</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Problem Form -->
        <div id="problem-form-wrap" style="display:none;margin-top:24px">
            <div class="card">
                <h3 id="form-title" style="margin-bottom:20px">New Problem</h3>
                <input type="hidden" id="problem-id" value="">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Title *</label>
                        <input type="text" id="p-title" class="form-input" placeholder="Two Sum">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Difficulty *</label>
                        <select id="p-difficulty" class="form-input">
                            <option>Easy</option><option>Medium</option><option>Hard</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description *</label>
                    <textarea id="p-desc" class="form-input form-textarea" rows="5" placeholder="Problem statement…"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Examples (JSON array or plain text)</label>
                    <textarea id="p-examples" class="form-input" rows="3"
                        placeholder='[{"input":"1 2","output":"3","explanation":"1+2=3"}]'></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Constraints</label>
                    <textarea id="p-constraints" class="form-input" rows="2" placeholder="1 ≤ n ≤ 10^5"></textarea>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Tags (comma-separated)</label>
                        <input type="text" id="p-tags" class="form-input" placeholder="array,hash-table">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Roadmap Day (leave blank if not on roadmap)</label>
                        <input type="number" id="p-roadmap-day" class="form-input" placeholder="1-30" min="1" max="30">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Hint Tier 1</label>
                    <textarea id="p-hint1" class="form-input" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Hint Tier 2</label>
                    <textarea id="p-hint2" class="form-input" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Hint Tier 3</label>
                    <textarea id="p-hint3" class="form-input" rows="2"></textarea>
                </div>

                <!-- Test Cases -->
                <div class="form-group">
                    <label class="form-label" style="margin-bottom:10px">Test Cases</label>
                    <div class="tc-list" id="tc-list"></div>
                    <button class="btn-outline btn-sm" onclick="addTestCase()">+ Add Test Case</button>
                </div>

                <div style="display:flex; gap:12px; align-items:center; margin-top:8px">
                    <button class="btn-primary" onclick="saveProblem()">Save Problem</button>
                    <button class="btn-outline" onclick="hideProblemForm()">Cancel</button>
                    <div id="form-msg" style="font-size:.88rem"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- SUBMISSIONS TAB -->
    <div class="tab-pane" id="tab-submissions">
        <div class="table-wrap">
            <table>
                <thead><tr>
                    <th>#</th><th>User</th><th>Problem</th><th>Status</th><th>Language</th><th>When</th>
                </tr></thead>
                <tbody id="admin-subs-body">
                    <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted)">Loading…</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
</div>

<script src="/code-arena/assets/js/main.js"></script>
<script>
const CURRENT_ADMIN_ID = <?= json_encode($currentAdminId) ?>;

// ── Tab switching ─────────────────────────────────────────────
function switchTab(name, btn) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(`tab-${name}`).classList.add('active');
    btn.classList.add('active');
    if (name === 'users')       loadUsers();
    if (name === 'problems')    loadAdminProblems();
    if (name === 'submissions') loadAdminSubs();
}

function debounce(fn, d) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), d); }; }

// ── Users ─────────────────────────────────────────────────────
let userPage = 1;
async function loadUsers(page = 1) {
    userPage = page;
    const search = document.getElementById('user-search').value.trim();
    const params = new URLSearchParams({ page });
    if (search) params.set('search', search);

    const { ok, data } = await api(`/code-arena/api/admin/users.php?${params}`);
    const tbody = document.getElementById('users-body');
    if (!ok || !data.success) { tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;color:var(--red);padding:40px">${data.message}</td></tr>`; return; }

    const users = data.data.users;
    const adminCount = data.data.totalAdmins;
    tbody.innerHTML = users.map(u => {
        const isSelf       = u.id === CURRENT_ADMIN_ID;
        const isLastAdmin  = u.role === 'admin' && adminCount === 1;
        const disableRole  = isSelf || isLastAdmin;
        const disableDel   = isSelf || isLastAdmin;
        const roleTitle    = isSelf ? 'You cannot modify your own account.'
                           : isLastAdmin ? 'Cannot demote the last admin account.' : '';
        const delTitle     = isSelf ? 'You cannot delete your own account.'
                           : isLastAdmin ? 'Cannot delete the last admin account.' : '';
        return `
        <tr>
            <td>${u.id}</td>
            <td><a href="/code-arena/profile.php?user=${u.username}">${u.username}</a></td>
            <td style="font-size:.82rem;color:var(--text-muted)">${u.email}</td>
            <td>
                <select class="role-select" onchange="updateRole(${u.id},this.value)"
                    ${disableRole ? `disabled title="${roleTitle}"` : ''}>
                    ${['student','instructor','admin'].map(r =>
                        `<option ${u.role===r?'selected':''}>${r}</option>`).join('')}
                </select>
                ${disableRole ? `<span style="font-size:.75rem;color:var(--text-muted);display:block;margin-top:2px">${roleTitle}</span>` : ''}
            </td>
            <td style="color:var(--red)">${u.hardcore_rating}</td>
            <td style="color:var(--blue)">${u.learning_rating}</td>
            <td style="font-size:.8rem;color:var(--text-muted)">${timeAgo(u.created_at)}</td>
            <td>
                <button class="btn-danger btn-sm" onclick="deleteUser(${u.id},'${u.username}')"
                    ${disableDel ? `disabled title="${delTitle}"` : ''}
                    ${disableDel ? 'style="opacity:.45;cursor:not-allowed"' : ''}>Delete</button>
            </td>
        </tr>`;
    }).join('');
}

async function updateRole(id, role) {
    const { ok, data } = await api('/code-arena/api/admin/users.php', {
        method: 'PUT', body: JSON.stringify({ id, role }),
    });
    toast(data.message || (ok ? 'Updated' : 'Failed'), ok ? 'success' : 'error');
}

async function deleteUser(id, username) {
    if (!confirm(`Delete user "${username}"? This cannot be undone.`)) return;
    const { ok, data } = await api('/code-arena/api/admin/users.php', {
        method: 'DELETE', body: JSON.stringify({ id }),
    });
    toast(data.message, ok ? 'success' : 'error');
    if (ok) loadUsers(userPage);
}

// ── Problems ──────────────────────────────────────────────────
async function loadAdminProblems() {
    const { ok, data } = await api('/code-arena/api/instructor/problems.php');
    const tbody = document.getElementById('problems-body');
    if (!ok || !data.success) { tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:var(--red);padding:40px">${data.message}</td></tr>`; return; }

    const problems = data.data;
    if (!problems.length) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:40px">No problems yet.</td></tr>`;
        return;
    }
    tbody.innerHTML = problems.map(p => `
        <tr>
            <td>${p.id}</td>
            <td><a href="/code-arena/problem.php?slug=${p.slug}" target="_blank">${p.title}</a></td>
            <td>${difficultyBadge(p.difficulty)}</td>
            <td>${p.total_submissions}</td>
            <td>${p.is_public ? '<span style="color:var(--accent)">Yes</span>' : '<span style="color:var(--text-muted)">No</span>'}</td>
            <td>
                <button class="btn-outline btn-sm" onclick="editProblem(${p.id})">Edit</button>
                <a class="btn-outline btn-sm" href="/code-arena/editorial_manage.php?problem_id=${p.id}">Editorial</a>
                <button class="btn-danger btn-sm" onclick="deleteProblem(${p.id},'${p.title.replace(/'/g,'')}')">Del</button>
            </td>
        </tr>`).join('');
}

let testCaseCount = 0;
function showProblemForm(reset = true) {
    if (reset) {
        document.getElementById('problem-id').value = '';
        document.getElementById('form-title').textContent = 'New Problem';
        ['p-title','p-tags','p-roadmap-day','p-hint1','p-hint2','p-hint3','p-examples','p-constraints','p-desc'].forEach(id => {
            document.getElementById(id).value = '';
        });
        document.getElementById('p-difficulty').value = 'Easy';
        document.getElementById('tc-list').innerHTML = '';
        testCaseCount = 0;
        addTestCase();
    }
    document.getElementById('problem-form-wrap').style.display = 'block';
    document.getElementById('problem-form-wrap').scrollIntoView({ behavior:'smooth' });
}

function hideProblemForm() {
    document.getElementById('problem-form-wrap').style.display = 'none';
}

function addTestCase(inputVal = '', outputVal = '') {
    testCaseCount++;
    const div = document.createElement('div');
    div.className = 'tc-item';
    div.id = `tc-${testCaseCount}`;
    div.innerHTML = `
        <div>
            <label class="form-label" style="margin-bottom:4px">Input ${testCaseCount}</label>
            <textarea class="form-input tc-input" placeholder="stdin input"></textarea>
        </div>
        <div>
            <label class="form-label" style="margin-bottom:4px">Expected Output ${testCaseCount}</label>
            <textarea class="form-input tc-output" placeholder="expected stdout"></textarea>
        </div>
        <button class="btn-danger btn-sm" style="margin-top:22px" onclick="this.closest('.tc-item').remove()">×</button>`;
    document.getElementById('tc-list').appendChild(div);
    // Set values after appending so the textareas exist in the DOM
    div.querySelector('.tc-input').value  = inputVal;
    div.querySelector('.tc-output').value = outputVal;
}

async function editProblem(id) {
    const { ok, data } = await api(`/code-arena/api/instructor/problems.php?id=${id}`);
    if (!ok || !data.success) { toast(data.message || 'Failed to load problem', 'error'); return; }

    const p = data.data;

    // Pre-fill all text fields
    document.getElementById('problem-id').value        = p.id;
    document.getElementById('form-title').textContent  = `Edit Problem #${p.id}`;
    document.getElementById('p-title').value           = p.title        || '';
    document.getElementById('p-difficulty').value      = p.difficulty   || 'Easy';
    document.getElementById('p-desc').value            = p.description  || '';
    document.getElementById('p-examples').value        = p.examples     || '';
    document.getElementById('p-constraints').value     = p.constraints  || '';
    document.getElementById('p-tags').value            = p.tags         || '';
    document.getElementById('p-roadmap-day').value     = p.roadmap_day  || '';
    document.getElementById('p-hint1').value           = p.hint_tier1   || '';
    document.getElementById('p-hint2').value           = p.hint_tier2   || '';
    document.getElementById('p-hint3').value           = p.hint_tier3   || '';

    // Re-populate test cases
    document.getElementById('tc-list').innerHTML = '';
    testCaseCount = 0;
    let tcs = [];
    try { tcs = JSON.parse(p.test_cases || '[]'); } catch(e) {}
    if (tcs.length) {
        tcs.forEach(tc => addTestCase(tc.input ?? '', tc.expected_output ?? ''));
    } else {
        addTestCase();
    }

    showProblemForm(false);
    toast(`Loaded "${p.title}" for editing`, 'info');
}

async function saveProblem() {
    const pid = document.getElementById('problem-id').value;

    // Collect test cases
    const testCases = [];
    document.querySelectorAll('#tc-list .tc-item').forEach(item => {
        const inp = item.querySelector('.tc-input').value.trim();
        const out = item.querySelector('.tc-output').value.trim();
        if (inp !== '' || out !== '') testCases.push({ input: inp, expected_output: out });
    });

    const payload = {
        title:        document.getElementById('p-title').value.trim(),
        difficulty:   document.getElementById('p-difficulty').value,
        description:  document.getElementById('p-desc').value.trim(),
        examples:     document.getElementById('p-examples').value.trim(),
        constraints:  document.getElementById('p-constraints').value.trim(),
        tags:         document.getElementById('p-tags').value.trim(),
        roadmap_day:  document.getElementById('p-roadmap-day').value || null,
        hint_tier1:   document.getElementById('p-hint1').value.trim() || null,
        hint_tier2:   document.getElementById('p-hint2').value.trim() || null,
        hint_tier3:   document.getElementById('p-hint3').value.trim() || null,
        test_cases:   testCases,
        is_public:    1,
    };

    if (pid) payload.id = parseInt(pid);

    const msg = document.getElementById('form-msg');
    msg.textContent = 'Saving…';
    msg.style.color = 'var(--text-muted)';

    const { ok, data } = await api('/code-arena/api/instructor/problems.php', {
        method: pid ? 'PUT' : 'POST',
        body: JSON.stringify(payload),
    });

    if (ok && data.success) {
        msg.textContent = data.message;
        msg.style.color = 'var(--accent)';
        toast(data.message, 'success');
        loadAdminProblems();
        setTimeout(() => { hideProblemForm(); msg.textContent=''; }, 1500);
    } else {
        msg.textContent = data.message || 'Failed';
        msg.style.color = 'var(--red)';
        toast(data.message || 'Failed', 'error');
    }
}

async function deleteProblem(id, title) {
    if (!confirm(`Delete problem "${title}"?`)) return;
    const { ok, data } = await api('/code-arena/api/instructor/problems.php', {
        method: 'DELETE', body: JSON.stringify({ id }),
    });
    toast(data.message, ok ? 'success' : 'error');
    if (ok) loadAdminProblems();
}

// ── Admin submissions ─────────────────────────────────────────
async function loadAdminSubs() {
    const { ok, data } = await api('/code-arena/api/submissions/verdict.php?all=1&page=1');
    const tbody = document.getElementById('admin-subs-body');
    if (!ok || !data.success) { tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:var(--red);padding:40px">${data.message}</td></tr>`; return; }

    const subs = data.data.submissions;
    if (!subs.length) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:40px">No submissions yet.</td></tr>`;
        return;
    }
    tbody.innerHTML = subs.map(s => `
        <tr>
            <td class="mono" style="font-size:.82rem">#${s.id}</td>
            <td><a href="/code-arena/profile.php?user=${s.username}">${s.username}</a></td>
            <td><a href="/code-arena/problem.php?slug=${s.problem_slug}">${s.problem_title}</a></td>
            <td>${statusBadge(s.status)}</td>
            <td style="font-size:.82rem">${langName(s.language)}</td>
            <td style="font-size:.82rem;color:var(--text-muted)">${timeAgo(s.submitted_at)}</td>
        </tr>`).join('');
}

// Init
loadUsers();
</script>
</body>
</html>
