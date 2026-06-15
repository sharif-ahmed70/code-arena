<?php
// ============================================================
//  CODE ARENA - Student Dashboard
// ============================================================
require_once 'includes/session.php';
requireLogin();
if (canAccessOrganizationDashboard()) {
    safeRedirect('/code-arena/organization/dashboard.php');
}
if (!isAdmin() && isset($_SESSION['profile_completed']) && (int)$_SESSION['profile_completed'] === 0) {
    safeRedirect('/code-arena/profile_complete.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Code Arena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css?v=20260615-ui2">
    <style>
        .dash-head { display:flex; justify-content:space-between; gap:20px; align-items:flex-end; margin-bottom:28px; }
        .dash-head p { color:var(--text-muted); margin-top:6px; }
        .dash-actions { display:flex; gap:10px; flex-wrap:wrap; }
        .metric-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:24px; }
        .metric { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:18px; }
        .metric-value { font-family:'Plus Jakarta Sans',sans-serif; font-size:2rem; font-weight:800; line-height:1; }
        .metric-label { color:var(--text-muted); font-size:.78rem; text-transform:uppercase; letter-spacing:.05em; margin-top:8px; }
        .dashboard-grid { display:grid; grid-template-columns:minmax(0,1.35fr) minmax(320px,.65fr); gap:24px; align-items:start; }
        .stack { display:flex; flex-direction:column; gap:16px; }
        .section-title { display:flex; justify-content:space-between; gap:14px; align-items:center; margin-bottom:14px; }
        .section-title h3 { font-size:1rem; }
        .section-title a { color:var(--accent); font-size:.84rem; }
        .next-card { border:1px solid rgba(0,232,122,.28); background:linear-gradient(135deg,rgba(0,232,122,.08),rgba(108,160,255,.06)); border-radius:var(--radius); padding:18px; }
        .next-card h3 { font-size:1.2rem; margin-bottom:8px; }
        .next-meta { display:flex; gap:8px; flex-wrap:wrap; margin:12px 0 16px; }
        .tag { display:inline-flex; align-items:center; padding:3px 8px; border-radius:999px; background:var(--bg-card2); color:var(--text-muted); font-size:.75rem; }
        .progress-wrap { height:8px; background:var(--border); border-radius:999px; overflow:hidden; margin:10px 0 8px; }
        .progress-fill { height:100%; background:linear-gradient(90deg,var(--accent),var(--blue)); border-radius:999px; }
        .problem-list, .contest-list, .submission-list { display:flex; flex-direction:column; gap:10px; }
        .problem-row, .contest-row, .submission-row { display:grid; gap:10px; align-items:center; padding:12px; background:var(--bg-card2); border:1px solid var(--border); border-radius:var(--radius-sm); }
        .problem-row { grid-template-columns:1fr auto; }
        .contest-row { grid-template-columns:1fr auto; }
        .submission-row { grid-template-columns:1fr auto auto; }
        .row-title { font-weight:650; color:var(--text); }
        .row-title:hover { color:var(--accent); }
        .row-sub { color:var(--text-muted); font-size:.78rem; margin-top:2px; }
        .muted-empty { color:var(--text-muted); font-size:.88rem; padding:10px 0; }
        .mini-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .mini-card { background:var(--bg-card2); border:1px solid var(--border); border-radius:var(--radius-sm); padding:14px; }
        .mini-card strong { display:block; font-size:1.4rem; line-height:1; }
        .mini-card span { color:var(--text-muted); font-size:.78rem; }
        .weak-panel { margin-bottom:24px; border:1px solid rgba(108,160,255,.22); background:linear-gradient(135deg,rgba(108,160,255,.08),rgba(0,232,122,.045)); }
        .weak-panel-body { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:18px; align-items:center; }
        .weak-list { display:grid; gap:10px; }
        .weak-row { display:grid; grid-template-columns:110px minmax(0,1fr) 72px; gap:10px; align-items:center; color:var(--text); }
        .weak-skill { color:var(--text); font-weight:750; }
        .weak-skill:hover { color:var(--accent); }
        .weak-bar { height:9px; overflow:hidden; border-radius:999px; background:var(--border); }
        .weak-bar span { display:block; height:100%; border-radius:inherit; background:linear-gradient(90deg,var(--accent),var(--blue)); }
        .weak-level { color:var(--text-muted); font-size:.78rem; text-align:right; }
        .weak-actions { display:flex; flex-direction:column; gap:10px; min-width:170px; }
        .weak-analytics-grid { display:grid; grid-template-columns:minmax(0,1.15fr) minmax(240px,.85fr); gap:16px; margin-top:18px; }
        .trend-card, .history-card, .smart-link-card { padding:14px; border:1px solid var(--border); border-radius:var(--radius-sm); background:rgba(255,255,255,.025); }
        .trend-card h4, .history-card h4, .smart-link-card h4 { margin:0 0 12px; font-size:.9rem; }
        .trend-bars { height:138px; display:grid; grid-template-columns:repeat(7,1fr); gap:8px; align-items:end; padding:12px; border:1px solid var(--border); border-radius:var(--radius-sm); background:var(--bg-card2); }
        .trend-bar-wrap { display:grid; gap:6px; align-items:end; justify-items:center; height:100%; }
        .trend-bar { width:100%; min-height:8px; border-radius:999px 999px 4px 4px; background:linear-gradient(180deg,var(--accent),var(--blue)); }
        .trend-label { color:var(--text-muted); font-size:.68rem; }
        .history-list, .smart-link-list { display:grid; gap:9px; }
        .history-item, .smart-link-item { display:block; padding:10px; border:1px solid var(--border); border-radius:var(--radius-sm); background:var(--bg-card2); color:var(--text-muted); font-size:.82rem; line-height:1.4; }
        .smart-link-item strong { display:block; color:var(--text); margin-bottom:4px; }
        .smart-link-item:hover { border-color:rgba(0,232,122,.28); color:var(--text); }
        @media(max-width:900px) {
            .dash-head { align-items:flex-start; flex-direction:column; }
            .metric-grid { grid-template-columns:repeat(2,1fr); }
            .dashboard-grid { grid-template-columns:1fr; }
            .weak-panel-body { grid-template-columns:1fr; }
            .weak-actions { flex-direction:row; flex-wrap:wrap; }
            .weak-analytics-grid { grid-template-columns:1fr; }
        }
        @media(max-width:560px) {
            .metric-grid, .mini-grid { grid-template-columns:1fr; }
            .problem-row, .contest-row, .submission-row { grid-template-columns:1fr; }
            .weak-row { grid-template-columns:1fr; }
            .weak-level { text-align:left; }
        }
    </style>
</head>
<body>
<?php require_once 'includes/navbar.php'; ?>

<div class="page">
<div class="container">
    <div class="dash-head fade-up">
        <div>
            <h1>Dashboard</h1>
            <p id="welcome-text">Loading your training snapshot...</p>
        </div>
        <div class="dash-actions">
            <a class="btn-outline" href="/code-arena/problems.php?saved=1">Saved</a>
            <a class="btn-primary" href="/code-arena/roadmap.php">Roadmap</a>
        </div>
    </div>

    <div class="metric-grid fade-up fade-up-1" id="metric-grid"></div>

    <section class="card weak-panel fade-up fade-up-2">
        <div class="section-title">
            <h3>Weak Areas</h3>
            <a href="/code-arena/analytics.php">Open analytics</a>
        </div>
        <div class="weak-panel-body">
            <div class="weak-list" id="weak-area-list"></div>
            <div class="weak-actions">
                <a class="btn-primary" href="/code-arena/analytics.php">View Details</a>
                <a class="btn-outline" href="/code-arena/problems.php?sort=recommended">Practice Now</a>
            </div>
        </div>
        <div class="weak-analytics-grid">
            <div class="trend-card">
                <h4>Weekly Progress Trend</h4>
                <div class="trend-bars" id="weak-trend-chart"></div>
            </div>
            <div class="history-card">
                <h4>Improvement History</h4>
                <div class="history-list" id="weak-history-list"></div>
            </div>
        </div>
        <div class="smart-link-card" style="margin-top:16px">
            <h4>Smart Suggestions</h4>
            <div class="smart-link-list" id="weak-smart-links"></div>
        </div>
    </section>

    <div class="dashboard-grid">
        <main class="stack">
            <section class="next-card fade-up fade-up-2" id="next-card">
                <p class="muted-empty">Loading recommendation...</p>
            </section>

            <section class="card fade-up fade-up-3">
                <div class="section-title">
                    <h3>Today Roadmap</h3>
                    <a href="/code-arena/roadmap.php">Open roadmap</a>
                </div>
                <div id="roadmap-card"></div>
            </section>

            <section class="card fade-up fade-up-4">
                <div class="section-title">
                    <h3>Saved Queue</h3>
                    <a href="/code-arena/problems.php?saved=1">View saved</a>
                </div>
                <div class="problem-list" id="saved-list"></div>
            </section>
        </main>

        <aside class="stack">
            <section class="card fade-up fade-up-2">
                <div class="section-title">
                    <h3>Focus</h3>
                    <a href="/code-arena/profile.php">Profile</a>
                </div>
                <div class="mini-grid" id="focus-grid"></div>
            </section>

            <section class="card fade-up fade-up-3">
                <div class="section-title">
                    <h3>Contests</h3>
                    <a href="/code-arena/contests.php">All contests</a>
                </div>
                <div class="contest-list" id="contest-list"></div>
            </section>

            <section class="card fade-up fade-up-4">
                <div class="section-title">
                    <h3>Recent Submissions</h3>
                    <a href="/code-arena/submissions.php">View all</a>
                </div>
                <div class="submission-list" id="submission-list"></div>
            </section>
        </aside>
    </div>
</div>
</div>

<script src="/code-arena/assets/js/main.js"></script>
<script>
function escHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, ch => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[ch]));
}

