<?php
// ============================================================
//  CODE ARENA — Profile Page
// ============================================================
require_once 'includes/session.php';

$profileId = (int)($_GET['id'] ?? 0);
$username = trim($_GET['user'] ?? '');
if (!$username && !$profileId) {
    requireLogin();
    $username = currentUsername();
}
$isSelf = isLoggedIn() && (currentUsername() === $username);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($username ?: 'Profile') ?> — Code Arena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css">
    <style>
        .profile-header {
            display: flex; gap: 28px; align-items: center; margin-bottom: 36px;
        }
        .profile-avatar {
            width: 80px; height: 80px; border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--blue));
            display: flex; align-items: center; justify-content: center;
            font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 2.2rem;
            color: #0a0a0f; flex-shrink: 0;
        }
        .profile-info h2 { font-size: 1.6rem; margin-bottom: 4px; }
        .profile-info p  { color: var(--text-muted); font-size: .9rem; }

        .ratings-row { display: grid; grid-template-columns: repeat(2,1fr); gap: 16px; margin-bottom: 32px; }
        .rating-card { background:var(--bg-card); border:1px solid var(--border);
                       border-radius:var(--radius); padding:20px; }
        .rating-label { font-size:.75rem; font-weight:600; text-transform:uppercase;
                        letter-spacing:.06em; color:var(--text-muted); margin-bottom:8px; }
        .rating-value { font-family:'Syne',sans-serif; font-size:2.8rem; font-weight:800; line-height:1; }
        .hc-value { color:var(--red); }
        .lr-value { color:var(--blue); }
        .rating-sub { font-size:.8rem; color:var(--text-muted); margin-top:6px; }

        .diff-bar { display:flex; gap:12px; align-items:center; margin-bottom:8px; }
        .diff-bar .label { width:60px; font-size:.85rem; color:var(--text-dim); }
        .diff-bar .bar   { flex:1; height:8px; background:var(--border); border-radius:4px; overflow:hidden; }
        .diff-bar .fill  { height:100%; border-radius:4px; transition:width .5s; }
        .fill-easy   { background:var(--accent); }
        .fill-medium { background:var(--yellow); }
        .fill-hard   { background:var(--red); }
        .diff-bar .cnt { width:28px; text-align:right; font-size:.82rem; color:var(--text-muted); }

        .section-title { font-family:'Syne',sans-serif; font-size:.95rem; font-weight:700;
                         margin-bottom:16px; padding-bottom:10px; border-bottom:1px solid var(--border); }
        .recent-list { display:flex; flex-direction:column; gap:8px; }
        .recent-item { display:flex; align-items:center; gap:12px; padding:10px 14px;
                       background:var(--bg-card2); border-radius:var(--radius-sm);
                       font-size:.87rem; }
        .recent-item a { flex:1; color:var(--text-dim); }
        .recent-item a:hover { color:var(--accent); }

        .rating-history { display:flex; flex-direction:column; gap:4px; }
        .rh-item { display:flex; gap:10px; align-items:center; font-size:.82rem;
                   padding:6px 10px; border-radius:var(--radius-sm); }
        .rh-item:nth-child(odd) { background:var(--bg-card2); }
        .rh-delta-pos { color:var(--accent); font-weight:600; }
        .rh-delta-neg { color:var(--red);   font-weight:600; }

        .two-col { display:grid; grid-template-columns:1fr 1fr; gap:24px; }
        @media(max-width:700px) { .two-col { grid-template-columns:1fr; } }

        .weak-areas-grid { display:flex; flex-direction:column; gap:10px; }
        .weak-row { display:grid; grid-template-columns:140px 1fr 56px 72px; gap:12px;
                    align-items:center; font-size:.875rem; }
        .weak-tag { color:var(--text-dim); font-weight:500; text-transform:capitalize;
                    white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .weak-bar-wrap { height:7px; background:var(--border); border-radius:4px; overflow:hidden; }
        .weak-bar-fill { height:100%; border-radius:4px;
                         background: linear-gradient(90deg, var(--red), #ff8f4f); }
        .weak-pct { text-align:right; font-weight:600; color:var(--red); font-size:.82rem; }
        .weak-attempts { text-align:right; color:var(--text-muted); font-size:.78rem; }
        .weak-empty { color:var(--text-muted); font-size:.88rem; text-align:center; padding:20px 0; }
        .weak-header-row { display:grid; grid-template-columns:140px 1fr 56px 72px; gap:12px;
                           font-size:.72rem; font-weight:600; text-transform:uppercase;
                           letter-spacing:.05em; color:var(--text-muted); margin-bottom:4px; }
        .analytics-grid { display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:24px; }
        @media(max-width:800px) { .analytics-grid { grid-template-columns:1fr; } }
        .activity-grid { display:grid; grid-template-columns:repeat(7, 1fr); gap:6px; }
        .activity-cell {
            aspect-ratio:1; border-radius:4px; background:var(--bg-card2);
            border:1px solid var(--border);
        }
        .activity-cell.l1 { background:rgba(0,232,122,.18); border-color:rgba(0,232,122,.22); }
        .activity-cell.l2 { background:rgba(0,232,122,.34); border-color:rgba(0,232,122,.34); }
        .activity-cell.l3 { background:rgba(0,232,122,.56); border-color:rgba(0,232,122,.48); }
        .lang-row { display:grid; grid-template-columns:92px 1fr 64px; gap:10px; align-items:center; margin-bottom:10px; }
        .lang-name { color:var(--text-dim); font-size:.85rem; font-family:'JetBrains Mono',monospace; }
        .lang-bar { height:8px; background:var(--border); border-radius:6px; overflow:hidden; }
        .lang-fill { height:100%; background:linear-gradient(90deg,var(--blue),var(--accent)); border-radius:6px; }
        .lang-count { text-align:right; color:var(--text-muted); font-size:.78rem; }
        .contest-mini { display:flex; flex-direction:column; gap:10px; }
        .contest-mini-row { padding:10px 12px; border:1px solid var(--border); border-radius:var(--radius-sm); background:var(--bg-card2); }
        .contest-mini-row a { color:var(--text); font-weight:600; }
        .contest-mini-row a:hover { color:var(--accent); }
        .contest-mini-meta { margin-top:4px; font-size:.78rem; color:var(--text-muted); display:flex; gap:10px; flex-wrap:wrap; }
    </style>
</head>
<body>
<?php require_once 'includes/navbar.php'; ?>

<div class="page">
<div class="container">

    <div class="profile-header fade-up" id="profile-header">
        <div class="profile-avatar" id="avatar-letter">?</div>
        <div class="profile-info" id="profile-info">
            <h2><?= htmlspecialchars($username ?: 'Profile') ?></h2>
            <p>Loading…</p>
        </div>
    </div>

    <div class="ratings-row fade-up fade-up-1">
        <div class="rating-card">
            <div class="rating-label">Hardcore Rating</div>
            <div class="rating-value hc-value" id="hc-rating">—</div>
            <div class="rating-sub">Solved without hints</div>
        </div>
        <div class="rating-card">
            <div class="rating-label">Learning Rating</div>
            <div class="rating-value lr-value" id="lr-rating">—</div>
            <div class="rating-sub">All accepted submissions</div>
        </div>
    </div>

    <div class="stats-row fade-up fade-up-2" id="stats-row"></div>

    <div style="margin-bottom:28px;padding:20px;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius)" id="diff-section">
        <div class="section-title">Solved by Difficulty</div>
        <div id="diff-bars"></div>
    </div>

    <div class="card fade-up" style="margin-bottom:24px" id="weak-section">
        <div class="section-title" style="display:flex;align-items:center;gap:10px">
            Weak Areas
            <span style="font-size:.75rem;font-weight:400;color:var(--text-muted)">Topics you struggle with most</span>
        </div>
        <div id="weak-areas-body"><p class="weak-empty">Loading…</p></div>
    </div>

    <div class="analytics-grid">
        <div class="card fade-up">
            <div class="section-title">Activity</div>
            <div class="activity-grid" id="activity-grid"></div>
            <p style="font-size:.78rem;color:var(--text-muted);margin-top:10px">Last 35 days, darker means more submissions.</p>
        </div>
        <div class="card fade-up">
            <div class="section-title">Language Usage</div>
            <div id="language-stats"><p style="color:var(--text-muted);font-size:.88rem">Loading...</p></div>
        </div>
    </div>

    <div class="card fade-up" style="margin-bottom:24px">
        <div class="section-title">Contest Performance</div>
        <div class="contest-mini" id="contest-stats"><p style="color:var(--text-muted);font-size:.88rem">Loading...</p></div>
    </div>

    <div class="two-col">
        <div class="card fade-up fade-up-3">
            <div class="section-title">Recent Submissions</div>
            <div class="recent-list" id="recent-list"><p style="color:var(--text-muted)">Loading…</p></div>
        </div>
        <div class="card fade-up fade-up-4">
            <div class="section-title">Rating History</div>
            <div class="rating-history" id="rating-history"><p style="color:var(--text-muted)">Loading…</p></div>
        </div>
    </div>

</div>
</div>

<script src="/code-arena/assets/js/main.js"></script>
<script>
const USERNAME = '<?= addslashes($username) ?>';
const PROFILE_ID = <?= $profileId ?: 'null' ?>;

async function loadProfile() {
    const profileUrl = PROFILE_ID
        ? `/code-arena/api/users/profile.php?id=${PROFILE_ID}`
        : `/code-arena/api/users/profile.php?username=${encodeURIComponent(USERNAME)}`;
    const { ok, data } = await api(profileUrl);
    if (!ok || !data.success) { toast('Failed to load profile', 'error'); return; }

    const { user, stats, solved_by_diff, recent, rating_history, weak_areas, language_stats, activity, contest_stats } = data.data;

    // Avatar
    document.getElementById('avatar-letter').textContent = user.username[0].toUpperCase();
    document.getElementById('profile-info').innerHTML = `
        <h2>${user.username}</h2>
        <p style="color:var(--text-muted)">${user.role} · Joined ${new Date(user.created_at).toLocaleDateString('en-US',{year:'numeric',month:'long'})}</p>`;

    // Ratings
    document.getElementById('hc-rating').textContent = user.hardcore_rating ?? 1200;
    document.getElementById('lr-rating').textContent = user.learning_rating ?? 1200;

    // Stats
    const accepted = Number(stats.accepted || 0);
    const totalSubs = Number(stats.total || 0);
    const acceptanceRate = totalSubs ? Math.round(accepted / totalSubs * 100) : 0;
    document.getElementById('stats-row').innerHTML = `
        <div class="stat-card"><div class="stat-value">${stats.problems_solved||0}</div><div class="stat-label">Solved</div></div>
        <div class="stat-card"><div class="stat-value">${stats.total||0}</div><div class="stat-label">Submissions</div></div>
        <div class="stat-card"><div class="stat-value">${stats.accepted||0}</div><div class="stat-label">Accepted</div></div>
        <div class="stat-card"><div class="stat-value">${acceptanceRate}%</div><div class="stat-label">Acceptance</div></div>`;

    // Difficulty bars
    const maxSolved = Math.max(1, solved_by_diff.Easy + solved_by_diff.Medium + solved_by_diff.Hard);
    document.getElementById('diff-bars').innerHTML = [
        { key:'Easy',   cls:'fill-easy',   val: solved_by_diff.Easy   || 0 },
        { key:'Medium', cls:'fill-medium', val: solved_by_diff.Medium || 0 },
        { key:'Hard',   cls:'fill-hard',   val: solved_by_diff.Hard   || 0 },
    ].map(({ key, cls, val }) => `
        <div class="diff-bar">
            <span class="label">${key}</span>
            <div class="bar"><div class="fill ${cls}" style="width:${Math.round(val/maxSolved*100)}%"></div></div>
            <span class="cnt">${val}</span>
        </div>`).join('');

    // Recent submissions
    if (recent.length === 0) {
        document.getElementById('recent-list').innerHTML = '<p style="color:var(--text-muted);font-size:.88rem">No submissions yet.</p>';
    } else {
        document.getElementById('recent-list').innerHTML = recent.map(s => `
            <div class="recent-item">
                <a href="/code-arena/problem.php?slug=${s.problem_slug}">${s.problem_title}</a>
                ${statusBadge(s.status)}
                <span style="font-size:.75rem;color:var(--text-muted)">${timeAgo(s.submitted_at)}</span>
            </div>`).join('');
    }

    // Weak areas
    const weakEl = document.getElementById('weak-areas-body');
    if (!weak_areas || weak_areas.length === 0) {
        weakEl.innerHTML = `<p class="weak-empty">Not enough data yet — solve more problems across different topics to see weak areas.</p>`;
    } else {
        weakEl.innerHTML = `
        <div class="weak-header-row">
            <div>Topic</div><div>Failure rate</div><div>Rate</div><div>Attempts</div>
        </div>` +
        weak_areas.map(w => `
        <div class="weak-row">
            <div class="weak-tag">${w.tag.replace(/-/g,' ')}</div>
            <div class="weak-bar-wrap"><div class="weak-bar-fill" style="width:${w.failure_rate}%"></div></div>
            <div class="weak-pct">${w.failure_rate}%</div>
            <div class="weak-attempts">${w.failures}/${w.attempts} failed</div>
        </div>`).join('');
    }

    renderActivity(activity || []);
    renderLanguages(language_stats || []);
    renderContestStats(contest_stats || []);

    // Rating history
    if (rating_history.length === 0) {
        document.getElementById('rating-history').innerHTML = '<p style="color:var(--text-muted);font-size:.88rem">No rating changes yet.</p>';
    } else {
        document.getElementById('rating-history').innerHTML = rating_history.map(r => {
            const sign = r.delta > 0 ? '+' : '';
            const cls  = r.delta > 0 ? 'rh-delta-pos' : 'rh-delta-neg';
            const typeColor = r.rating_type === 'hardcore' ? 'var(--red)' : 'var(--blue)';
            return `<div class="rh-item">
                <span style="font-size:.75rem;color:${typeColor};font-weight:600;text-transform:uppercase">${r.rating_type.slice(0,2)}</span>
                <span style="flex:1;color:var(--text-muted)">${r.old_rating} → ${r.new_rating}</span>
                <span class="${cls}">${sign}${r.delta}</span>
                <span style="font-size:.72rem;color:var(--text-muted)">${timeAgo(r.changed_at)}</span>
            </div>`;
        }).join('');
    }
}

function renderActivity(activity) {
    const map = {};
    activity.forEach(a => { map[a.day] = Number(a.submissions || 0); });
    const today = new Date();
    const cells = [];
    for (let i = 34; i >= 0; i--) {
        const d = new Date(today);
        d.setDate(today.getDate() - i);
        const key = d.toISOString().slice(0, 10);
        const count = map[key] || 0;
        const level = count >= 5 ? 3 : (count >= 2 ? 2 : (count >= 1 ? 1 : 0));
        cells.push(`<div class="activity-cell l${level}" title="${key}: ${count} submissions"></div>`);
    }
    document.getElementById('activity-grid').innerHTML = cells.join('');
}

function renderLanguages(rows) {
    const el = document.getElementById('language-stats');
    if (!rows.length) {
        el.innerHTML = '<p style="color:var(--text-muted);font-size:.88rem">No language data yet.</p>';
        return;
    }
    const max = Math.max(...rows.map(r => Number(r.submissions || 0)), 1);
    el.innerHTML = rows.map(r => {
        const subs = Number(r.submissions || 0);
        const ac = Number(r.accepted || 0);
        return `
        <div class="lang-row">
            <div class="lang-name">${r.language}</div>
            <div class="lang-bar"><div class="lang-fill" style="width:${Math.round(subs / max * 100)}%"></div></div>
            <div class="lang-count">${ac}/${subs} AC</div>
        </div>`;
    }).join('');
}

function renderContestStats(rows) {
    const el = document.getElementById('contest-stats');
    if (!rows.length) {
        el.innerHTML = '<p style="color:var(--text-muted);font-size:.88rem">No contest participation yet.</p>';
        return;
    }
    el.innerHTML = rows.map(c => `
        <div class="contest-mini-row">
            <a href="/code-arena/contest.php?id=${c.id}">${c.title}</a>
            <div class="contest-mini-meta">
                <span>${c.status}</span>
                <span>${c.solved || 0}/${c.attempted || 0} solved</span>
                <span>${c.last_submission_at ? timeAgo(c.last_submission_at) : 'registered'}</span>
            </div>
        </div>
    `).join('');
}

loadProfile();
</script>
</body>
</html>
