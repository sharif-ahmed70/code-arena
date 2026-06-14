<?php
// ============================================================
//  CODE ARENA — Admin: User Management
//  GET  /api/admin/users.php           → list users
//  PUT  /api/admin/users.php           → update role   {id, role}
//  DELETE /api/admin/users.php         → delete user   {id}
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/adminAuthMiddleware.php';
require_once '../../includes/adminActivityLogger.php';

requireAdminApi();

$method = $_SERVER['REQUEST_METHOD'];

// ── List ─────────────────────────────────────────────────────
if ($method === 'GET') {
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 30;
    $offset  = ($page - 1) * $perPage;
    $search  = cleanString($_GET['search'] ?? '', 100);
    $isBlocked = isset($_GET['is_blocked']) && $_GET['is_blocked'] !== '' ? cleanString($_GET['is_blocked'], 1) : '';
    $createdFrom = cleanString($_GET['created_from'] ?? '', 10);
    $createdTo = cleanString($_GET['created_to'] ?? '', 10);

    $where  = [];
    $params = [];
    $where[] = 'COALESCE(is_deleted, 0) = 0';
    if ($search) {
        $where[]  = '(username LIKE ? OR email LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if ($isBlocked !== '') {
        if (!in_array($isBlocked, ['0', '1'], true)) err('Invalid is_blocked filter');
        $where[] = 'COALESCE(is_blocked, 0) = ?';
        $params[] = (int)$isBlocked;
    }
    if ($createdFrom) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $createdFrom)) err('Invalid created_from date');
        $where[] = 'created_at >= ?';
        $params[] = $createdFrom . ' 00:00:00';
    }
    if ($createdTo) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $createdTo)) err('Invalid created_to date');
        $where[] = 'created_at <= ?';
        $params[] = $createdTo . ' 23:59:59';
    }
    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countSt = $pdo->prepare("SELECT COUNT(*) FROM users $whereSQL");
    $countSt->execute($params);
    $total = (int) $countSt->fetchColumn();

    $listSt = $pdo->prepare(
        "SELECT id, username, email, role, COALESCE(is_blocked, 0) AS is_blocked,
                hardcore_rating, learning_rating, created_at
         FROM users $whereSQL ORDER BY id DESC LIMIT $perPage OFFSET $offset"
    );
    $listSt->execute($params);

    $adminCountSt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = ? AND COALESCE(is_deleted, 0) = 0");
    $adminCountSt->execute(['admin']);
    $totalAdmins  = (int) $adminCountSt->fetchColumn();

    ok(['users' => $listSt->fetchAll(), 'total' => $total, 'page' => $page, 'totalAdmins' => $totalAdmins]);
}

// ── Update role ───────────────────────────────────────────────
if ($method === 'PUT') {
    $body = jsonBody();
    $id   = (int) ($body['id']   ?? 0);
    $role = cleanString($body['role'] ?? '', 20);
    $action = cleanString($body['action'] ?? 'role', 20);
    if (!$id) err('id required');

    if ($id === currentUserId()) err('You cannot modify your own admin account.', 403);

    if ($action === 'block') {
        $pdo->prepare('UPDATE users SET is_blocked = 1 WHERE id = ?')->execute([$id]);
        logAdminAction(currentUserId(), 'BLOCK_USER', 'user', $id, "Blocked user ID $id");
        appLog($pdo, 'admin_user_blocked', ['target_user_id' => $id]);
        ok(null, 'User blocked');
    }

    if ($action === 'unblock') {
        $pdo->prepare('UPDATE users SET is_blocked = 0 WHERE id = ?')->execute([$id]);
        logAdminAction(currentUserId(), 'UNBLOCK_USER', 'user', $id, "Unblocked user ID $id");
        appLog($pdo, 'admin_user_unblocked', ['target_user_id' => $id]);
        ok(null, 'User unblocked');
    }

    if (!in_array($role, ['student', 'instructor', 'admin'], true)) err('Invalid role');

    if ($role !== 'admin') {
        $st = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin' AND COALESCE(is_deleted, 0) = 0");
        $st->execute();
        $adminCount = (int) $st->fetchColumn();
        $st2 = $pdo->prepare("SELECT role FROM users WHERE id = ? AND COALESCE(is_deleted, 0) = 0");
        $st2->execute([$id]);
        $targetRole = $st2->fetchColumn();
        if ($adminCount === 1 && $targetRole === 'admin') err('Cannot remove the last admin account.', 403);
    }

    $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $id]);
    appLog($pdo, 'admin_user_role_updated', ['target_user_id' => $id, 'role' => $role]);
    ok(null, 'Role updated');
}

// ── Delete ────────────────────────────────────────────────────
if ($method === 'DELETE') {
    $body = jsonBody();
    $id   = (int) ($body['id'] ?? 0);
    if (!$id) err('id required');

    if ($id === currentUserId()) err('You cannot modify your own admin account.', 403);

    $st = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin' AND COALESCE(is_deleted, 0) = 0");
    $st->execute();
    $adminCount = (int) $st->fetchColumn();
    $st2 = $pdo->prepare("SELECT role FROM users WHERE id = ? AND COALESCE(is_deleted, 0) = 0");
    $st2->execute([$id]);
    $targetRole = $st2->fetchColumn();
    if (!$targetRole) err('User not found', 404);
    if ($adminCount === 1 && $targetRole === 'admin') err('Cannot remove the last admin account.', 403);

    $pdo->prepare('UPDATE users SET is_deleted = 1, is_blocked = 1 WHERE id = ?')->execute([$id]);
    logAdminAction(currentUserId(), 'SOFT_DELETE_USER', 'user', $id, "Soft deleted user ID $id");
    appLog($pdo, 'admin_user_deleted', ['target_user_id' => $id]);
    ok(null, 'User deleted');
}

err('Method not allowed', 405);
