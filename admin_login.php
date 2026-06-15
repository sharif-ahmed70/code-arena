<?php
// ============================================================
//  CODE ARENA - Admin Login
// ============================================================
require_once 'includes/session.php';
if (isLoggedIn() && isRealAdmin()) {
    header('Location: /code-arena/admin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Code Arena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css?v=20260615-ui2">
    <style>
        .auth-wrap { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }
        .auth-card { width:100%; max-width:420px; }
        .auth-card h1 { font-size:1.8rem; margin-bottom:8px; }
        .auth-card p { color:var(--text-muted); margin-bottom:24px; }
        .auth-actions { display:flex; gap:12px; align-items:center; margin-top:18px; }
    </style>
</head>
<body>
<div class="auth-wrap">
    <div class="card auth-card">
        <h1>Admin Login</h1>
        <p>Secure access for Code Arena administrators.</p>
        <div class="form-group">
            <label class="form-label">Username or Email</label>
            <input id="identifier" class="form-input" autocomplete="username">
        </div>
        <div class="form-group">
            <label class="form-label">Password</label>
            <input id="password" type="password" class="form-input" autocomplete="current-password">
        </div>
        <div class="auth-actions">
            <button class="btn-primary" id="login-btn" onclick="adminLogin()">Login</button>
            <a class="btn-outline" href="/code-arena/">Home</a>
        </div>
    </div>
</div>

<script src="/code-arena/assets/js/main.js"></script>
<script>
async function adminLogin() {
    const identifier = document.getElementById('identifier').value.trim();
    const password = document.getElementById('password').value;
    if (!identifier || !password) { toast('Enter username/email and password', 'warn'); return; }

    const btn = document.getElementById('login-btn');
    btn.disabled = true;
    btn.textContent = 'Checking...';

    const { ok, data } = await api('/code-arena/api/admin/auth.php', {
        method: 'POST',
        body: JSON.stringify({ identifier, password }),
    });

    btn.disabled = false;
    btn.textContent = 'Login';
    if (!ok || !data.success) {
        toast(data.message || 'Admin login failed', 'error');
        return;
    }

    toast('Welcome admin', 'success');
    setTimeout(() => window.location.href = '/code-arena/admin.php', 500);
}

document.addEventListener('keydown', e => {
    if (e.key === 'Enter') adminLogin();
});
</script>
</body>
</html>
