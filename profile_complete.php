<?php
require_once 'includes/session.php';
require_once 'config/db.php';
requireLogin();

if (isRealAdmin()) {
    safeRedirect('/code-arena/admin.php');
}

$stmt = $pdo->prepare('SELECT name, country, university FROM users WHERE id = ?');
$stmt->execute([currentUserId()]);
$user = $stmt->fetch() ?: [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Profile - Code Arena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css?v=20260615-ui2">
</head>
<body>
<div class="auth-page">
    <div class="auth-card fade-up">
        <div class="auth-logo">Code<span>Arena</span></div>
        <p class="auth-subtitle">Finish your profile before entering your dashboard.</p>
        <div class="alert" id="alert"></div>

        <div class="form-group">
            <label class="form-label">Full Name</label>
            <input id="name" class="form-input" value="<?= htmlspecialchars($user['name'] ?? currentUsername() ?? '') ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Country</label>
            <input id="country" class="form-input" value="<?= htmlspecialchars($user['country'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label class="form-label">University <span style="color:var(--text-muted);font-size:.78rem">(optional)</span></label>
            <input id="university" class="form-input" value="<?= htmlspecialchars($user['university'] ?? '') ?>">
        </div>

        <button class="btn-primary w-full" id="saveBtn" onclick="completeProfile()">Continue</button>
    </div>
</div>

<script>
async function completeProfile() {
    const name = document.getElementById('name').value.trim();
    const country = document.getElementById('country').value.trim();
    const university = document.getElementById('university').value.trim();
    const btn = document.getElementById('saveBtn');
    if (!name || !country) {
        showAlert('Full name and country are required.', 'error');
        return;
    }
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Saving...';
    try {
        const res = await fetch('/code-arena/api/users/complete_profile.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, country, university }),
        });
        const data = await res.json();
        if (data.success) {
            window.location.href = data.data?.redirect_url || '/code-arena/dashboard.php';
            return;
        }
        showAlert(data.message || 'Could not save profile.', 'error');
    } catch (e) {
        showAlert('Something went wrong. Try again.', 'error');
    }
    btn.disabled = false;
    btn.textContent = 'Continue';
}

function showAlert(msg, type) {
    const alert = document.getElementById('alert');
    alert.textContent = msg;
    alert.className = `alert alert-${type} show`;
}
</script>
</body>
</html>
