<?php
// ============================================================
//  CODE ARENA — 30-Day Roadmap
// ============================================================
require_once 'includes/session.php';
requireLogin();
if (isAdmin()) { header('Location: /code-arena/admin.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roadmap — Code Arena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css">
    <style>
        .roadmap-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 16px;
            margin-top: 32px;
        }
        .day-card {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 20px;
            transition: border-color .2s, transform .2s;
        }
        .day-card.active    { border-color: var(--accent); box-shadow: 0 0 0 1px rgba(0,232,122,.12); }
        .day-card.completed { border-color: rgba(0,232,122,.28); }
        .day-card.locked    { opacity: .42; pointer-events: none; }
        .day-card:not(.locked):hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(0,0,0,.35); }

        .day-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:6px; gap:8px; }
        .day-number { font-family:'Plus Jakarta Sans', sans-serif; font-size:.72rem; font-weight:700;
                      text-transform:uppercase; letter-spacing:.07em; color:var(--text-muted); }
        .day-topic  { font-family:'Plus Jakarta Sans', sans-serif; font-size:1rem; font-weight:700;
                      color:var(--text); margin-bottom:3px; line-height:1.3; }
        .day-desc   { font-size:.75rem; color:var(--text-muted); margin-bottom:12px; }
        .day-status { font-size:.72rem; padding:3px 9px; border-radius:100px; font-weight:600;
                      white-space:nowrap; flex-shrink:0; }
        .ds-active    { background:rgba(0,232,122,.12);  color:var(--accent); }
        .ds-completed { background:rgba(0,232,122,.1);   color:var(--accent); }
        .ds-locked    { background:rgba(143,143,170,.12);color:var(--text-muted); }

        .day-progress { margin-bottom:12px; }
        .progress-bar { height:4px; background:var(--border); border-radius:100px; overflow:hidden; }
        .progress-fill{ height:100%; background:var(--accent); border-radius:100px;
                        transition:width .4s ease; }
        .progress-text { font-size:.75rem; color:var(--text-muted); margin-top:4px; }

        .day-problems { list-style:none; display:flex; flex-direction:column; gap:6px; }
        .day-problems li { display:flex; align-items:center; gap:8px; font-size:.87rem; }
        .day-problems li a { color:var(--text-dim); }
        .day-problems li a:hover { color:var(--accent); }
        .dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
        .dot-solved   { background:var(--accent); }
        .dot-unsolved { background:var(--border); }

        .no-problems { color:var(--text-muted); font-size:.85rem; }

        .progress-summary {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(160px,1fr));
            gap:16px; margin-bottom:32px;
        }
    </style>
</head>
<body>
<?php require_once 'includes/navbar.php'; ?>

<div class="page">
<div class="container">
    <div class="page-header fade-up">
        <h1>30-Day Roadmap</h1>
        <p>A sequential journey through algorithmic fundamentals.</p>
    </div>

    <div class="progress-summary fade-up fade-up-1" id="summary"></div>

    <div class="roadmap-grid fade-up fade-up-2" id="roadmap-grid">
        <?php for ($d = 1; $d <= 30; $d++): ?>
        <div class="day-card locked" id="day-<?= $d ?>">
            <div class="day-header">
                <div>
                    <div class="day-number">Day <?= $d ?></div>
                    <div class="day-topic">Loading…</div>
                </div>
                <span class="day-status ds-locked">Locked</span>
            </div>
            <div class="progress-bar"><div class="progress-fill" style="width:0"></div></div>
            <div class="progress-text" style="margin-top:4px">—</div>
        </div>
        <?php endfor; ?>
    </div>
</div>
</div>

<script src="/code-arena/assets/js/main.js"></script>
<script>
async function loadRoadmap() {
    const { ok, data } = await api('/code-arena/api/roadmap/progress.php');
    if (!ok || !data.success) { toast('Failed to load roadmap', 'error'); return; }

    const { days, unlocked_day, completed_days } = data.data;
    const totalDays = days.length;
    const completedCount = completed_days.length;

    // Summary
    document.getElementById('summary').innerHTML = `
        <div class="stat-card">
            <div class="stat-value">${completedCount}</div>
            <div class="stat-label">Days Completed</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">${unlocked_day}</div>
            <div class="stat-label">Current Day</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">${Math.round(completedCount/30*100)}%</div>
            <div class="stat-label">Progress</div>
        </div>`;

    days.forEach(day => {
        const card = document.getElementById(`day-${day.day}`);
        if (!card) return;

        const pct         = day.total > 0 ? Math.round(day.solved / day.total * 100) : 0;
        const statusClass = { active:'ds-active', completed:'ds-completed', locked:'ds-locked' }[day.status] || 'ds-locked';
        const statusLabel = { active:'Active', completed:'Done ✓', locked:'🔒' }[day.status] || '🔒';
        const allSolved   = day.total > 0 && day.solved === day.total;

        card.className = `day-card ${day.status}`;
        card.innerHTML = `
            <div class="day-header">
                <div>
                    <div class="day-number">Day ${day.day}</div>
                    <div class="day-topic">${day.topic}</div>
                </div>
                <span class="day-status ${statusClass}">${statusLabel}</span>
            </div>
            <div class="day-desc">${day.desc}</div>
            <div class="day-progress">
                <div class="progress-bar">
                    <div class="progress-fill" style="width:${pct}%"></div>
                </div>
                <div class="progress-text">${day.solved}/${day.total} solved</div>
            </div>
            ${day.problems.length === 0
                ? '<p class="no-problems">No problems assigned</p>'
                : `<ul class="day-problems" style="margin-top:10px">${day.problems.map(p => `
                    <li>
                        <span class="dot ${p.solved ? 'dot-solved' : 'dot-unsolved'}"></span>
                        ${day.status !== 'locked'
                            ? `<a href="/code-arena/problem.php?slug=${p.slug}">${p.title}</a>`
                            : `<span style="color:var(--text-muted)">${p.title}</span>`}
                        ${difficultyBadge(p.difficulty)}
                    </li>`).join('')}
                </ul>`}`;
    });
}

loadRoadmap();
</script>
</body>
</html>
