<?php
// ============================================================
//  CODE ARENA - System Admin Control Hub
// ============================================================
require_once 'includes/session.php';
require_once 'includes/adminAuthMiddleware.php';
requireAdminPage();

$currentAdminId = currentUserId();
$currentAdmin = currentUsername() ?? 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Admin - Code Arena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css?v=20260615-ui2">
    <style>
        body { background:var(--bg); }
        .admin-shell { min-height:100vh; display:grid; grid-template-columns:270px minmax(0,1fr); }
        .admin-sidebar {
            position:sticky; top:0; height:100vh; padding:22px 16px; border-right:1px solid var(--border);
            background:linear-gradient(180deg,var(--bg-card),rgba(255,255,255,.018));
            display:flex; flex-direction:column; gap:20px;
        }
        .admin-brand { padding:0 8px 16px; border-bottom:1px solid var(--border); }
        .admin-brand strong { display:block; font-size:1.08rem; color:var(--text); }
        .admin-brand span { display:block; color:var(--text-muted); font-size:.78rem; margin-top:4px; }
        .admin-nav { display:grid; gap:5px; }
        .admin-nav button {
            border:0; width:100%; text-align:left; display:flex; align-items:center; gap:10px;
            padding:10px 12px; border-radius:var(--radius-sm); background:transparent;
            color:var(--text-muted); font:inherit; font-size:.9rem; cursor:pointer;
            transition:background .18s ease,color .18s ease;
        }
        .admin-nav button:hover, .admin-nav button.active { background:rgba(0,232,122,.08); color:var(--accent); }
        .admin-main { min-width:0; }
        .admin-topbar {
            position:sticky; top:0; z-index:10; min-height:68px; display:flex; align-items:center; gap:14px;
            padding:14px 24px; border-bottom:1px solid var(--border); background:rgba(10,10,15,.94);
        }
        .admin-title { font-size:1.05rem; font-weight:800; white-space:nowrap; }
        .admin-search {
            flex:1; max-width:560px; padding:10px 12px; border-radius:var(--radius-sm);
            border:1px solid var(--border); background:var(--bg-card2); color:var(--text);
        }
        .top-chip {
            min-width:38px; height:38px; display:inline-flex; align-items:center; justify-content:center;
            border:1px solid var(--border); border-radius:var(--radius-sm); background:var(--bg-card2);
            color:var(--text-muted); padding:0 12px;
        }
        .admin-content { padding:24px; }
        .admin-section { display:none; }
        .admin-section.active { display:block; }
        .section-head { display:flex; justify-content:space-between; align-items:flex-end; gap:18px; margin-bottom:20px; }
        .section-head h1 { margin:0; font-size:1.6rem; }
        .section-head p { color:var(--text-muted); margin-top:6px; }
        .metric-grid { display:grid; grid-template-columns:repeat(6,minmax(130px,1fr)); gap:14px; margin-bottom:20px; }
        .metric-card, .panel-card {
            border:1px solid var(--border); border-radius:var(--radius); background:var(--bg-card); padding:18px;
        }
        .metric-card strong { display:block; font-size:1.7rem; line-height:1; }
        .metric-card span { display:block; margin-top:8px; color:var(--text-muted); font-size:.74rem; text-transform:uppercase; letter-spacing:.05em; }
        .panel-grid { display:grid; grid-template-columns:minmax(0,1.35fr) minmax(320px,.65fr); gap:18px; align-items:start; }
        .panel-title { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:14px; }
        .panel-title h2, .panel-title h3 { margin:0; font-size:1rem; }
        .toolbar { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:14px; }
        .toolbar input, .toolbar select { min-width:180px; }
        .table-wrap table a { color:var(--text); font-weight:650; }
        .table-wrap table a:hover { color:var(--accent); }
        .role-select, .mini-select {
            padding:6px 8px; background:var(--bg-card2); border:1px solid var(--border);
            border-radius:var(--radius-sm); color:var(--text); font-size:.82rem;
        }
        .activity-list, .stack-list { display:grid; gap:10px; }
        .activity-item, .list-item {
            padding:12px; border:1px solid var(--border); border-radius:var(--radius-sm);
            background:var(--bg-card2); color:var(--text-dim); font-size:.86rem;
        }
        .activity-item span, .list-item span { display:block; color:var(--text-muted); font-size:.76rem; margin-top:4px; }
        .chart-grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
        .bars { height:190px; display:grid; grid-template-columns:repeat(10,1fr); gap:8px; align-items:end; }
        .bar { min-height:6px; border-radius:8px 8px 3px 3px; background:linear-gradient(180deg,var(--accent),var(--blue)); }
        .line-chart { height:190px; position:relative; border:1px solid var(--border); border-radius:var(--radius-sm); background:rgba(255,255,255,.025); overflow:hidden; }
        .line-chart svg { width:100%; height:100%; display:block; }
        .analytics-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px; margin-bottom:18px; }
        .setting-row { display:grid; grid-template-columns:220px 1fr; gap:16px; padding:14px 0; border-bottom:1px solid var(--border); }
        .setting-row:last-child { border-bottom:0; }
        @media(max-width:1200px) {
            .metric-grid { grid-template-columns:repeat(3,1fr); }
            .panel-grid, .chart-grid { grid-template-columns:1fr; }
        }
        @media(max-width:760px) {
            .admin-shell { grid-template-columns:1fr; }
            .admin-sidebar { position:static; height:auto; }
            .admin-topbar { flex-wrap:wrap; }
            .admin-search { flex-basis:100%; max-width:none; order:3; }
            .metric-grid { grid-template-columns:1fr; }
            .setting-row { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="admin-brand">
            <strong>Code Arena Admin</strong>
            <span>System control center</span>
        </div>
        <nav class="admin-nav" aria-label="Admin navigation">
            <button class="active" data-section="overview" onclick="showSection('overview')">Dashboard Overview</button>
            <button data-section="users" onclick="showSection('users')">User Management</button>
            <button data-section="organizations" onclick="showSection('organizations')">Organization Management</button>
            <button data-section="contests" onclick="showSection('contests')">Contest Management</button>
            <button data-section="problems" onclick="showSection('problems')">Problem Management</button>
            <button data-section="analytics" onclick="showSection('analytics')">Analytics Dashboard</button>
            <button data-section="submissions" onclick="showSection('submissions')">Submissions Monitor</button>
            <button data-section="logs" onclick="showSection('logs')">System Logs / Activity</button>
            <button data-section="settings" onclick="showSection('settings')">System Settings</button>
        </nav>
    </aside>

    <main class="admin-main">
        <header class="admin-topbar">
            <div class="admin-title">System Admin</div>
            <input class="admin-search" id="global-search" placeholder="Quick search users, contests, organizations" onkeydown="globalSearch(event)">
            <a class="top-chip" href="#logs" onclick="showSection('logs')" title="Notifications">!</a>
            <?php require __DIR__ . '/includes/admin_control_dropdown.php'; ?>
            <a class="top-chip" href="/code-arena/profile.php" title="Admin profile"><?= htmlspecialchars(strtoupper(substr($currentAdmin, 0, 1))) ?></a>
            <a class="btn-outline" href="/code-arena/api/auth/logout.php">Logout</a>
        </header>

        <div class="admin-content">
            <section class="admin-section active" id="section-overview">
                <div class="section-head">
                    <div><h1>Dashboard Overview</h1><p>Central platform health, activity, and operational signals.</p></div>
                    <button class="btn-outline" onclick="loadOverview()">Refresh</button>
                </div>
                <div class="metric-grid" id="overview-metrics"></div>
                <div class="panel-grid">
                    <section class="panel-card">
                        <div class="panel-title"><h2>Recent Activity Logs</h2><a href="#logs" onclick="showSection('logs')">View all</a></div>
                        <div class="activity-list" id="overview-activity"><div class="activity-item">Loading...</div></div>
                    </section>
                    <aside class="panel-card">
                        <div class="panel-title"><h3>System Health Status</h3></div>
                        <div class="stack-list" id="health-list"></div>
                    </aside>
                </div>
            </section>

            <section class="admin-section" id="section-users">
                <div class="section-head"><div><h1>User Management</h1><p>View, ban/unban, and manage all platform accounts.</p></div></div>
                <div class="toolbar">
                    <input id="user-search" class="form-input" placeholder="Search users" oninput="debounce(loadUsers,350)()">
                </div>
                <div class="table-wrap"><table><thead><tr><th>ID</th><th>User</th><th>Email</th><th>Role</th><th>Status</th><th>Skill</th><th>Contest</th><th>Joined</th><th>Actions</th></tr></thead><tbody id="users-body"></tbody></table></div>
            </section>

            <section class="admin-section" id="section-organizations">
                <div class="section-head"><div><h1>Organization Management</h1><p>Monitor organization workspaces, owners, members, and hosted contests.</p></div></div>
                <div class="toolbar"><input id="org-search" class="form-input" placeholder="Search organizations" oninput="debounce(loadOrganizations,350)()"></div>
                <div class="table-wrap"><table><thead><tr><th>ID</th><th>Organization</th><th>Type</th><th>Owner</th><th>Members</th><th>Contests</th><th>Created</th></tr></thead><tbody id="orgs-body"></tbody></table></div>
            </section>

            <section class="admin-section" id="section-contests">
                <div class="section-head"><div><h1>Contest Management</h1><p>Monitor live contests, manage contest lifecycle, and inspect participation.</p></div></div>
                <div class="toolbar">
                    <input id="contest-search" class="form-input" placeholder="Search contests" oninput="debounce(loadContests,350)()">
                    <select id="contest-status" class="form-input" onchange="loadContests()"><option value="">All statuses</option><option value="active">Live</option><option value="upcoming">Upcoming</option><option value="ended">Ended</option></select>
                    <a class="btn-primary" href="/code-arena/contests.php">Create Contest</a>
                </div>
                <div class="table-wrap"><table><thead><tr><th>ID</th><th>Contest</th><th>Status</th><th>Creator</th><th>Participants</th><th>Submissions</th><th>Window</th><th>Actions</th></tr></thead><tbody id="contests-body"></tbody></table></div>
            </section>

            <section class="admin-section" id="section-problems">
                <div class="section-head"><div><h1>Problem Management</h1><p>Review problem inventory and open moderation tools.</p></div><a class="btn-primary" href="/code-arena/instructor.php">New Problem</a></div>
                <div class="table-wrap"><table><thead><tr><th>ID</th><th>Title</th><th>Difficulty</th><th>Submissions</th><th>Public</th><th>Actions</th></tr></thead><tbody id="problems-body"></tbody></table></div>
            </section>

            <section class="admin-section" id="section-analytics">
                <div class="section-head"><div><h1>Analytics Dashboard</h1><p>Usage trends, participation trends, and role distribution.</p></div></div>
                <div class="analytics-kpis" id="analytics-kpis"></div>
                <div class="chart-grid">
                    <section class="panel-card"><div class="panel-title"><h2>User Growth</h2></div><div class="line-chart" id="user-growth-chart"></div></section>
                    <section class="panel-card"><div class="panel-title"><h2>Submission Trend</h2></div><div class="line-chart" id="submission-chart"></div></section>
                    <section class="panel-card"><div class="panel-title"><h2>Contest Participation</h2></div><div class="bars" id="contest-chart"></div></section>
                    <section class="panel-card"><div class="panel-title"><h2>Acceptance by Difficulty</h2></div><div class="bars" id="difficulty-chart"></div></section>
                    <section class="panel-card"><div class="panel-title"><h2>DAU / WAU</h2></div><div class="line-chart" id="activity-chart"></div></section>
                    <section class="panel-card"><div class="panel-title"><h2>Average Rating Trend</h2></div><div class="line-chart" id="rating-chart"></div></section>
                    <section class="panel-card"><div class="panel-title"><h2>Role Breakdown</h2></div><div class="stack-list" id="role-breakdown"></div></section>
                </div>
            </section>

            <section class="admin-section" id="section-submissions">
                <div class="section-head"><div><h1>Submissions Monitor</h1><p>Filter submissions and spot abnormal activity patterns.</p></div></div>
                <div class="toolbar">
                    <input id="sub-user" class="form-input" placeholder="User/email" oninput="debounce(loadSubmissions,350)()">
                    <input id="sub-problem" class="form-input" placeholder="Problem" oninput="debounce(loadSubmissions,350)()">
                    <select id="sub-status" class="form-input" onchange="loadSubmissions()"><option value="">All statuses</option><option>Accepted</option><option>Wrong Answer</option><option>Runtime Error</option><option>Time Limit Exceeded</option><option>Compilation Error</option></select>
                </div>
                <div class="table-wrap"><table><thead><tr><th>#</th><th>User</th><th>Problem</th><th>Status</th><th>Language</th><th>Runtime</th><th>Contest</th><th>When</th></tr></thead><tbody id="subs-body"></tbody></table></div>
            </section>

            <section class="admin-section" id="section-logs">
                <div class="section-head"><div><h1>System Logs / Activity</h1><p>Admin actions, login events, contest activity, and audit trail.</p></div><button class="btn-outline" onclick="loadLogs()">Refresh</button></div>
                <div class="activity-list" id="logs-list"></div>
            </section>

            <section class="admin-section" id="section-settings">
                <div class="section-head"><div><h1>System Settings</h1><p>Operational status and platform configuration surface.</p></div></div>
                <section class="panel-card">
                    <div class="setting-row"><strong>Admin Isolation</strong><span>Admin panel uses dedicated middleware and admin-only APIs.</span></div>
                    <div class="setting-row"><strong>Contest Engine</strong><span>Read-only monitoring from this hub unless explicit admin lifecycle action is selected.</span></div>
                    <div class="setting-row"><strong>System Health</strong><span id="settings-health">Loading health status...</span></div>
                </section>
            </section>
        </div>
    </main>
</div>

<script src="/code-arena/assets/js/main.js"></script>
<script>
const CURRENT_ADMIN_ID = <?= json_encode($currentAdminId) ?>;
const loadedSections = new Set();

function showSection(name) {
    document.querySelectorAll('.admin-section').forEach(section => section.classList.remove('active'));
    document.querySelectorAll('.admin-nav button').forEach(btn => btn.classList.toggle('active', btn.dataset.section === name));
    document.getElementById(`section-${name}`).classList.add('active');
    if (!loadedSections.has(name)) {
        ({ overview: loadOverview, users: loadUsers, organizations: loadOrganizations, contests: loadContests,
           problems: loadProblems, analytics: loadAnalytics, submissions: loadSubmissions, logs: loadLogs,
           settings: loadOverview }[name] || (() => {}))();
        loadedSections.add(name);
    }
}

function debounce(fn, d) { let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), d); }; }
function safeText(value) { return escHtml(value ?? '-'); }
function globalSearch(event) {
    if (event.key !== 'Enter') return;
    const q = document.getElementById('global-search').value.trim();
    if (!q) return;
    document.getElementById('user-search').value = q;
    showSection('users');
    loadUsers();
}

