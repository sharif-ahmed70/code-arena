<?php
// ============================================================
//  CODE ARENA - Admin Context Switcher
//  POST /api/admin/context.php { action, token }
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';
require_once '../../includes/adminActivityLogger.php';

methodCheck('POST');
requireLogin();
if (!isRealAdmin()) err('Admin access required', 403);

$body = jsonBody();
$action = cleanString($body['action'] ?? '', 40);
$token = (string)($body['token'] ?? '');
$sessionToken = (string)($_SESSION['admin_context_token'] ?? '');

if (!$sessionToken || !$token || !hash_equals($sessionToken, $token)) {
    err('Invalid admin context token', 403);
}

$redirect = '/code-arena/index.php';
$description = 'Switched admin context';

if ($action === 'dashboard') {
    setAdminViewContext('admin');
    $redirect = '/code-arena/admin.php';
    $description = 'Opened admin dashboard';
} elseif ($action === 'explore') {
    setAdminViewContext('admin');
    $redirect = '/code-arena/index.php';
    $description = 'Explored site in admin context';
} elseif ($action === 'view_user') {
    setAdminViewContext('user');
    $redirect = '/code-arena/index.php';
    $description = 'Switched to user view context';
} elseif ($action === 'view_org') {
    setAdminViewContext('org');
    $redirect = '/code-arena/organization/dashboard.php';
    $description = 'Switched to organization view context';
} elseif ($action === 'exit') {
    setAdminViewContext('user');
    $redirect = '/code-arena/index.php';
    $description = 'Exited admin mode into user view context';
} else {
    err('Unknown admin context action');
}

session_regenerate_id(true);
logAdminAction(currentUserId(), 'ADMIN_CONTEXT_SWITCH', 'session', currentUserId(), $description);

ok([
    'context' => adminViewContext(),
    'redirect_url' => $redirect,
], 'Admin context updated');
