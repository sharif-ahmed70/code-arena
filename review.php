<?php
// ============================================================
//  CODE ARENA - Mistake Review
// ============================================================
require_once 'includes/session.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review - Code Arena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css?v=20260615-ui5">
    <style>
        .review-head { display:flex; justify-content:space-between; gap:20px; align-items:flex-end; margin-bottom:28px; }
        .review-head p { color:var(--text-muted); margin-top:6px; }
        .review-actions { display:flex; gap:10px; flex-wrap:wrap; }
        .metric-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:24px; }
        .metric { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:18px; }
        .metric strong { display:block; font-family:'Plus Jakarta Sans',sans-serif; font-size:2rem; line-height:1; }
        .metric span { display:block; color:var(--text-muted); font-size:.78rem; text-transform:uppercase; letter-spacing:.05em; margin-top:8px; }
        .review-grid { display:grid; grid-template-columns:minmax(0,1.25fr) minmax(320px,.75fr); gap:24px; align-items:start; }
        .stack { display:flex; flex-direction:column; gap:16px; }
        .section-title { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:14px; }
        .section-title h3 { font-size:1rem; }
        .section-title a { color:var(--accent); font-size:.84rem; }
        .retry-list, .topic-list, .failure-list { display:flex; flex-direction:column; gap:10px; }
        .retry-row, .topic-row, .failure-row { background:var(--bg-card2); border:1px solid var(--border); border-radius:var(--radius-sm); padding:12px; }
        .retry-row { display:grid; grid-template-columns:1fr auto; gap:12px; align-items:center; }
        .failure-row { display:grid; grid-template-columns:1fr auto; gap:10px; align-items:center; }
        .row-title { color:var(--text); font-weight:650; }
        .row-title:hover { color:var(--accent); }
        .row-sub { color:var(--text-muted); font-size:.78rem; margin-top:4px; display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
        .tag { display:inline-flex; align-items:center; padding:3px 8px; border-radius:999px; background:var(--bg-card); color:var(--text-muted); font-size:.74rem; border:1px solid var(--border); }
        .topic-row-top { display:flex; justify-content:space-between; gap:10px; align-items:center; margin-bottom:8px; }
        .topic-name { text-transform:capitalize; font-weight:650; }
        .topic-bar { height:8px; border-radius:999px; overflow:hidden; background:var(--border); }
        .topic-fill { height:100%; border-radius:999px; background:linear-gradient(90deg,var(--red),var(--yellow)); }
        .empty { color:var(--text-muted); font-size:.88rem; padding:12px 0; }
        .status-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
        .status-mini { padding:12px; background:var(--bg-card2); border:1px solid var(--border); border-radius:var(--radius-sm); }
        .status-mini strong { display:block; font-size:1.45rem; line-height:1; }
        .status-mini span { color:var(--text-muted); font-size:.78rem; }
        @media(max-width:900px) {
            .review-head { flex-direction:column; align-items:flex-start; }
            .metric-grid { grid-template-columns:repeat(2,1fr); }
            .review-grid { grid-template-columns:1fr; }
        }
        @media(max-width:560px) {
            .metric-grid, .status-grid { grid-template-columns:1fr; }
            .retry-row, .failure-row { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>
<?php require_once 'includes/navbar.php'; ?>

<div class="page">
<div class="container">
    <div class="review-head fade-up">
        <div>
            <h1>Review</h1>
            <p>Turn failed attempts into a focused retry plan.</p>
        </div>
        <div class="review-actions">
            <a href="/code-arena/submissions.php" class="btn-outline">Submissions</a>
            <a href="/code-arena/dashboard.php" class="btn-primary">Dashboard</a>
        </div>
    </div>

    <div class="metric-grid fade-up fade-up-1" id="metric-grid"></div>

    <div class="review-grid">
        <main class="stack">
            <section class="card fade-up fade-up-2">
                <div class="section-title">
                    <h3>Retry Queue</h3>
                    <a href="/code-arena/problems.php">Find more</a>
                </div>
                <div class="retry-list" id="retry-list"></div>
            </section>

            <section class="card fade-up fade-up-3">
                <div class="section-title">
                    <h3>Recent Failures</h3>
                    <a href="/code-arena/submissions.php">Open history</a>
                </div>
                <div class="failure-list" id="failure-list"></div>
            </section>
        </main>

        <aside class="stack">
            <section class="card fade-up fade-up-2">
                <div class="section-title">
                    <h3>Status Breakdown</h3>
                </div>
                <div class="status-grid" id="status-grid"></div>
            </section>

            <section class="card fade-up fade-up-3">
                <div class="section-title">
                    <h3>Topic Review</h3>
                    <a href="/code-arena/profile.php">Analytics</a>
                </div>
                <div class="topic-list" id="topic-list"></div>
            </section>
        </aside>
    </div>
</div>
</div>

<script src="/code-arena/assets/js/main.js?v=20260615-ui5"></script>
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

async function loadReview() {
    const { ok, data } = await api('/code-arena/api/users/review.php');
    if (!ok || !data.success) {
        toast(data.message || 'Failed to load review', 'error');
        return;
    }

    const d = data.data;
    const breakdown = d.stats.status_breakdown || {};
    const retryCount = (d.retry_queue || []).length;
    const topicCount = (d.topic_review || []).length;

    document.getElementById('metric-grid').innerHTML = `
        <div class="metric"><strong>${d.stats.failed_submissions}</strong><span>Failed Submissions</span></div>
        <div class="metric"><strong>${d.stats.failed_problems}</strong><span>Failed Problems</span></div>
        <div class="metric"><strong>${retryCount}</strong><span>Unsolved Retries</span></div>
        <div class="metric"><strong>${topicCount}</strong><span>Topics To Review</span></div>`;

    renderBreakdown(breakdown);
    renderRetry(d.retry_queue || []);
    renderTopics(d.topic_review || []);
    renderRecent(d.recent_failures || []);
}

function renderBreakdown(rows) {
    const labels = ['Wrong Answer', 'Runtime Error', 'Time Limit Exceeded', 'Compilation Error'];
    document.getElementById('status-grid').innerHTML = labels.map(label => `
        <div class="status-mini">
            <strong>${Number(rows[label] || 0)}</strong>
            <span>${label}</span>
        </div>`).join('');
}

function renderRetry(rows) {
    const el = document.getElementById('retry-list');
    if (!rows.length) {
        el.innerHTML = '<p class="empty">No unsolved failed problems. Nice recovery.</p>';
        return;
    }
    el.innerHTML = rows.map(p => `
        <div class="retry-row">
            <div>
                <a class="row-title" href="/code-arena/problem.php?slug=${encodeURIComponent(p.slug)}">${escHtml(p.title)}</a>
                <div class="row-sub">
                    ${difficultyBadge(p.difficulty)}
                    <span class="tag">${p.failed_attempts} failed</span>
                    <span class="tag">${escHtml(p.last_status)}</span>
                    ${tagList(p.tags)}
                </div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end">
                <a class="btn-outline" href="/code-arena/discuss_create.php?problem_id=${p.id}">Ask Help</a>
                <a class="btn-outline" href="/code-arena/problem.php?slug=${encodeURIComponent(p.slug)}">Retry</a>
            </div>
        </div>`).join('');
}

function renderTopics(rows) {
    const el = document.getElementById('topic-list');
    if (!rows.length) {
        el.innerHTML = '<p class="empty">Not enough failed topic data yet.</p>';
        return;
    }
    el.innerHTML = rows.map(t => `
        <div class="topic-row">
            <div class="topic-row-top">
                <span class="topic-name">${escHtml(t.tag.replace(/-/g, ' '))}</span>
                <span class="tag">${t.failure_rate}% fail</span>
            </div>
            <div class="topic-bar"><div class="topic-fill" style="width:${t.failure_rate}%"></div></div>
            <div class="row-sub"><span>${t.failures}/${t.attempts} failed attempts</span><span>${t.problems} problems</span></div>
        </div>`).join('');
}

function renderRecent(rows) {
    const el = document.getElementById('failure-list');
    if (!rows.length) {
        el.innerHTML = '<p class="empty">No recent failures.</p>';
        return;
    }
    el.innerHTML = rows.map(s => `
        <div class="failure-row">
            <div>
                <a class="row-title" href="/code-arena/submissions.php">${escHtml(s.problem_title)}</a>
                <div class="row-sub">
                    ${statusBadge(s.status)}
                    <span class="tag">${langName(s.language)}</span>
                    <span>${timeAgo(s.submitted_at)}</span>
                </div>
            </div>
            <a class="btn-outline" href="/code-arena/problem.php?slug=${encodeURIComponent(s.problem_slug)}">Open</a>
        </div>`).join('');
}

loadReview();
</script>
</body>
</html>