async function loadOverview() {
    const { ok, data } = await api('/code-arena/api/admin/dashboard.php');
    if (!ok || !data.success) { toast(data.message || 'Failed to load dashboard', 'error'); return; }
    const s = data.data.stats || {};
    const live = await api('/code-arena/api/admin/contests.php?status=active');
    const liveCount = live.ok && live.data.success ? live.data.data.contests.length : 0;
    const health = [
        { label:'API', value:'Operational' },
        { label:'Database', value:'Connected' },
        { label:'Live Contests', value:String(liveCount) },
        { label:'Blocked Users', value:String(s.blocked_users || 0) },
    ];
    document.getElementById('overview-metrics').innerHTML = [
        ['Total Users', s.total_users || 0], ['Active Users', s.active_users_7d || 0],
        ['Total Contests', s.total_contests || 0], ['Live Contests', liveCount],
        ['Total Submissions', s.total_submissions || 0], ['System Health', 'OK'],
    ].map(([label,value]) => `<div class="metric-card"><strong>${value}</strong><span>${label}</span></div>`).join('');
    document.getElementById('health-list').innerHTML = health.map(h => `<div class="list-item"><strong>${h.label}</strong><span>${h.value}</span></div>`).join('');
    document.getElementById('settings-health').textContent = `Operational · ${s.total_users || 0} users · ${s.total_submissions || 0} submissions`;
    renderActivity('overview-activity', data.data.recent_activity || []);
}

