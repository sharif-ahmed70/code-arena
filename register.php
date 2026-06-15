<?php
// ============================================================
//  CODE ARENA - Register Page
// ============================================================
require_once 'includes/session.php';

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
    <title>Register - Code Arena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css?v=20260615-ui5">
    <style>
        .auth-card { max-width: 620px; }
        .step-label { color:var(--text-muted); font-size:.78rem; text-transform:uppercase; letter-spacing:.06em; margin:18px 0 10px; }
        .account-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .account-choice {
            display:flex; gap:10px; align-items:flex-start; padding:14px; border:1px solid var(--border);
            border-radius:var(--radius-sm); background:var(--bg-card2); cursor:pointer;
            transition:border-color .18s ease, background .18s ease;
        }
        .account-choice:hover, .account-choice.active { border-color:rgba(0,232,122,.45); background:rgba(0,232,122,.06); }
        .account-choice input { margin-top:3px; accent-color:var(--accent); }
        .account-choice strong { display:block; color:var(--text); margin-bottom:3px; }
        .account-choice span { display:block; color:var(--text-muted); font-size:.82rem; line-height:1.45; }
        .field-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        .field-grid .full { grid-column:1 / -1; }
        .optional-note { color:var(--text-muted); font-size:.78rem; margin-top:4px; }
        @media(max-width:640px) {
            .account-grid, .field-grid { grid-template-columns:1fr; }
            .field-grid .full { grid-column:auto; }
        }
    </style>
</head>
<body>

<div class="auth-page">
    <div class="auth-card fade-up">
        <div class="auth-logo">Code<span>Arena</span></div>
        <p class="auth-subtitle">Create an individual account or register an organization workspace.</p>

        <div class="alert" id="alert"></div>

        <div class="step-label">Step 1 - Choose Account Type</div>
        <div class="account-grid">
            <label class="account-choice active" id="choice-individual">
                <input type="radio" name="accountType" value="individual" checked onchange="setAccountType('individual')">
                <span>
                    <strong>Individual User</strong>
                    <span>Practice, compete, and optionally join an organization.</span>
                </span>
            </label>
            <label class="account-choice" id="choice-organization">
                <input type="radio" name="accountType" value="organization" onchange="setAccountType('organization')">
                <span>
                    <strong>Organization Admin</strong>
                    <span>Create an organization and manage its members.</span>
                </span>
            </label>
        </div>

        <div class="step-label">Step 2 - Account Details</div>
        <div class="field-grid">
            <div class="form-group full">
                <label class="form-label">Full Name</label>
                <input type="text" id="fullName" class="form-input" placeholder="Your full name" autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">Contact Address</label>
                <input type="text" id="email" class="form-input" inputmode="email" autocomplete="username" spellcheck="false" placeholder="account@example.com">
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" id="password" class="form-input" placeholder="At least 8 characters">
            </div>
            <div class="form-group full">
                <label class="form-label">Confirm Password</label>
                <input type="password" id="confirmPassword" class="form-input" placeholder="Repeat your password">
            </div>
        </div>

        <div id="individual-fields" class="field-grid">
            <div class="form-group">
                <label class="form-label">Country</label>
                <input type="text" id="country" class="form-input" placeholder="Country">
            </div>
            <div class="form-group">
                <label class="form-label">University <span class="optional-note">(optional)</span></label>
                <input type="text" id="university" class="form-input" placeholder="University">
            </div>
            <div class="form-group full">
                <label class="form-label">Join Organization <span class="optional-note">(optional)</span></label>
                <select id="orgId" class="form-input">
                    <option value="">No organization</option>
                </select>
            </div>
        </div>

        <div id="organization-fields" class="field-grid" style="display:none">
            <div class="form-group full">
                <label class="form-label">Organization Name</label>
                <input type="text" id="organizationName" class="form-input" placeholder="Organization name">
            </div>
            <div class="form-group">
                <label class="form-label">Organization Type</label>
                <select id="organizationType" class="form-input">
                    <option value="university">University</option>
                    <option value="company">Company</option>
                    <option value="community">Community</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Logo URL <span class="optional-note">(optional)</span></label>
                <input type="text" id="logo" class="form-input" placeholder="https://...">
            </div>
            <div class="form-group full">
                <label class="form-label">Description <span class="optional-note">(optional)</span></label>
                <textarea id="description" class="form-input" rows="3" placeholder="Short organization description"></textarea>
            </div>
        </div>

        <button class="btn-primary w-full" id="registerBtn" onclick="handleRegister()">Create Account</button>

        <div class="auth-footer">
            Already have an account? <a href="/code-arena/login.php">Sign in</a>
        </div>
    </div>
</div>

<script src="/code-arena/assets/js/main.js?v=20260615-ui5"></script>
<script>
let accountType = 'individual';

document.addEventListener('keydown', e => {
    if (e.key === 'Enter') handleRegister();
});

loadOrganizations();

function setAccountType(type) {
    accountType = type === 'organization' ? 'organization' : 'individual';
    document.getElementById('choice-individual').classList.toggle('active', accountType === 'individual');
    document.getElementById('choice-organization').classList.toggle('active', accountType === 'organization');
    document.getElementById('individual-fields').style.display = accountType === 'individual' ? 'grid' : 'none';
    document.getElementById('organization-fields').style.display = accountType === 'organization' ? 'grid' : 'none';
}

async function loadOrganizations() {
    try {
        const res = await fetch('/code-arena/api/organizations/index.php');
        const data = await res.json();
        if (!data.success) return;
        const select = document.getElementById('orgId');
        data.data.organizations.forEach(org => {
            const option = document.createElement('option');
            option.value = org.id;
            option.textContent = `${org.name} (${org.type})`;
            select.appendChild(option);
        });
    } catch (e) {}
}

async function handleRegister() {
    const full_name = document.getElementById('fullName').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const btn = document.getElementById('registerBtn');

    showAlert('', '');
    if (!full_name || !email || !password || !confirmPassword) {
        showAlert('Please complete the required fields.', 'error');
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

    const payload = {
        account_type: accountType,
        full_name,
        email,
        password,
    };

    if (accountType === 'individual') {
        payload.country = document.getElementById('country').value.trim();
        payload.university = document.getElementById('university').value.trim();
        payload.org_id = document.getElementById('orgId').value || null;
        if (!payload.country) {
            showAlert('Country is required for individual users.', 'error');
            return;
        }
    } else {
        payload.organization_name = document.getElementById('organizationName').value.trim();
        payload.organization_type = document.getElementById('organizationType').value;
        payload.description = document.getElementById('description').value.trim();
        payload.logo = document.getElementById('logo').value.trim();
        if (!payload.organization_name) {
            showAlert('Organization name is required.', 'error');
            return;
        }
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Creating account...';

    try {
        const res = await fetch('/code-arena/api/auth/register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (data.success) {
            showAlert('Account created. Redirecting...', 'success');
            setTimeout(() => {
                window.location.href = data.data?.redirect_url || '/code-arena/problems.php';
            }, 700);
            return;
        }
        showAlert(data.message || 'Registration failed.', 'error');
    } catch (err) {
        showAlert('Something went wrong. Try again.', 'error');
    }
    btn.disabled = false;
    btn.innerHTML = 'Create Account';
}

function showAlert(msg, type) {
    const alert = document.getElementById('alert');
    if (!msg) { alert.className = 'alert'; alert.textContent = ''; return; }
    alert.textContent = msg;
    alert.className = `alert alert-${type} show`;
}
</script>

</body>
</html>
