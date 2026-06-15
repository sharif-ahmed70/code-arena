<?php
// ============================================================
//  CODE ARENA - Admin Auth Middleware
// ============================================================

require_once __DIR__ . '/session.php';

function requireAdminApi(): void {
    require_once __DIR__ . '/response.php';
    requireLogin();
    if (!isAdmin()) {
        err('Admin access required', 403);
    }
}

function requireAdminPage(): void {
    if (!isLoggedIn()) {
        header('Location: /code-arena/admin_login.php');
        exit;
    }
    if (!isRealAdmin()) {
        header('Location: /code-arena/problems.php');
        exit;
    }
    if (!isAdmin()) {
        safeRedirect(authDashboardPath(currentRole()));
    }
}