function renderActivity(id, rows) {
    const el = document.getElementById(id);
    if (!rows.length) { el.innerHTML = '<div class="activity-item">No activity yet.</div>'; return; }
    el.innerHTML = rows.map(row => `<div class="activity-item">${safeText(row.event || row.action)}<span>${safeText(row.actor || row.ip_address || 'system')} · ${timeAgo(row.created_at)}</span></div>`).join('');
}

async function loadUsers() {
    const search = document.getElementById('user-search')?.value.trim() || '';
    const { ok, data } = await api(`/code-arena/api/admin/users.php?search=${encodeURIComponent(search)}`);
    const body = document.getElementById('users-body');
    if (!ok || !data.success) { body.innerHTML = `<tr><td colspan="9">${safeText(data.message)}</td></tr>`; return; }
    const adminCount = data.data.totalAdmins || 0;
    body.innerHTML = (data.data.users || []).map(u => {
        const isSelf = Number(u.id) === Number(CURRENT_ADMIN_ID);
        const isLastAdmin = u.role === 'admin' && adminCount === 1;
        return `<tr>
            <td>${u.id}</td><td><a href="/code-arena/profile.php?user=${encodeURIComponent(u.username)}">${safeText(u.username)}</a></td>
            <td>${safeText(u.email)}</td>
            <td><select class="role-select" onchange="updateRole(${u.id},this.value)" ${isSelf || isLastAdmin ? 'disabled' : ''}>${['student','user','instructor','org_admin','admin'].map(r => `<option ${u.role===r?'selected':''}>${r}</option>`).join('')}</select></td>
            <td>${Number(u.is_blocked) ? '<span style="color:var(--red)">Blocked</span>' : '<span style="color:var(--accent)">Active</span>'}</td>
            <td>${u.skill_rating || 1200}</td><td>${u.contest_rating || 1200}</td><td>${timeAgo(u.created_at)}</td>
            <td>${Number(u.is_blocked) ? `<button class="btn-outline btn-sm" onclick="toggleBlock(${u.id},false)" ${isSelf?'disabled':''}>Unban</button>` : `<button class="btn-outline btn-sm" onclick="toggleBlock(${u.id},true)" ${isSelf?'disabled':''}>Ban</button>`}</td>
        </tr>`;
    }).join('');
}
async function updateRole(id, role) {
    const { ok, data } = await api('/code-arena/api/admin/users.php', { method:'PUT', body:JSON.stringify({ id, role }) });
    toast(data.message || 'Updated', ok ? 'success' : 'error');
}
async function toggleBlock(id, block) {
    const { ok, data } = await api('/code-arena/api/admin/users.php', { method:'PUT', body:JSON.stringify({ id, action:block ? 'block' : 'unblock' }) });
    toast(data.message || 'Updated', ok ? 'success' : 'error');
    if (ok) loadUsers();
}

