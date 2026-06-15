<?php
require_once '../includes/session.php';
requireLogin();

if (isAdmin()) {
    safeRedirect('/code-arena/admin.php');
}
if (canAccessOrganizationDashboard()) {
    safeRedirect('/code-arena/organization/dashboard.php');
}
if (isset($_SESSION['profile_completed']) && (int)$_SESSION['profile_completed'] === 0) {
    safeRedirect('/code-arena/profile_complete.php');
}

safeRedirect('/code-arena/dashboard.php');
