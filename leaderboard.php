<?php
// ============================================================
//  CODE ARENA - Dual Leaderboard
// ============================================================
require_once 'includes/session.php';
require_once 'config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - Code Arena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css?v=20260615-ui5">
    <style>
        .board-toolbar { display:flex; gap:10px; margin-bottom:18px; flex-wrap:wrap; }
        .board-btn { padding:9px 14px; border-radius:var(--radius-sm); border:1px solid var(--border);
                     background:var(--bg-card); color:var(--text-dim); cursor:pointer; }
        .board-btn.active, .board-btn:hover { border-color:var(--accent); color:var(--accent); }
        .board-mode { display:flex; gap:10px; margin-bottom:14px; flex-wrap:wrap; }
        .rank-cell { font-weight:700; color:var(--text); }
        .rank-top { color:var(--yellow); }
        .user-link { color:var(--text); font-weight:600; }
        .user-link:hover { color:var(--accent); }
        .metric-strong { color:var(--accent); font-weight:700; }
        .delta-pos { color:var(--accent); font-weight:700; }
        .delta-neg { color:var(--red); font-weight:700; }
        .board-note { color:var(--text-muted); font-size:.86rem; margin:0 0 16px; }
    </style>
</head>
<body>
<?php require_once 'includes/navbar.php'; ?>

<div class="page">
<div class="container">
    <div class="page-header fade-up">
        <h1>Leaderboard</h1>
        <p>Practice ranking and contest ranking are tracked separately.</p>
    </div>

    <div class="board-mode fade-up fade-up-1">
        <button class="board-btn active" data-mode="practice" onclick="setMode('practice', this)">Practice Leaderboard</button>
        <button class="board-btn" data-mode="contest" onclick="setMode('contest', this)">Contest Leaderboard</button>
    </div>

    <div class="board-toolbar fade-up fade-up-1" id="practice-toolbar">
        <button class="board-btn active" data-sort="rating" onclick="loadPractice('rating', this)">Skill Rating</button>
        <button class="board-btn" data-sort="solved" onclick="loadPractice('solved', this)">Solved</button>
        <button class="board-btn" data-sort="accuracy" onclick="loadPractice('accuracy', this)">Accuracy</button>
        <button class="board-btn" data-sort="streak" onclick="loadPractice('streak', this)">Streak</button>
    </div>

    <p class="board-note" id="board-note">Practice leaderboard is based on non-contest problem solving activity.</p>

    <div class="table-wrap fade-up fade-up-2">
        <table>
            <thead id="leaderboard-head"></thead>
            <tbody id="leaderboard-body">
                <tr><td colspan="7" style="text-align:center;padding:36px;color:var(--text-muted)">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>
</div>

<script src="/code-arena/assets/js/main.js?v=20260615-ui5"></script>
<script>
let boardMode = 'practice';

function setMode(mode, btn) {
    boardMode = mode;
    document.querySelectorAll('[data-mode]').forEach(b => b.classList.toggle('active', b === btn));
    document.getElementById('practice-toolbar').style.display = mode === 'practice' ? 'flex' : 'none';
    if (mode === 'practice') loadPractice('rating');
    else loadContest();
}

async function loadPractice(sort = 'rating', btn = null) {
    document.querySelectorAll('#practice-toolbar .board-btn').forEach(b => b.classList.toggle('active', b === btn || b.dataset.sort === sort));
    document.getElementById('board-note').textContent = 'Skill leaderboard is based on non-contest problem solving activity.';
    document.getElementById('leaderboard-head').innerHTML = `
        <tr>
            <th>Rank</th><th>User</th><th>Skill Rating</th><th>Solved</th><th>Accuracy</th><th>Streak</th><th>Last Active</th>
        </tr>`;

    const { ok, data } = await api(`/code-arena/api/leaderboard/index.php?type=practice&sort=${encodeURIComponent(sort)}`);
    const body = document.getElementById('leaderboard-body');
    if (!ok || !data.success) {
        body.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:36px;color:var(--red)">Failed to load leaderboard.</td></tr>';
        return;
    }

    const users = data.data.users;
    if (!users.length) {
        body.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:36px;color:var(--text-muted)">No practice stats yet.</td></tr>';
        return;
    }

    body.innerHTML = users.map(u => `
        <tr>
            <td class="rank-cell ${u.rank <= 3 ? 'rank-top' : ''}">#${u.rank}</td>
            <td><a class="user-link" href="/code-arena/profile.php?id=${u.id}">${escHtml(u.username)}</a></td>
            <td class="metric-strong">${u.skill_rating}</td>
            <td>${u.total_solved}</td>
            <td>${Number(u.accuracy).toFixed(1)}%</td>
            <td>${u.streak_days}d</td>
            <td>${u.last_active_date || '-'}</td>
        </tr>
    `).join('');
}

async function loadContest() {
    document.getElementById('board-note').textContent = 'Contest leaderboard is a locked snapshot after contests end.';
    document.getElementById('leaderboard-head').innerHTML = `
        <tr>
            <th>Rank</th><th>User</th><th>Score</th><th>Penalty</th><th>Solved</th><th>Rating Change</th><th>Snapshot</th>
        </tr>`;

    const { ok, data } = await api('/code-arena/api/leaderboard/index.php?type=contest');
    const body = document.getElementById('leaderboard-body');
    if (!ok || !data.success) {
        body.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:36px;color:var(--red)">Failed to load contest leaderboard.</td></tr>';
        return;
    }

    const rows = data.data.rows || [];
    if (!rows.length) {
        body.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:36px;color:var(--text-muted)">No finalized contest leaderboard yet.</td></tr>';
        return;
    }

    body.innerHTML = rows.map(row => {
        const delta = Number(row.rating_change || 0);
        return `
            <tr>
                <td class="rank-cell ${row.rank <= 3 ? 'rank-top' : ''}">#${row.rank}</td>
                <td><a class="user-link" href="/code-arena/profile.php?id=${row.user_id}">${escHtml(row.username)}</a></td>
                <td class="metric-strong">${row.score}</td>
                <td>${row.penalty}m</td>
                <td>${row.solved_count}</td>
                <td class="${delta >= 0 ? 'delta-pos' : 'delta-neg'}">${delta >= 0 ? '+' : ''}${delta}</td>
                <td>${new Date(row.created_at.replace(' ', 'T')).toLocaleDateString()}</td>
            </tr>
        `;
    }).join('');
}

function escHtml(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

loadPractice();
</script>
</body>
</html>