async function loadOrganizations() {
    const q = document.getElementById('org-search')?.value.trim() || '';
    const { ok, data } = await api(`/code-arena/api/admin/organizations.php?search=${encodeURIComponent(q)}`);
    const body = document.getElementById('orgs-body');
    if (!ok || !data.success) { body.innerHTML = `<tr><td colspan="7">${safeText(data.message)}</td></tr>`; return; }
    body.innerHTML = (data.data.organizations || []).map(o => `<tr>
        <td>${o.id}</td><td>${safeText(o.name)}</td><td>${safeText(o.type)}</td><td>${safeText(o.owner_username)}<div style="color:var(--text-muted);font-size:.78rem">${safeText(o.owner_email)}</div></td>
        <td>${o.member_count || 0}</td><td>${o.contest_count || 0}</td><td>${timeAgo(o.created_at)}</td>
    </tr>`).join('');
}

async function loadContests() {
    const q = document.getElementById('contest-search')?.value.trim() || '';
    const status = document.getElementById('contest-status')?.value || '';
    const { ok, data } = await api(`/code-arena/api/admin/contests.php?search=${encodeURIComponent(q)}&status=${encodeURIComponent(status)}`);
    const body = document.getElementById('contests-body');
    if (!ok || !data.success) { body.innerHTML = `<tr><td colspan="8">${safeText(data.message)}</td></tr>`; return; }
    body.innerHTML = (data.data.contests || []).map(c => `<tr>
        <td>${c.id}</td><td><a href="/code-arena/contest.php?id=${c.id}">${safeText(c.title)}</a></td><td>${safeText(c.status)}</td>
        <td>${safeText(c.creator_username)}</td><td>${c.participants || 0}</td><td>${c.submissions || 0}</td>
        <td>${safeText(c.start_time)}<div style="color:var(--text-muted);font-size:.78rem">${safeText(c.end_time)}</div></td>
        <td><a class="btn-outline btn-sm" href="/code-arena/contest_manage.php?id=${c.id}">Manage</a> ${c.status !== 'ended' ? `<button class="btn-outline btn-sm" onclick="endContest(${c.id})">End</button>` : ''}</td>
    </tr>`).join('');
}
async function endContest(id) {
    if (!confirm('End this contest now?')) return;
    const { ok, data } = await api('/code-arena/api/admin/contests.php', { method:'PUT', body:JSON.stringify({ id, action:'end' }) });
    toast(data.message || 'Updated', ok ? 'success' : 'error');
    if (ok) loadContests();
}

