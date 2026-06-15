<?php
require_once '../includes/session.php';
require_once '../config/db.php';
require_once '../includes/organization.php';

$organization = requireOrganizationPage($pdo);
$pageTitle = 'Announcements';
$activeOrgPage = 'announcements';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - Code Arena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css?v=20260615-ui5">
</head>
<body>
<?php require_once '../includes/organization_shell.php'; ?>

<div class="org-content" style="display:grid;grid-template-columns:minmax(280px,420px) minmax(0,1fr);gap:18px">
    <section class="org-card">
        <h2 style="margin-bottom:16px">New Post</h2>
        <div class="form-group">
            <label class="form-label">Title</label>
            <input id="title" class="form-input">
        </div>

        <div class="form-group">
            <label class="form-label">Contest <span style="color:var(--text-muted);font-size:.78rem">(optional)</span></label>
            <div style="display:grid;grid-template-columns:minmax(90px,130px) minmax(0,1fr);gap:10px">
                <input id="contest_id" type="number" min="1" class="form-input" placeholder="ID" oninput="syncContestDropdownFromInput()">
                <select id="contest_select" class="form-input" onchange="selectAnnouncementContest()">
                    <option value="">All organization members</option>
                </select>
            </div>
            <div id="contest_hint" style="color:var(--text-muted);font-size:.78rem;margin-top:6px">
                Choose a contest to send only to that contest's participants.
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Type</label>
            <select id="type" class="form-input">
                <option value="announcement">Announcement</option>
                <option value="clarification">Clarification</option>
                <option value="instruction">Instruction</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Message</label>
            <textarea id="message" class="form-input" rows="7"></textarea>
        </div>

        <button class="btn-primary" onclick="createAnnouncement()">Publish</button>
    </section>

    <section class="org-card">
        <div class="org-toolbar">
            <input id="filter_contest" class="form-input" type="number" min="1" placeholder="Filter contest ID">
            <button class="btn-outline" onclick="loadAnnouncements()">Refresh</button>
        </div>
        <div id="announcement-list" class="org-grid"></div>
    </section>
</div>

<?php require_once '../includes/organization_shell_end.php'; ?>
<script src="/code-arena/assets/js/main.js?v=20260615-ui5"></script>
<script>
let orgContests = [];

function esc(value) {
    return String(value ?? '').replace(/[&<>"']/g, match => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[match]));
}

async function loadContestOptions() {
    const { ok, data } = await api('/code-arena/api/organization/contests.php');
    if (!ok || !data.success) return;

    orgContests = data.data.contests || [];
    contest_select.innerHTML = '<option value="">All organization members</option>' + orgContests.map(contest =>
        `<option value="${contest.id}">#${contest.id} - ${esc(contest.title)} (${esc(contest.org_status || contest.status || 'contest')})</option>`
    ).join('');
    syncContestDropdownFromInput();
}

function selectAnnouncementContest() {
    contest_id.value = contest_select.value || '';
    updateContestHint();
}

function syncContestDropdownFromInput() {
    const id = contest_id.value.trim();
    const exists = orgContests.some(contest => String(contest.id) === id);
    contest_select.value = exists ? id : '';
    updateContestHint();
}

function updateContestHint() {
    const id = contest_id.value.trim();
    const contest = orgContests.find(row => String(row.id) === id);
    contest_hint.textContent = contest
        ? `This announcement will go only to participants of "${contest.title}".`
        : "Choose a contest to send only to that contest's participants.";
}

async function loadAnnouncements() {
    const qs = new URLSearchParams();
    if (filter_contest.value) qs.set('contest_id', filter_contest.value);

    const { ok, data } = await api('/code-arena/api/organization/announcements.php?' + qs.toString());
    const list = document.getElementById('announcement-list');
    if (!ok || !data.success) {
        list.innerHTML = esc(data.message || 'Failed');
        return;
    }

    const rows = data.data.announcements || [];
    list.innerHTML = rows.length ? rows.map(row => `
        <article class="org-card">
            <div style="display:flex;justify-content:space-between;gap:12px">
                <div>
                    <strong>${esc(row.title)}</strong>
                    <div style="color:var(--text-muted);font-size:.85rem">
                        ${esc(row.type)}${row.contest_title ? ' - ' + esc(row.contest_title) : ''} - ${esc(row.created_at)}
                    </div>
                </div>
                <button class="btn-danger btn-sm" onclick="deleteAnnouncement(${row.id})">Delete</button>
            </div>
            <p style="margin-top:12px;white-space:pre-wrap">${esc(row.message)}</p>
        </article>
    `).join('') : 'No announcements yet.';
}

async function createAnnouncement() {
    const payload = {
        title: title.value.trim(),
        message: message.value.trim(),
        type: type.value,
        contest_id: contest_id.value ? Number(contest_id.value) : null,
        is_published: true,
    };

    const { ok, data } = await api('/code-arena/api/organization/announcements.php', {
        method: 'POST',
        body: JSON.stringify(payload),
    });
    toast(data.message || 'Saved', ok ? 'success' : 'error');
    if (ok) {
        title.value = '';
        message.value = '';
        contest_id.value = '';
        contest_select.value = '';
        updateContestHint();
        loadAnnouncements();
    }
}

async function deleteAnnouncement(id) {
    if (!confirm('Delete this announcement?')) return;
    const { ok, data } = await api('/code-arena/api/organization/announcements.php', {
        method: 'DELETE',
        body: JSON.stringify({ id }),
    });
    toast(data.message || 'Deleted', ok ? 'success' : 'error');
    if (ok) loadAnnouncements();
}

loadContestOptions();
loadAnnouncements();
</script>
</body>
</html>
