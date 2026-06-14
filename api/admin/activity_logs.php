<?php
// ============================================================
//  CODE ARENA - Admin Activity Logs API
//  GET /api/admin/activity_logs.php?page=&per_page=&admin_id=
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/adminAuthMiddleware.php';

requireAdminApi();
methodCheck('GET');

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = min(100, max(10, (int)($_GET['per_page'] ?? 30)));
$offset = ($page - 1) * $perPage;
$adminId = isset($_GET['admin_id']) && $_GET['admin_id'] !== '' ? (int)$_GET['admin_id'] : null;

$where = [];
$params = [];

if ($adminId !== null) {
    if ($adminId < 1) err('Invalid admin_id');
    $where[] = 'l.admin_id = ?';
    $params[] = $adminId;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $countStmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM admin_activity_logs l
         $whereSQL"
    );
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT l.id, l.admin_id, u.username AS admin_username, u.email AS admin_email,
                l.action, l.target_type, l.target_id, l.description,
                l.ip_address, l.created_at
         FROM admin_activity_logs l
         LEFT JOIN users u ON u.id = l.admin_id
         $whereSQL
         ORDER BY l.created_at DESC, l.id DESC
         LIMIT $perPage OFFSET $offset"
    );
    $stmt->execute($params);

    ok([
        'logs' => $stmt->fetchAll(),
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'pages' => (int)ceil($total / $perPage),
    ]);
} catch (PDOException $e) {
    error_log('admin activity logs fetch failed: ' . $e->getMessage());
    err('Failed to load admin activity logs', 500);
}