async function loadProblems() {
    const { ok, data } = await api('/code-arena/api/admin/problems.php');
    const body = document.getElementById('problems-body');
    if (!ok || !data.success) { body.innerHTML = `<tr><td colspan="6">${safeText(data.message)}</td></tr>`; return; }
    body.innerHTML = (data.data.problems || []).map(p => `<tr>
        <td>${p.id}</td><td><a href="/code-arena/problem.php?slug=${p.slug}">${safeText(p.title)}</a></td><td>${difficultyBadge(p.difficulty)}</td>
        <td>${p.total_submissions || 0}</td><td>${Number(p.is_public) ? 'Yes' : 'No'}</td>
        <td><a class="btn-outline btn-sm" href="/code-arena/editorial_manage.php?problem_id=${p.id}">Editorial</a></td>
    </tr>`).join('');
}

async function loadAnalytics() {
    const { ok, data } = await api('/code-arena/api/admin/analytics.php');
    if (!ok || !data.success) { toast(data.message || 'Failed to load analytics', 'error'); return; }
    const k = data.data.kpis || {};
    document.getElementById('analytics-kpis').innerHTML = [
        ['Users', k.total_users || 0],
        ['Submissions', k.total_submissions || 0],
        ['Acceptance', `${k.modeled_acceptance_rate || 0}%`],
        ['DAU / WAU', `${k.latest_dau || 0} / ${k.latest_wau || 0}`],
        ['Avg Rating', k.avg_rating || 0],
    ].map(([label,value]) => `<div class="metric-card"><strong>${value}</strong><span>${label}</span></div>`).join('');
    renderLine('user-growth-chart', data.data.user_growth || [], 'count');
    renderLine('submission-chart', data.data.submission_trend || [], 'count', 'accepted');
    renderBars('contest-chart', data.data.contest_trend || [], 'participants');
    renderBars('difficulty-chart', data.data.difficulty_performance || [], 'acceptance_rate', 'difficulty');
    renderLine('activity-chart', data.data.activity_trend || [], 'wau', 'dau');
    renderLine('rating-chart', data.data.rating_trend || [], 'avg_rating');
    document.getElementById('role-breakdown').innerHTML = (data.data.role_breakdown || []).map(r => `<div class="list-item"><strong>${safeText(r.role)}</strong><span>${r.count} accounts</span></div>`).join('');
}
function renderBars(id, rows, key, labelKey = 'day') {
    const lastRows = rows.slice(-10);
    const max = Math.max(1, ...lastRows.map(r => Number(r[key] || 0)));
    document.getElementById(id).innerHTML = lastRows.length ? lastRows.map(r => `<div class="bar" title="${safeText(r[labelKey] || r.day)}: ${r[key] || 0}" style="height:${Math.max(6, Math.round(Number(r[key] || 0) / max * 100))}%"></div>`).join('') : '<div style="color:var(--text-muted)">No data yet.</div>';
}
function renderLine(id, rows, key, secondaryKey = null) {
    const el = document.getElementById(id);
    const points = rows.slice(-30);
    if (!points.length) { el.innerHTML = '<div style="padding:18px;color:var(--text-muted)">No data yet.</div>'; return; }
    const keys = secondaryKey ? [key, secondaryKey] : [key];
    const values = points.flatMap(row => keys.map(k => Number(row[k] || 0)));
    const min = Math.min(...values);
    const max = Math.max(...values, min + 1);
    const w = 640, h = 190, pad = 18;
    const pathFor = chartKey => points.map((row, i) => {
        const x = pad + (i / Math.max(1, points.length - 1)) * (w - pad * 2);
        const y = h - pad - ((Number(row[chartKey] || 0) - min) / (max - min)) * (h - pad * 2);
        return `${i === 0 ? 'M' : 'L'}${x.toFixed(1)},${y.toFixed(1)}`;
    }).join(' ');
    const title = `${safeText(points[0].day)} → ${safeText(points[points.length - 1].day)}`;
    el.innerHTML = `<svg viewBox="0 0 ${w} ${h}" role="img" aria-label="${title}">
        <defs><linearGradient id="lineGrad-${id}" x1="0" x2="1"><stop offset="0" stop-color="#7c3aed"/><stop offset="1" stop-color="#22c55e"/></linearGradient></defs>
        <path d="${pathFor(key)}" fill="none" stroke="url(#lineGrad-${id})" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
        ${secondaryKey ? `<path d="${pathFor(secondaryKey)}" fill="none" stroke="rgba(167,139,250,.62)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>` : ''}
    </svg>`;
}

async function loadSubmissions() {
    const params = new URLSearchParams({ page: 1 });
    const u = document.getElementById('sub-user')?.value.trim();
    const p = document.getElementById('sub-problem')?.value.trim();
    const s = document.getElementById('sub-status')?.value;
    if (u) params.set('user', u); if (p) params.set('problem', p); if (s) params.set('status', s);
    const { ok, data } = await api(`/code-arena/api/admin/submissions.php?${params}`);
    const body = document.getElementById('subs-body');
    if (!ok || !data.success) { body.innerHTML = `<tr><td colspan="8">${safeText(data.message)}</td></tr>`; return; }
    body.innerHTML = (data.data.submissions || []).map(s => `<tr>
        <td>#${s.id}</td><td>${safeText(s.username)}</td><td>${safeText(s.problem_title)}</td><td>${statusBadge(s.status)}</td>
        <td>${safeText(s.language)}</td><td>${s.runtime_ms ? s.runtime_ms + 'ms' : '-'}</td><td>${s.contest_id || '-'}</td><td>${timeAgo(s.submitted_at)}</td>
    </tr>`).join('');
}

async function loadLogs() {
    const { ok, data } = await api('/code-arena/api/admin/logs.php');
    if (!ok || !data.success) { toast(data.message || 'Failed to load logs', 'error'); return; }
    renderActivity('logs-list', data.data.logs || []);
}

function escHtml(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

loadOverview();
loadedSections.add('overview');
</script>
</body>
</html>
