<?php
// ============================================================
//  CODE ARENA — Contests Page
// ============================================================
require_once 'includes/session.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contests — Code Arena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css">
    <style>
        .contest-tabs { display:flex; gap:4px; margin-bottom:24px; border-bottom:1px solid var(--border); }
        .ctab { padding:10px 20px; background:none; border:none; border-bottom:2px solid transparent;
                color:var(--text-muted); font-family:inherit; font-size:.9rem; font-weight:500;
                cursor:pointer; margin-bottom:-1px; transition:color .2s,border-color .2s; }
        .ctab.active { color:var(--accent); border-bottom-color:var(--accent); }

        .contests-grid { display:flex; flex-direction:column; gap:14px; }
        .contest-card {
            background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius);
            padding:22px 24px; display:flex; gap:20px; align-items:center;
            transition:border-color .2s, transform .2s;
        }
        .contest-card:hover { border-color:rgba(79,126,248,.35); transform:translateY(-2px); }
        .contest-card.active-contest { border-color:rgba(0,255,136,.3); }
        .contest-status { width:80px; text-align:center; flex-shrink:0; }
        .cs-badge { padding:4px 10px; border-radius:100px; font-size:.75rem; font-weight:600; }
        .cs-active   { background:rgba(0,255,136,.12);  color:var(--accent); }
        .cs-upcoming { background:rgba(79,126,248,.12); color:var(--blue); }
        .cs-ended    { background:rgba(107,107,138,.12);color:var(--text-muted); }
        .contest-info { flex:1; min-width:0; }
        .contest-info h3 { margin-bottom:4px; font-size:1rem; }
        .contest-info h3 a { color:var(--text); }
        .contest-info h3 a:hover { color:var(--accent); }
        .contest-meta { display:flex; gap:16px; font-size:.82rem; color:var(--text-muted); flex-wrap:wrap; }
        .contest-actions { flex-shrink:0; }
        .countdown { font-family:'JetBrains Mono',monospace; font-size:.9rem; color:var(--accent); }

        .empty-state { text-align:center; padding:60px 20px; color:var(--text-muted); }

        /* delete confirm modal */
        .del-overlay {
            position:fixed; inset:0; background:rgba(0,0,0,.7);
            z-index:600; display:flex; align-items:center; justify-content:center;
        }
        .del-card {
            background:var(--bg-card); border:1px solid var(--border);
            border-radius:var(--radius); padding:36px 32px; text-align:center;
            max-width:380px; width:90%;
        }
        .del-card h3 { margin-bottom:10px; font-size:1.1rem; }
        .del-card p  { color:var(--text-muted); font-size:.88rem; margin-bottom:24px; }
        .del-card .del-actions { display:flex; gap:12px; justify-content:center; }
    </style>
</head>
<body>
<?php require_once 'includes/navbar.php'; ?>

