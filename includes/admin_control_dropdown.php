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
    .admin-control-item:hover { background:rgba(0,232,122,.09); color:var(--text); transform:translateX(2px); }
    .admin-control-item.danger:hover { background:rgba(255,83,83,.1); color:var(--red); }
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
        <button type="button" class="admin-control-item" onclick="adminControlAction('view_user')">👤 View as User</button>
        <button type="button" class="admin-control-item" onclick="adminControlAction('view_org')">🏢 View as Org</button>
        <button type="button" class="admin-control-item danger" onclick="adminControlAction('exit')">⏏ Exit Admin Mode</button>
    </div>
</div>

<script>
window.CodeArenaAdminControl = window.CodeArenaAdminControl || {
    token: <?= json_encode($adminContextToken) ?>,
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
    const res = await fetch('/code-arena/api/admin/context.php', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action, token: window.CodeArenaAdminControl.token }),
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
    if (event.key === 'Escape') closeAdminControl();
});
</script>
