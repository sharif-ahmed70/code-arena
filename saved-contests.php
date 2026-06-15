<?php
// ============================================================
//  CODE ARENA - Saved Contests
// ============================================================
require_once 'includes/session.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Contests - Code Arena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css?v=20260615-ui2">
    <style>
        .saved-head { display:flex; justify-content:space-between; gap:18px; align-items:flex-end; margin-bottom:24px; }
        .saved-head p { margin-top:6px; color:var(--text-muted); }
        .saved-grid { display:grid; gap:12px; }
        .saved-card { display:grid; grid-template-columns:46px minmax(0,1fr) auto; gap:14px; align-items:center; padding:16px; border:1px solid var(--border); border-radius:var(--radius); background:var(--bg-card); }
        .platform-mark { width:42px; height:42px; display:grid; place-items:center; border-radius:13px; background:rgba(108,160,255,.12); color:var(--blue); font-weight:850; }
        .saved-card h3 { margin:0; font-size:1rem; }
        .saved-card p { margin:5px 0 0; color:var(--text-muted); font-size:.84rem; }
        .empty { padding:28px; border:1px dashed var(--border); border-radius:var(--radius); color:var(--text-muted); text-align:center; }
        @media(max-width:640px) { .saved-head { align-items:flex-start; flex-direction:column; } .saved-card { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<?php require_once 'includes/navbar.php'; ?>
<div class="page">
<div class="container">
    <div class="saved-head fade-up">
        <div>
            <h1>Saved Contests</h1>
            <p>Your locally saved contest queue for quick planning.</p>
        </div>
        <a class="btn-primary" href="/code-arena/contests.php">Back to contests</a>
    </div>

    <section class="saved-grid fade-up fade-up-1" id="saved-contest-list">
        <div class="empty">Loading saved contests...</div>
    </section>
</div>
</div>

<script src="/code-arena/assets/js/main.js"></script>
<script>
const SAVE_KEY = 'code_arena_saved_contests';
const demoContests = [
    { id: 'demo-1', title: 'Codeforces Round 1050', platform: 'Codeforces', start_time: '2026-06-16 18:00:00' },
    { id: 'demo-2', title: 'LeetCode Weekly Contest 420', platform: 'LeetCode', start_time: '2026-06-16 10:00:00' },
    { id: 'demo-3', title: 'CodeChef Starter 120', platform: 'CodeChef', start_time: '2026-06-18 20:00:00' },
    { id: 'demo-4', title: 'AtCoder Beginner Contest 350', platform: 'AtCoder', start_time: '2026-06-20 12:00:00' },
    { id: 'demo-5', title: 'HackerRank Weekly Challenge', platform: 'HackerRank', start_time: '2026-06-20 15:00:00' }
];

function escHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, ch => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[ch]));
}

function savedIds() {
    try { return JSON.parse(localStorage.getItem(SAVE_KEY) || '[]').map(String); }
    catch (_) { return []; }
}

function platformShort(platform) {
    const value = String(platform || '').toLowerCase();
    if (value.includes('codechef')) return 'CC';
    if (value.includes('leetcode')) return 'LC';
    if (value.includes('atcoder')) return 'AT';
    if (value.includes('hackerrank')) return 'HR';
    return 'CF';
}

async function fetchContests() {
    const endpoints = ['/code-arena/api/contests/list.php', '/code-arena/api/contests/index.php'];
    for (const endpoint of endpoints) {
        try {
            const { ok, data } = await api(endpoint);
            if (ok && data.success && Array.isArray(data.data)) {
                return data.data.map(row => ({ ...row, id: String(row.id) }));
            }
        } catch (_) {}
    }
    return [];
}

async function renderSavedContests() {
    const ids = savedIds();
    const rows = [...await fetchContests(), ...demoContests];
    const saved = rows.filter(contest => ids.includes(String(contest.id)));
    const list = document.getElementById('saved-contest-list');

    if (!ids.length || !saved.length) {
        list.innerHTML = '<div class="empty">No saved contests yet. Save contests from the Contest Tracker page.</div>';
        return;
    }

    list.innerHTML = saved.map(contest => {
        const isDemo = String(contest.id).startsWith('demo-');
        const href = isDemo ? '/code-arena/contests.php' : `/code-arena/contest.php?id=${encodeURIComponent(contest.id)}`;
        return `
            <article class="saved-card">
                <div class="platform-mark">${platformShort(contest.platform || contest.title)}</div>
                <div>
                    <h3>${escHtml(contest.title)}</h3>
                    <p>${escHtml(contest.platform || 'Code Arena')} - ${new Date(contest.start_time).toLocaleString()}</p>
                </div>
                <a class="btn-outline" href="${href}">${isDemo ? 'Open tracker' : 'View'}</a>
            </article>
        `;
    }).join('');
}

renderSavedContests();
</script>
</body>
</html>
