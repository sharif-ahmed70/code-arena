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
$requestedOrgId = (int)($body['org_id'] ?? 0);
$requestedUserId = (int)($body['user_id'] ?? 0);
$token = (string)($body['token'] ?? '');
$sessionToken = (string)($_SESSION['admin_context_token'] ?? '');

if (!$sessionToken || !$token || !hash_equals($sessionToken, $token)) {
    err('Invalid admin context token', 403);
}

$redirect = '/code-arena/index.php';
$description = 'Switched admin context';
$viewOrgId = null;
$viewUserId = null;

function firstVisibleOrgId(PDO $pdo, int $requestedOrgId = 0): ?int {
    if ($requestedOrgId > 0) {
        $stmt = $pdo->prepare('SELECT id FROM organizations WHERE id = ? LIMIT 1');
        $stmt->execute([$requestedOrgId]);
        $id = $stmt->fetchColumn();
        return $id ? (int)$id : null;
    }
    $stmt = $pdo->query('SELECT id FROM organizations ORDER BY id ASC LIMIT 1');
    $id = $stmt->fetchColumn();
    return $id ? (int)$id : null;
}

function firstViewUserId(PDO $pdo, int $requestedUserId = 0): ?int {
    if ($requestedUserId > 0) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? AND role <> "admin" AND COALESCE(is_deleted, 0) = 0 LIMIT 1');
        $stmt->execute([$requestedUserId]);
        $id = $stmt->fetchColumn();
        return $id ? (int)$id : null;
    }
    $stmt = $pdo->query('SELECT id FROM users WHERE role <> "admin" AND COALESCE(is_deleted, 0) = 0 ORDER BY id ASC LIMIT 1');
    $id = $stmt->fetchColumn();
    return $id ? (int)$id : null;
}

if ($action === 'dashboard') {
    setAdminViewContext('admin');
    $redirect = '/code-arena/admin.php';
    $description = 'Opened admin dashboard';
} elseif ($action === 'explore') {
    setAdminViewContext('admin');
    $redirect = '/code-arena/index.php';
    $description = 'Explored site in admin context';
} elseif ($action === 'view_user') {
    $viewUserId = firstViewUserId($pdo, $requestedUserId);
    if ($requestedUserId > 0 && !$viewUserId) err('User not found for admin view');
    setAdminViewContext('user', null, $viewUserId);
    $redirect = '/code-arena/user/dashboard.php';
    $description = $viewUserId ? "Switched to user view context for user #$viewUserId" : 'Switched to generic user view context';
} elseif ($action === 'view_org') {
    $viewOrgId = firstVisibleOrgId($pdo, $requestedOrgId);
    if (!$viewOrgId) err('No organization is available for org view');
    setAdminViewContext('org', $viewOrgId);
    $redirect = '/code-arena/organization/dashboard.php';
    $description = "Switched to organization view context for organization #$viewOrgId";
} elseif ($action === 'exit') {
    setAdminViewContext('admin');
    $redirect = '/code-arena/admin.php';
    $description = 'Exited impersonation and restored admin view context';
} else {
    err('Unknown admin context action');
}

session_regenerate_id(true);
logAdminAction(currentUserId(), 'ADMIN_CONTEXT_SWITCH', 'session', currentUserId(), $description);

ok([
    'context' => adminViewContext(),
    'view_mode' => adminViewMode(),
    'view_org_id' => adminViewOrgId(),
    'view_user_id' => adminViewUserId(),
    'redirect_url' => $redirect,
], 'Admin context updated');
