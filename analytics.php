<?php
// ============================================================
//  CODE ARENA - User Analytics
// ============================================================
require_once 'includes/session.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Code Arena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css?v=20260615-ui2">
    <style>
        .analytics-head { display:flex; justify-content:space-between; gap:18px; align-items:flex-end; margin-bottom:24px; }
        .analytics-head p { margin-top:6px; color:var(--text-muted); }
        .dash-actions { display:flex; gap:10px; flex-wrap:wrap; }
        .analytics-grid { display:grid; grid-template-columns:minmax(0,1.2fr) minmax(300px,.8fr); gap:20px; align-items:start; }
        .section-title { display:flex; justify-content:space-between; gap:14px; align-items:center; margin-bottom:14px; }
        .section-title h3 { font-size:1rem; }
        .section-title a { color:var(--accent); font-size:.84rem; }
        .skill-stack { display:grid; gap:12px; }
        .skill-row { display:grid; grid-template-columns:130px minmax(0,1fr) 80px; gap:12px; align-items:center; padding:14px; border:1px solid var(--border); border-radius:var(--radius-sm); background:var(--bg-card2); }
        .skill-row a { color:var(--text); font-weight:750; }
        .skill-row a:hover { color:var(--accent); }
        .skill-bar { height:10px; border-radius:999px; overflow:hidden; background:var(--border); }
        .skill-bar span { display:block; height:100%; border-radius:inherit; background:linear-gradient(90deg,var(--accent),var(--blue)); }
        .skill-level { color:var(--text-muted); font-size:.82rem; text-align:right; }
        .recommendation-list { display:grid; gap:10px; }
        .recommendation { padding:14px; border:1px solid rgba(0,232,122,.18); border-radius:var(--radius-sm); background:linear-gradient(135deg,rgba(0,232,122,.07),rgba(108,160,255,.045)); }
        .recommendation strong { display:block; margin-bottom:5px; }
        .recommendation span { color:var(--text-muted); font-size:.86rem; line-height:1.45; }
        @media(max-width:900px) { .analytics-head { align-items:flex-start; flex-direction:column; } .analytics-grid { grid-template-columns:1fr; } }
        @media(max-width:560px) { .skill-row { grid-template-columns:1fr; } .skill-level { text-align:left; } }
    </style>
</head>
<body>
<?php require_once 'includes/navbar.php'; ?>
<div class="page">
<div class="container">
    <div class="analytics-head fade-up">
        <div>
            <h1>Performance Analytics</h1>
            <p>Track weak areas and jump directly into focused practice.</p>
        </div>
        <div class="dash-actions">
            <a class="btn-outline" href="/code-arena/dashboard.php">Dashboard</a>
            <a class="btn-primary" href="/code-arena/problems.php?sort=recommended">Practice</a>
        </div>
    </div>

    <div class="analytics-grid">
        <section class="card fade-up fade-up-1">
            <div class="section-title">
                <h3>Weak Areas</h3>
                <a href="/code-arena/problems.php">All problems</a>
            </div>
            <div class="skill-stack" id="analytics-skills"></div>
        </section>

        <aside class="card fade-up fade-up-2">
            <div class="section-title">
                <h3>AI Practice Plan</h3>
                <a href="/code-arena/roadmap.php">Roadmap</a>
            </div>
            <div class="recommendation-list" id="analytics-plan"></div>
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

async function loadAnalytics() {
    const demo = [
        { skill: 'Graphs', tag: 'graphs', level: 'Weak', score: 32 },
        { skill: 'DP', tag: 'dynamic-programming', level: 'Medium', score: 58 },
        { skill: 'Trees', tag: 'trees', level: 'Strong', score: 82 },
        { skill: 'Greedy', tag: 'greedy', level: 'Medium', score: 63 },
    ];

    try {
        const { ok, data } = await api('/code-arena/api/users/dashboard.php');
        if (ok && data.success && data.data?.weak_topic?.tag) {
            const tag = data.data.weak_topic.tag;
            demo[0] = { skill: tag.replace(/-/g, ' '), tag, level: 'Weak', score: 30 };
        }
    } catch (_) {}

    document.getElementById('analytics-skills').innerHTML = demo.map(item => `
        <div class="skill-row">
            <a href="/code-arena/problems.php?tag=${encodeURIComponent(item.tag)}">${escHtml(item.skill)}</a>
            <div class="skill-bar"><span style="width:${item.score}%"></span></div>
            <a class="skill-level" href="/code-arena/problems.php?tag=${encodeURIComponent(item.tag)}">${item.level}</a>
        </div>
    `).join('');

    document.getElementById('analytics-plan').innerHTML = [
        ['Repair the weakest topic first', 'Solve 3 targeted problems from your weakest tag before starting random practice.'],
        ['Mix speed and accuracy', 'Use one 25-minute focus session for easy/medium problems, then review wrong submissions.'],
        ['Contest prep', 'Before upcoming contests, practice graphs, greedy, and binary search patterns.']
    ].map(([title, body]) => `
        <div class="recommendation">
            <strong>${escHtml(title)}</strong>
            <span>${escHtml(body)}</span>
        </div>
    `).join('');
}

loadAnalytics();
</script>
</body>
</html>
