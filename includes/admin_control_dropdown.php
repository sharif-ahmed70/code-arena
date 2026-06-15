<?php
// Minimal admin control dropdown. Include only after session.php.
if (!isLoggedIn() || !isRealAdmin()) return;

if (empty($_SESSION['admin_context_token'])) {
    $_SESSION['admin_context_token'] = bin2hex(random_bytes(16));
}
$adminContext = adminViewContext();
$adminContextToken = $_SESSION['admin_context_token'];
?>
<style>
    .admin-control-wrap { position:relative; display:inline-flex; align-items:center; z-index:1000; }
    .admin-control-trigger {
        display:inline-flex; align-items:center; gap:8px; height:36px; padding:0 12px;
        border:1px solid rgba(108,160,255,.28); border-radius:999px;
        background:rgba(20,24,36,.58); color:var(--text); cursor:pointer;
        box-shadow:0 8px 28px rgba(0,0,0,.18), inset 0 1px 0 rgba(255,255,255,.06);
        backdrop-filter:blur(14px); -webkit-backdrop-filter:blur(14px);
        transition:border-color .18s ease, background .18s ease, transform .18s ease;
        font:inherit; font-size:.82rem; font-weight:700;
    }
    .admin-control-trigger:hover,
    .admin-control-trigger.open {
        border-color:rgba(0,232,122,.45); background:rgba(24,32,44,.76); transform:translateY(-1px);
    }
    .admin-control-dot {
        width:8px; height:8px; border-radius:50%; background:var(--blue);
        box-shadow:0 0 14px rgba(108,160,255,.8);
    }
    .admin-control-menu {
        position:absolute; top:calc(100% + 10px); right:0; width:238px; padding:9px;
        border:1px solid rgba(255,255,255,.12); border-radius:14px;
        background:rgba(13,16,24,.82); color:var(--text);
        box-shadow:0 24px 70px rgba(0,0,0,.42), inset 0 1px 0 rgba(255,255,255,.06);
        backdrop-filter:blur(20px); -webkit-backdrop-filter:blur(20px);
        opacity:0; transform:translateY(-6px) scale(.98); pointer-events:none;
        transition:opacity .16s ease, transform .16s ease;
    }
    .admin-control-menu.open { opacity:1; transform:translateY(0) scale(1); pointer-events:auto; }
    .admin-control-title {
        padding:8px 9px 9px; color:var(--text-muted); font-size:.72rem;
        text-transform:uppercase; letter-spacing:.08em; border-bottom:1px solid rgba(255,255,255,.08);
        margin-bottom:5px;
    }
    .admin-control-item {
        width:100%; display:flex; align-items:center; gap:10px; padding:9px 10px;
        border:0; border-radius:10px; background:transparent; color:var(--text-dim);
        cursor:pointer; text-align:left; font:inherit; font-size:.86rem;
        transition:background .15s ease, color .15s ease, transform .15s ease;
    }
    .admin-control-item:hover { background:rgba(124,58,237,.14); color:var(--text); transform:translateX(2px); }
    .admin-control-item.danger:hover { background:rgba(255,83,83,.1); color:var(--red); }
    .admin-context-modal {
        position:fixed; inset:0; z-index:5000; display:grid; place-items:center;
        padding:20px; background:rgba(2,6,18,.54); opacity:0; pointer-events:none;
        transition:opacity .18s ease;
    }
    .admin-context-modal.open { opacity:1; pointer-events:auto; }
    .admin-context-dialog {
        width:min(420px,100%); border:1px solid rgba(255,255,255,.13); border-radius:16px;
        background:rgba(13,16,24,.9); color:var(--text); padding:18px;
        box-shadow:0 28px 90px rgba(0,0,0,.48), inset 0 1px 0 rgba(255,255,255,.06);
        backdrop-filter:blur(22px); -webkit-backdrop-filter:blur(22px);
        transform:translateY(8px) scale(.98); transition:transform .18s ease;
    }
    .admin-context-modal.open .admin-context-dialog { transform:translateY(0) scale(1); }
    .admin-context-dialog h3 { margin:0 0 6px; font-size:1rem; }
    .admin-context-dialog p { margin:0 0 14px; color:var(--text-muted); font-size:.84rem; }
    .admin-context-field { display:grid; gap:7px; margin-bottom:14px; }
    .admin-context-field label { color:var(--text-dim); font-size:.78rem; font-weight:700; }
    .admin-context-field input {
        width:100%; height:42px; border:1px solid rgba(255,255,255,.12); border-radius:12px;
        background:rgba(255,255,255,.05); color:var(--text); padding:0 12px; outline:none;
    }
    .admin-context-field input:focus { border-color:rgba(124,58,237,.62); box-shadow:0 0 0 3px rgba(124,58,237,.14); }
    .admin-context-error { min-height:18px; color:var(--red); font-size:.78rem; margin-bottom:10px; }
    .admin-context-actions { display:flex; justify-content:flex-end; gap:10px; }
    .admin-context-actions button {
        height:36px; padding:0 13px; border-radius:10px; cursor:pointer; font:inherit; font-weight:800;
        border:1px solid rgba(255,255,255,.12); color:var(--text); background:rgba(255,255,255,.05);
    }
    .admin-context-actions .primary { border-color:rgba(124,58,237,.6); background:linear-gradient(135deg,#7c3aed,#a855f7); }
</style>

<div class="admin-control-wrap" data-admin-context="<?= htmlspecialchars($adminContext) ?>">
    <button type="button" class="admin-control-trigger" id="admin-control-trigger" onclick="toggleAdminControl(event)">
        <span class="admin-control-dot"></span>
        <span>Admin Mode</span>
        <span style="color:var(--text-muted)">▾</span>
    </button>
    <div class="admin-control-menu" id="admin-control-menu">
        <div class="admin-control-title">Admin Controls</div>
        <button type="button" class="admin-control-item" onclick="adminControlAction('dashboard')">📊 Dashboard</button>
        <button type="button" class="admin-control-item" onclick="adminControlAction('explore')">🌐 Explore Site</button>
        <button type="button" class="admin-control-item" onclick="adminControlViewUser()">👤 View as User</button>
        <button type="button" class="admin-control-item" onclick="adminControlViewOrg()">🏢 View as Org</button>
        <button type="button" class="admin-control-item danger" onclick="adminControlAction('exit')">⏏ Exit Admin Mode</button>
    </div>
</div>

<div class="admin-context-modal" id="admin-context-modal" aria-hidden="true">
    <div class="admin-context-dialog" role="dialog" aria-modal="true" aria-labelledby="admin-context-title">
        <h3 id="admin-context-title">Switch Context</h3>
        <p id="admin-context-help">Leave blank to use the default available context.</p>
        <div class="admin-context-field">
            <label id="admin-context-label" for="admin-context-input">ID</label>
            <input id="admin-context-input" type="number" min="1" inputmode="numeric" autocomplete="off">
        </div>
        <div class="admin-context-error" id="admin-context-error"></div>
        <div class="admin-context-actions">
            <button type="button" onclick="closeAdminContextModal()">Cancel</button>
            <button type="button" class="primary" onclick="submitAdminContextModal()">Switch</button>
        </div>
    </div>
</div>

<script>
window.CodeArenaAdminControl = window.CodeArenaAdminControl || {
    token: <?= json_encode($adminContextToken) ?>,
    pendingAction: null,
};

function toggleAdminControl(event) {
    event.stopPropagation();
    const trigger = document.getElementById('admin-control-trigger');
    const menu = document.getElementById('admin-control-menu');
    const nextOpen = !menu.classList.contains('open');
    trigger.classList.toggle('open', nextOpen);
    menu.classList.toggle('open', nextOpen);
}

function closeAdminControl() {
    document.getElementById('admin-control-trigger')?.classList.remove('open');
    document.getElementById('admin-control-menu')?.classList.remove('open');
}

async function adminControlAction(action) {
    return adminControlPost({ action });
}

function adminControlViewUser() {
    openAdminContextModal({
        action: 'view_user',
        title: 'View as User',
        label: 'User ID',
        help: 'Enter a user ID, or leave blank to use the first available non-admin user.',
    });
}

function adminControlViewOrg() {
    openAdminContextModal({
        action: 'view_org',
        title: 'View as Organization',
        label: 'Organization ID',
        help: 'Enter an organization ID, or leave blank to use the first available organization.',
    });
}

function openAdminContextModal(options) {
    closeAdminControl();
    window.CodeArenaAdminControl.pendingAction = options.action;
    document.getElementById('admin-context-title').textContent = options.title;
    document.getElementById('admin-context-label').textContent = options.label;
    document.getElementById('admin-context-help').textContent = options.help;
    document.getElementById('admin-context-error').textContent = '';
    const input = document.getElementById('admin-context-input');
    input.value = '';
    const modal = document.getElementById('admin-context-modal');
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
    setTimeout(() => input.focus(), 30);
}

function closeAdminContextModal() {
    const modal = document.getElementById('admin-context-modal');
    modal?.classList.remove('open');
    modal?.setAttribute('aria-hidden', 'true');
    window.CodeArenaAdminControl.pendingAction = null;
}

function submitAdminContextModal() {
    const action = window.CodeArenaAdminControl.pendingAction;
    if (!action) return;

    const input = document.getElementById('admin-context-input');
    const raw = input.value.trim();
    const error = document.getElementById('admin-context-error');
    if (raw !== '' && (!/^\d+$/.test(raw) || Number(raw) <= 0)) {
        error.textContent = 'Please enter a valid numeric ID, or leave it blank.';
        input.focus();
        return;
    }

    const id = raw === '' ? 0 : Number(raw);
    const payload = { action };
    if (action === 'view_user') payload.user_id = id;
    if (action === 'view_org') payload.org_id = id;
    closeAdminContextModal();
    adminControlPost(payload);
}

async function adminControlPost(payload) {
    const res = await fetch('/code-arena/api/admin/context.php', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ...payload, token: window.CodeArenaAdminControl.token }),
    });
    const data = await res.json();
    if (data.success && data.data?.redirect_url) {
        window.location.href = data.data.redirect_url;
        return;
    }
    if (window.toast) toast(data.message || 'Admin control action failed', 'error');
}

document.addEventListener('click', closeAdminControl);
document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
        closeAdminControl();
        closeAdminContextModal();
    }
    if (event.key === 'Enter' && document.getElementById('admin-context-modal')?.classList.contains('open')) {
        event.preventDefault();
        submitAdminContextModal();
    }
});
document.getElementById('admin-context-modal')?.addEventListener('click', event => {
    if (event.target?.id === 'admin-context-modal') closeAdminContextModal();
});
</script>