function tagList(tags) {
    return String(tags || '').split(',').map(t => t.trim()).filter(Boolean).slice(0, 3)
        .map(t => `<span class="tag">${escHtml(t.replace(/-/g, ' '))}</span>`).join('');
}

function problemRow(p, extra = '') {
    return `
        <div class="problem-row">
            <div>
                <a class="row-title" href="/code-arena/problem.php?slug=${encodeURIComponent(p.slug)}">${escHtml(p.title)}</a>
                <div class="row-sub">${tagList(p.tags)} ${extra}</div>
            </div>
            ${difficultyBadge(p.difficulty)}
        </div>`;
}

async function loadDashboard() {
    const { ok, data } = await api('/code-arena/api/users/dashboard.php');
    if (!ok || !data.success) {
        toast(data.message || 'Failed to load dashboard', 'error');
        return;
    }

    const d = data.data;
    const s = d.stats;
    const accepted = Number(s.accepted_submissions || 0);
    const total = Number(s.total_submissions || 0);
    const acceptance = total ? Math.round(accepted / total * 100) : 0;

    document.getElementById('welcome-text').textContent =
        `Welcome back, ${d.user.username}. Pick the next useful problem and keep the streak moving.`;

    document.getElementById('metric-grid').innerHTML = `
        <div class="metric"><div class="metric-value">${s.solved_problems}</div><div class="metric-label">Solved</div></div>
        <div class="metric"><div class="metric-value">${acceptance}%</div><div class="metric-label">Acceptance</div></div>
        <div class="metric"><div class="metric-value">${s.streak_days}</div><div class="metric-label">Day Streak</div></div>
        <div class="metric"><div class="metric-value">${d.user.skill_rating || 1200}</div><div class="metric-label">Skill Rating</div></div>`;

    renderNext(d.recommended_problem, d.weak_topic, d.roadmap.next_problem);
    renderRoadmap(d.roadmap);
    renderSaved(d.saved_problems || []);
    renderWeakAreas(d);
    renderFocus(d);
    renderContests(d.contests || []);
    renderRecent(d.recent_submissions || []);
}