<div class="page">
<div class="container">
    <div class="page-header fade-up" style="display:flex;justify-content:space-between;align-items:flex-end">
        <div>
            <h1>Contests</h1>
            <p>Compete in timed rated contests.</p>
        </div>
        <?php if (isInstructor()): ?>
        <button class="btn-primary" onclick="showCreateForm()">+ New Contest</button>
        <?php endif; ?>
    </div>

    <div class="contest-tabs fade-up fade-up-1">
        <button class="ctab active" onclick="loadContests('', event)">All</button>
        <button class="ctab" onclick="loadContests('active', event)">Active</button>
        <button class="ctab" onclick="loadContests('upcoming', event)">Upcoming</button>
        <button class="ctab" onclick="loadContests('ended', event)">Past</button>
    </div>

    <div class="contests-grid fade-up fade-up-2" id="contests-grid">
        <div class="empty-state"><p>Loading…</p></div>
    </div>

    <?php if (isInstructor()): ?>
    <!-- Create contest form -->
    <div id="create-form" style="display:none;margin-top:32px" class="card">
        <h3 style="margin-bottom:20px">Create Contest</h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <div class="form-group" style="grid-column:1/-1">
                <label class="form-label">Title *</label>
                <input type="text" id="ct-title" class="form-input" placeholder="Weekly Contest #1">
            </div>
            <div class="form-group">
                <label class="form-label">Start Time *</label>
                <input type="datetime-local" id="ct-start" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">End Time *</label>
                <input type="datetime-local" id="ct-end" class="form-input">
            </div>
            <div class="form-group" style="grid-column:1/-1">
                <label class="form-label">Problem IDs</label>
                <input type="text" id="ct-problems" class="form-input" placeholder="Example: 1, 2, 3, 4">
                <p style="font-size:.78rem;color:var(--text-muted);margin-top:6px">
                    Add public problem IDs separated by commas. Each problem starts at 100 points.
                </p>
            </div>
            <div class="form-group" style="grid-column:1/-1">
                <label class="form-label">Description</label>
                <textarea id="ct-desc" class="form-input" rows="3" placeholder="Contest description…"></textarea>
            </div>
        </div>
        <div style="display:flex;gap:12px;align-items:center;margin-top:8px">
            <button class="btn-primary" onclick="createContest()">Create</button>
            <button class="btn-outline" onclick="document.getElementById('create-form').style.display='none'">Cancel</button>
            <span id="ct-msg" style="font-size:.88rem"></span>
        </div>
    </div>
    <?php endif; ?>
</div>
</div>

<!-- Delete confirmation modal (admin only) -->
<?php if (isAdmin()): ?>
<div class="del-overlay" id="del-overlay" style="display:none" onclick="closeDeleteModal(event)">
    <div class="del-card">
        <h3>Delete Contest?</h3>
        <p>This cannot be undone. All problems, registrations, and results for this contest will be removed.</p>
        <div class="del-actions">
            <button class="btn-outline" onclick="closeDeleteModal()">Cancel</button>
            <button class="btn-primary" id="del-confirm-btn"
                    style="background:var(--red,#e05);border-color:var(--red,#e05)"
                    onclick="confirmDelete()">Delete</button>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="/code-arena/assets/js/main.js"></script>
<script>
const IS_ADMIN = <?= isAdmin() ? 'true' : 'false' ?>;
const CAN_MANAGE = <?= isInstructor() ? 'true' : 'false' ?>;

async function loadContests(status = '', event = null) {
    if (event) {
        document.querySelectorAll('.ctab').forEach(b => b.classList.remove('active'));
        event.target.classList.add('active');
    }

    const params = status ? `?status=${status}` : '';
    const { ok, data } = await api(`/code-arena/api/contests/index.php${params}`);
    const grid = document.getElementById('contests-grid');

    if (!ok || !data.success) {
        grid.innerHTML = `<div class="empty-state"><p>${data.message || 'Failed to load'}</p></div>`;
        return;
    }

    const contests = data.data;
    if (!contests.length) {
        grid.innerHTML = `<div class="empty-state"><p>No contests found.</p></div>`;
        return;
    }

    grid.innerHTML = contests.map(c => {
        const statusCls = { active:'cs-active', upcoming:'cs-upcoming', ended:'cs-ended' }[c.status] || 'cs-ended';
        const statusLabel = { active:'Live 🔴', upcoming:'Soon', ended:'Ended' }[c.status] || c.status;
        const cardCls = c.status === 'active' ? 'contest-card active-contest' : 'contest-card';
        const start = new Date(c.start_time);
        const end   = new Date(c.end_time);
        const dur   = Math.round((end - start) / 60000);
        return `
        <div class="${cardCls}">
            <div class="contest-status">
                <div class="cs-badge ${statusCls}">${statusLabel}</div>
            </div>
            <div class="contest-info">
                <h3><a href="/code-arena/contest.php?id=${c.id}">${c.title}</a></h3>
                <div class="contest-meta">
                    <span>by ${c.author}</span>
                    <span>${start.toLocaleDateString('en-US',{month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'})}</span>
                    <span>${dur >= 60 ? Math.round(dur/60)+'h' : dur+'min'} duration</span>
                    <span>${c.participant_count} participants</span>
                    ${c.is_rated ? '<span style="color:var(--accent)">Rated</span>' : ''}
                </div>
            </div>
            <div class="contest-actions" style="display:flex;gap:8px;align-items:center">
                ${c.status !== 'ended'
                    ? `<a href="/code-arena/contest.php?id=${c.id}" class="btn-primary">
                        ${c.status === 'active' ? 'Enter' : 'Register'}</a>`
                    : `<a href="/code-arena/contest.php?id=${c.id}" class="btn-outline">Results</a>`}
                ${CAN_MANAGE
                    ? `<a href="/code-arena/contest_manage.php?id=${c.id}" class="btn-outline" style="padding:7px 14px;font-size:.82rem">Manage</a>`
                    : ''}
                ${IS_ADMIN && c.status !== 'active'
                    ? `<button class="btn-outline" style="color:var(--red,#e05);border-color:var(--red,#e05);padding:7px 14px;font-size:.82rem"
                               onclick="openDeleteModal(${c.id}, this)">Delete</button>`
                    : ''}
            </div>
        </div>`;
    }).join('');
}

