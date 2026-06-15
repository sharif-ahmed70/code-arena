<?php
// ============================================================
//  CODE ARENA — Login Page
//  File: login.php
// ============================================================
require_once 'includes/session.php';

// Already logged in? Go to problems
if (isLoggedIn()) {
    header('Location: ' . (isset($_SESSION['profile_completed']) && (int)$_SESSION['profile_completed'] === 0 && !isAdmin() ? '/code-arena/profile_complete.php' : authDashboardPath()));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Code Arena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css?v=20260615-ui2">
</head>
<body>

<div class="auth-page">
    <div class="auth-card fade-up">

        <div class="auth-logo">Code<span>Arena</span></div>
        <p class="auth-subtitle">Welcome back. Sign in to continue.</p>

        <div class="alert alert-error" id="alert"></div>

        <div class="form-group">
            <label class="form-label">Username or Email</label>
            <input
                type="text"
                id="identifier"
                class="form-input"
                placeholder="Enter username or email"
                autofocus
            >
        </div>

        <div class="form-group">
            <label class="form-label">Password</label>
            <input
                type="password"
                id="password"
                class="form-input"
                placeholder="Enter password"
            >
        </div>

        <button class="btn-primary w-full" id="loginBtn" onclick="handleLogin()">
            Sign In
        </button>

        <div class="auth-footer">
            Don't have an account? <a href="/code-arena/register.php">Register</a>
        </div>

    </div>
</div>

<script>
    // Allow pressing Enter to submit
    document.addEventListener('keydown', e => {
        if (e.key === 'Enter') handleLogin();
    });

    async function handleLogin() {
        const identifier = document.getElementById('identifier').value.trim();
        const password   = document.getElementById('password').value.trim();
        const alert      = document.getElementById('alert');
        const btn        = document.getElementById('loginBtn');

        // Hide previous alert
        alert.className = 'alert';
        alert.textContent = '';

        if (!identifier || !password) {
            showAlert('Please fill in all fields.', 'error');
            return;
        }

        // Loading state
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Signing in...';

        try {
            const res = await fetch('/code-arena/api/auth/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ identifier, password })
            });

            const data = await res.json();

            if (data.success) {
                window.location.href = data.data?.redirect_url || '/code-arena/problems.php';
            } else {
                showAlert(data.message || 'Login failed.', 'error');
                btn.disabled = false;
                btn.innerHTML = 'Sign In';
            }
        } catch (err) {
            showAlert('Something went wrong. Try again.', 'error');
            btn.disabled = false;
            btn.innerHTML = 'Sign In';
        }
    }

    function showAlert(msg, type) {
        const alert = document.getElementById('alert');
        alert.textContent = msg;
        alert.className = `alert alert-${type} show`;
    }
</script>

</body>
</html>