function renderNext(recommended, weakTopic, nextRoadmap) {
    const p = recommended || nextRoadmap;
    const el = document.getElementById('next-card');
    if (!p) {
        el.innerHTML = '<h3>No pending public problems</h3><p class="muted-empty">You are caught up for now.</p>';
        return;
    }
    const reason = weakTopic
        ? `Recommended because ${weakTopic.tag.replace(/-/g, ' ')} is your current weak topic.`
        : 'Recommended from your unsolved public problem queue.';
    el.innerHTML = `
        <h3>${escHtml(p.title)}</h3>
        <p style="color:var(--text-muted)">${escHtml(reason)}</p>
        <div class="next-meta">
            ${difficultyBadge(p.difficulty)}
            ${p.roadmap_day ? `<span class="tag">Day ${p.roadmap_day}</span>` : ''}
            ${tagList(p.tags)}
        </div>
        <a class="btn-primary" href="/code-arena/problem.php?slug=${encodeURIComponent(p.slug)}">Start Problem</a>`;
}

function renderRoadmap(roadmap) {
    const total = Number(roadmap.total || 0);
    const solved = Number(roadmap.solved || 0);
    const pct = total ? Math.round(solved / total * 100) : 0;
    const problems = (roadmap.problems || []).map(p =>
        problemRow(p, Number(p.solved) ? '<span class="tag">Solved</span>' : '')
    ).join('');
    document.getElementById('roadmap-card').innerHTML = `
        <div style="display:flex;justify-content:space-between;gap:12px;color:var(--text-muted);font-size:.88rem">
            <span>Day ${roadmap.day}</span><span>${solved}/${total} solved</span>
        </div>
        <div class="progress-wrap"><div class="progress-fill" style="width:${pct}%"></div></div>
        <div class="problem-list" style="margin-top:14px">${problems || '<p class="muted-empty">No roadmap problems for this day.</p>'}</div>`;
}