function showCreateForm() {
    document.getElementById('create-form').style.display = 'block';
    document.getElementById('create-form').scrollIntoView({ behavior:'smooth' });
}

async function createContest() {
    const payload = {
        title:       document.getElementById('ct-title').value.trim(),
        start_time:  document.getElementById('ct-start').value,
        end_time:    document.getElementById('ct-end').value,
        description: document.getElementById('ct-desc').value.trim(),
        problem_ids: document.getElementById('ct-problems').value.trim(),
        is_rated:    1,
    };

    const msg = document.getElementById('ct-msg');
    const { ok, data } = await api('/code-arena/api/contests/index.php', {
        method: 'POST', body: JSON.stringify(payload),
    });
    if (ok && data.success) {
        toast('Contest created!', 'success');
        document.getElementById('create-form').style.display = 'none';
        loadContests();
    } else {
        msg.textContent = data.message || 'Failed';
        msg.style.color = 'var(--red)';
    }
}

loadContests();

// ── Contest deletion (admin) ──────────────────────────────────
let _deleteTargetId   = null;
let _deleteTargetCard = null;

function openDeleteModal(id, btn) {
    _deleteTargetId   = id;
    _deleteTargetCard = btn.closest('.contest-card');
    document.getElementById('del-overlay').style.display = 'flex';
}

function closeDeleteModal(e) {
    if (e && e.target !== document.getElementById('del-overlay')) return;
    document.getElementById('del-overlay').style.display = 'none';
    _deleteTargetId   = null;
    _deleteTargetCard = null;
}

async function confirmDelete() {
    if (!_deleteTargetId) return;
    const btn = document.getElementById('del-confirm-btn');
    btn.disabled = true;
    btn.textContent = 'Deleting…';

    const { ok, data } = await api('/code-arena/api/contests/delete.php', {
        method: 'POST',
        body: JSON.stringify({ contest_id: _deleteTargetId }),
    });

    btn.disabled = false;
    btn.textContent = 'Delete';
    document.getElementById('del-overlay').style.display = 'none';

    if (ok && data.success) {
        toast('Contest deleted', 'success');
        if (_deleteTargetCard) {
            _deleteTargetCard.style.transition = 'opacity .3s ease, transform .3s ease';
            _deleteTargetCard.style.opacity    = '0';
            _deleteTargetCard.style.transform  = 'translateX(20px)';
            setTimeout(() => _deleteTargetCard.remove(), 300);
        }
    } else {
        toast(data.message || 'Deletion failed', 'error');
    }

    _deleteTargetId   = null;
    _deleteTargetCard = null;
}
</script>
</body>
</html>
