<?php
require_once 'includes/session.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - CodeArena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css?v=20260615-ui5">
    <style>
        .announcement-layout { display:grid; gap:18px; }
        .announcement-tabs { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px; }
        .announcement-tabs button {
            border:1px solid var(--border); border-radius:999px; padding:8px 14px;
            background:rgba(255,255,255,.035); color:var(--text-dim); font-weight:800;
        }
        .announcement-tabs button.active,
        .announcement-tabs button:hover {
            color:#fff; border-color:rgba(124,58,237,.55); background:rgba(124,58,237,.22);
        }
        .announcement-card { display:grid; gap:10px; }
        .announcement-meta { display:flex; gap:10px; flex-wrap:wrap; color:var(--text-muted); font-size:.82rem; }
        .announcement-message { color:var(--text-dim); white-space:pre-wrap; line-height:1.75; }
        .scope-badge {
            display:inline-flex; align-items:center; padding:4px 9px; border-radius:999px;
            background:var(--accent-dim); color:#c59bff; font-size:.72rem; font-weight:800;
            text-transform:uppercase; letter-spacing:.04em;
        }
    </style>
</head>
<body>
<?php require_once 'includes/navbar.php'; ?>

<main class="page">
    <div class="container">
        <header class="page-header">
            <h1>Announcements</h1>
            <p>Latest platform and organization updates.</p>
        </header>

        <div class="announcement-tabs" aria-label="Announcement filters">
            <button class="active" data-filter="all" onclick="setAnnouncementFilter('all')">All</button>
            <button data-filter="global" onclick="setAnnouncementFilter('global')">Global</button>
            <button data-filter="org" onclick="setAnnouncementFilter('org')">Organization</button>
        </div>

        <section class="announcement-layout" id="announcement-list">
            <article class="card">Loading announcements...</article>
        </section>
    </div>
</main>

<script src="/code-arena/assets/js/main.js?v=20260615-ui5"></script>
<script>
let announcementFilter = 'all';

function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
}

function setAnnouncementFilter(filter) {
    announcementFilter = filter;
    document.querySelectorAll('.announcement-tabs button').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.filter === filter);
    });
    loadAnnouncements();
}

function scopeLabel(row) {
    if (row.target_type === 'global') return 'Global';
    if (row.target_type === 'contest') return row.contest_title ? `Contest: ${row.contest_title}` : 'Contest';
    return row.organization_name ? `Organization: ${row.organization_name}` : 'Organization';
}

async function loadAnnouncements() {
    const list = document.getElementById('announcement-list');
    const { ok, data } = await api(`/code-arena/api/announcements/index.php?filter=${encodeURIComponent(announcementFilter)}&mark_read=1`);
    if (!ok || !data.success) {
        list.innerHTML = `<article class="card">${esc(data.message || 'Failed to load announcements')}</article>`;
        return;
    }
    const badge = document.getElementById('announcement-count-badge');
    if (badge) {
        badge.style.display = 'none';
        badge.textContent = '';
    }

    const rows = data.data.announcements || [];
    list.innerHTML = rows.length ? rows.map(row => `
        <article class="card announcement-card">
            <div style="display:flex;justify-content:space-between;gap:14px;align-items:flex-start">
                <div>
                    <h2 style="font-size:1.12rem;margin:0 0 8px">${esc(row.title)}</h2>
                    <div class="announcement-meta">
                        <span class="scope-badge">${esc(scopeLabel(row))}</span>
                        <span>${timeAgo(row.created_at)}</span>
                        ${row.creator_username ? `<span>by ${esc(row.creator_username)}</span>` : ''}
                    </div>
                </div>
            </div>
            <div class="announcement-message">${esc(row.message)}</div>
        </article>
    `).join('') : '<article class="card">No announcements found.</article>';
}

loadAnnouncements();
setInterval(loadAnnouncements, 20000);
</script>
</body>
</html>