function renderSaved(rows) {
    const el = document.getElementById('saved-list');
    if (!rows.length) {
        el.innerHTML = '<p class="muted-empty">No saved problems yet.</p>';
        return;
    }
    el.innerHTML = rows.map(p => problemRow(p, Number(p.solved) ? '<span class="tag">Solved</span>' : '')).join('');
}

function renderWeakAreas(d) {
    const weakTopic = d.weak_topic ? String(d.weak_topic.tag || '').replace(/-/g, ' ') : '';
    const weakTag = d.weak_topic?.tag || 'graphs';
    const rows = [
        { skill: weakTopic || 'Graphs', level: weakTopic ? 'Weak' : 'Weak', score: 32, tag: weakTag },
        { skill: 'DP', level: 'Medium', score: 58, tag: 'dynamic-programming' },
        { skill: 'Trees', level: 'Strong', score: 82, tag: 'trees' },
    ];
    document.getElementById('weak-area-list').innerHTML = rows.map(item => `
        <div class="weak-row">
            <a class="weak-skill" href="/code-arena/problems.php?tag=${encodeURIComponent(item.tag)}">${escHtml(item.skill)}</a>
            <div class="weak-bar" aria-label="${escHtml(item.skill)} ${item.level}">
                <span style="width:${item.score}%"></span>
            </div>
            <a class="weak-level" href="/code-arena/analytics.php?topic=${encodeURIComponent(item.tag)}">${item.level}</a>
        </div>
    `).join('');

    const trend = [
        { day: 'Mon', solved: 2, accuracy: 54 },
        { day: 'Tue', solved: 3, accuracy: 58 },
        { day: 'Wed', solved: 1, accuracy: 50 },
        { day: 'Thu', solved: 4, accuracy: 64 },
        { day: 'Fri', solved: 3, accuracy: 67 },
        { day: 'Sat', solved: 5, accuracy: 72 },
        { day: 'Sun', solved: 4, accuracy: 76 },
    ];
    document.getElementById('weak-trend-chart').innerHTML = trend.map(item => `
        <a class="trend-bar-wrap" href="/code-arena/analytics.php?day=${encodeURIComponent(item.day)}" title="${item.solved} solved, ${item.accuracy}% accuracy">
            <span class="trend-bar" style="height:${Math.max(14, item.accuracy)}%"></span>
            <span class="trend-label">${item.day}</span>
        </a>
    `).join('');

    document.getElementById('weak-history-list').innerHTML = [
        `${rows[0].skill} improved from Weak to Medium target range`,
        'DP accuracy increased over the last practice block',
        'Trees remained stable with strong solve consistency'
    ].map(item => `<a class="history-item" href="/code-arena/analytics.php">${escHtml(item)}</a>`).join('');

    document.getElementById('weak-smart-links').innerHTML = [
        {
            title: `${rows[0].skill} practice set`,
            body: 'Open filtered problems for the current weak topic.',
            href: `/code-arena/problems.php?tag=${encodeURIComponent(weakTag)}`
        },
        {
            title: 'Contest suggestion',
            body: `Find contests that match ${rows[0].skill} preparation.`,
            href: `/code-arena/contests.php?tag=${encodeURIComponent(weakTag)}`
        },
        {
            title: 'Detailed AI insight',
            body: 'Review trend and improvement plan.',
            href: `/code-arena/analytics.php?topic=${encodeURIComponent(weakTag)}`
        }
    ].map(item => `
        <a class="smart-link-item" href="${item.href}">
            <strong>${escHtml(item.title)}</strong>
            ${escHtml(item.body)}
        </a>
    `).join('');
}

