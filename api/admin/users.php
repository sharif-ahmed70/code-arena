<?php
// ============================================================
//  CODE ARENA — Admin: User Management
//  GET  /api/admin/users.php           → list users
//  PUT  /api/admin/users.php           → update role   {id, role}
//  DELETE /api/admin/users.php         → delete user   {id}
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';

requireAdmin();

$method = $_SERVER['REQUEST_METHOD'];

// ── List ─────────────────────────────────────────────────────
if ($method === 'GET') {
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 30;
    $offset  = ($page - 1) * $perPage;
    $search  = trim($_GET['search'] ?? '');

    $where  = [];
    $params = [];
    if ($search) {
        $where[]  = '(username LIKE ? OR email LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countSt = $pdo->prepare("SELECT COUNT(*) FROM users $whereSQL");
    $countSt->execute($params);
    $total = (int) $countSt->fetchColumn();

    $listSt = $pdo->prepare(
        "SELECT id, username, email, role, hardcore_rating, learning_rating, created_at
         FROM users $whereSQL ORDER BY id DESC LIMIT $perPage OFFSET $offset"
    );
    $listSt->execute($params);

    $adminCountSt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $totalAdmins  = (int) $adminCountSt->fetchColumn();

    ok(['users' => $listSt->fetchAll(), 'total' => $total, 'page' => $page, 'totalAdmins' => $totalAdmins]);
}

// ── Update role ───────────────────────────────────────────────
if ($method === 'PUT') {
    $body = jsonBody();
    $id   = (int) ($body['id']   ?? 0);
    $role = trim($body['role']   ?? '');
    if (!$id || !in_array($role, ['student', 'instructor', 'admin'])) err('Invalid data');

    if ($id === currentUserId()) err('You cannot modify your own admin account.', 403);

    if ($role !== 'admin') {
        $st = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $st->execute();
        $adminCount = (int) $st->fetchColumn();
        $st2 = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $st2->execute([$id]);
        $targetRole = $st2->fetchColumn();
        if ($adminCount === 1 && $targetRole === 'admin') err('Cannot remove the last admin account.', 403);
    }

    $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $id]);
    ok(null, 'Role updated');
}

// ── Delete ────────────────────────────────────────────────────
if ($method === 'DELETE') {
    $body = jsonBody();
    $id   = (int) ($body['id'] ?? 0);
    if (!$id) err('id required');

    if ($id === currentUserId()) err('You cannot modify your own admin account.', 403);

    $st = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $st->execute();
    $adminCount = (int) $st->fetchColumn();
    $st2 = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $st2->execute([$id]);
    $targetRole = $st2->fetchColumn();
    if ($adminCount === 1 && $targetRole === 'admin') err('Cannot remove the last admin account.', 403);

    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    ok(null, 'User deleted');
}

err('Method not allowed', 405);
