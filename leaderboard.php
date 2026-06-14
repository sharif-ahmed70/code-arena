<?php
// ============================================================
//  CODE ARENA - Leaderboard
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
    <link rel="stylesheet" href="/code-arena/assets/css/style.css">
    <style>
        .board-toolbar { display:flex; gap:10px; margin-bottom:18px; flex-wrap:wrap; }
        .board-btn { padding:9px 14px; border-radius:var(--radius-sm); border:1px solid var(--border);
                     background:var(--bg-card); color:var(--text-dim); cursor:pointer; }
        .board-btn.active, .board-btn:hover { border-color:var(--accent); color:var(--accent); }
        .rank-cell { font-weight:700; color:var(--text); }
        .rank-top { color:var(--yellow); }
        .user-link { color:var(--text); font-weight:600; }
        .user-link:hover { color:var(--accent); }
        .metric-strong { color:var(--accent); font-weight:700; }
    </style>
</head>
<body>
<?php require_once 'includes/navbar.php'; ?>

<div class="page">
<div class="container">
    <div class="page-header fade-up">
        <h1>Leaderboard</h1>
        <p>Track top performers by rating and solved problems.</p>
    </div>

    <div class="board-toolbar fade-up fade-up-1">
        <button class="board-btn active" data-sort="hardcore" onclick="loadBoard('hardcore', this)">Hardcore</button>
        <button class="board-btn" data-sort="learning" onclick="loadBoard('learning', this)">Learning</button>
        <button class="board-btn" data-sort="solved" onclick="loadBoard('solved', this)">Solved</button>
    </div>

    <div class="table-wrap fade-up fade-up-2">
        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>User</th>
                    <th>Hardcore</th>
                    <th>Learning</th>
                    <th>Solved</th>
                    <th>Submissions</th>
                    <th>Last Active</th>
                </tr>
            </thead>
            <tbody id="leaderboard-body">
                <tr><td colspan="7" style="text-align:center;padding:36px;color:var(--text-muted)">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>
</div>

<script src="/code-arena/assets/js/main.js"></script>
<script>
async function loadBoard(sort = 'hardcore', btn = null) {
    document.querySelectorAll('.board-btn').forEach(b => b.classList.toggle('active', b === btn || b.dataset.sort === sort));
    const { ok, data } = await api(`/code-arena/api/leaderboard/index.php?sort=${encodeURIComponent(sort)}`);
    const body = document.getElementById('leaderboard-body');

    if (!ok || !data.success) {
        body.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:36px;color:var(--red)">Failed to load leaderboard.</td></tr>';
        return;
    }

    const users = data.data.users;
    if (!users.length) {
        body.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:36px;color:var(--text-muted)">No ranked users yet.</td></tr>';
        return;
    }

    body.innerHTML = users.map(u => `
        <tr>
            <td class="rank-cell ${u.rank <= 3 ? 'rank-top' : ''}">#${u.rank}</td>
            <td><a class="user-link" href="/code-arena/profile.php?id=${u.id}">${escHtml(u.username)}</a></td>
            <td>${u.hardcore_rating}</td>
            <td>${u.learning_rating}</td>
            <td><span class="metric-strong">${u.solved_count}</span></td>
            <td>${u.submission_count}</td>
            <td>${u.last_submission_at ? new Date(u.last_submission_at.replace(' ', 'T')).toLocaleDateString() : '-'}</td>
        </tr>
    `).join('');
}

function escHtml(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

loadBoard();
</script>
</body>
</html>