function renderFocus(d) {
    const weak = d.weak_topic
        ? `${d.weak_topic.tag.replace(/-/g, ' ')} (${d.weak_topic.failure_rate}%)`
        : 'No weak topic yet';
    document.getElementById('focus-grid').innerHTML = `
        <div class="mini-card"><strong>${d.roadmap.day}</strong><span>Current roadmap day</span></div>
        <div class="mini-card"><strong>${d.stats.attempted_problems}</strong><span>Attempted problems</span></div>
        <div class="mini-card"><strong>${d.user.contest_rating || 1200}</strong><span>Contest rating</span></div>
        <div class="mini-card"><strong style="font-size:1rem;line-height:1.25">${escHtml(weak)}</strong><span>Weak topic</span></div>`;
}

function renderContests(rows) {
    const el = document.getElementById('contest-list');
    if (!rows.length) {
        el.innerHTML = '<p class="muted-empty">No active or upcoming contests.</p>';
        return;
    }
    el.innerHTML = rows.map(c => `
        <div class="contest-row">
            <div>
                <a class="row-title" href="/code-arena/contest.php?id=${c.id}">${escHtml(c.title)}</a>
                <div class="row-sub">${c.status} - ${new Date(c.start_time).toLocaleString()}</div>
            </div>
            <span class="tag">${Number(c.registered) ? 'Registered' : 'Open'}</span>
        </div>`).join('');
}

function renderRecent(rows) {
    const el = document.getElementById('submission-list');
    if (!rows.length) {
        el.innerHTML = '<p class="muted-empty">No submissions yet.</p>';
        return;
    }
    el.innerHTML = rows.map(s => `
        <div class="submission-row">
            <div>
                <a class="row-title" href="/code-arena/problem.php?slug=${encodeURIComponent(s.problem_slug)}">${escHtml(s.problem_title)}</a>
                <div class="row-sub">${langName(s.language)} - ${timeAgo(s.submitted_at)}</div>
            </div>
            ${statusBadge(s.status)}
            ${difficultyBadge(s.difficulty)}
        </div>`).join('');
}

loadDashboard();
</script>
</body>
</html>
