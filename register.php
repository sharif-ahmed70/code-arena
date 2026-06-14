<?php
// ============================================================
//  CODE ARENA — Register Page
//  File: register.php
// ============================================================
require_once 'includes/session.php';

// Already logged in? Go to problems
if (isLoggedIn()) {
    header('Location: /code-arena/problems.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — Code Arena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css">
</head>
<body>

<div class="auth-page">
    <div class="auth-card fade-up">

        <div class="auth-logo">Code<span>Arena</span></div>
        <p class="auth-subtitle">Create your account and start competing.</p>

        <div class="alert" id="alert"></div>

        <div class="form-group">
            <label class="form-label">Username</label>
            <input
                type="text"
                id="username"
                class="form-input"
                placeholder="Choose a username"
                autofocus
            >
        </div>

        <div class="form-group">
            <label class="form-label">Email</label>
            <input
                type="email"
                id="email"
                class="form-input"
                placeholder="Enter your email"
            >
        </div>

        <div class="form-group">
            <label class="form-label">Password</label>
            <input
                type="password"
                id="password"
                class="form-input"
                placeholder="At least 8 characters"
            >
        </div>

        <div class="form-group">
            <label class="form-label">Confirm Password</label>
            <input
                type="password"
                id="confirmPassword"
                class="form-input"
                placeholder="Repeat your password"
            >
        </div>

        <button class="btn-primary w-full" id="registerBtn" onclick="handleRegister()">
            Create Account
        </button>

        <div class="auth-footer">
            Already have an account? <a href="/code-arena/login.php">Sign in</a>
        </div>

    </div>
</div>

<script>
    document.addEventListener('keydown', e => {
        if (e.key === 'Enter') handleRegister();
    });

    async function handleRegister() {
        const username        = document.getElementById('username').value.trim();
        const email           = document.getElementById('email').value.trim();
        const password        = document.getElementById('password').value.trim();
        const confirmPassword = document.getElementById('confirmPassword').value.trim();
        const btn             = document.getElementById('registerBtn');

        // Clear alert
        showAlert('', '');

        // Client-side validation
        if (!username || !email || !password || !confirmPassword) {
            showAlert('Please fill in all fields.', 'error');
            return;
        }

        if (password !== confirmPassword) {
            showAlert('Passwords do not match.', 'error');
            return;
        }

        if (password.length < 8) {
            showAlert('Password must be at least 8 characters.', 'error');
            return;
        }

        // Loading state
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Creating account...';

        try {
            const res = await fetch('/code-arena/api/auth/register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, email, password })
            });

            const data = await res.json();

            if (data.success) {
                showAlert('Account created! Redirecting...', 'success');
                setTimeout(() => {
                    window.location.href = '/code-arena/problems.php';
                }, 1000);
            } else {
                showAlert(data.message || 'Registration failed.', 'error');
                btn.disabled = false;
                btn.innerHTML = 'Create Account';
            }
        } catch (err) {
            showAlert('Something went wrong. Try again.', 'error');
            btn.disabled = false;
            btn.innerHTML = 'Create Account';
        }
    }

    function showAlert(msg, type) {
        const alert = document.getElementById('alert');
        if (!msg) { alert.className = 'alert'; return; }
        alert.textContent = msg;
        alert.className = `alert alert-${type} show`;
    }
</script>

</body>
</html>
