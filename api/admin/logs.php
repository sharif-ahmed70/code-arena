<?php
// ============================================================
//  CODE ARENA - Admin System Logs API
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/adminAuthMiddleware.php';

requireAdminApi();
methodCheck('GET');

$limit = min(100, max(10, (int)($_GET['limit'] ?? 40)));

$adminLogs = [];
$auditLogs = [];

try {
    $stmt = $pdo->prepare(
        "SELECT l.id, l.admin_id, u.username AS actor, l.action AS event,
                l.target_type, l.target_id, l.description, l.ip_address, l.created_at,
                'admin' AS source
         FROM admin_activity_logs l
         LEFT JOIN users u ON u.id = l.admin_id
         ORDER BY l.created_at DESC
         LIMIT $limit"
    );
    $stmt->execute();
    $adminLogs = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('admin logs fetch failed: ' . $e->getMessage());
}

try {
    $stmt = $pdo->prepare(
        "SELECT a.id, a.user_id AS admin_id, u.username AS actor, a.event,
                NULL AS target_type, NULL AS target_id, a.context AS description,
                a.ip_address, a.created_at, 'audit' AS source
         FROM audit_logs a
         LEFT JOIN users u ON u.id = a.user_id
         ORDER BY a.created_at DESC
         LIMIT $limit"
    );
    $stmt->execute();
    $auditLogs = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('audit logs fetch failed: ' . $e->getMessage());
}

$logs = array_merge($adminLogs, $auditLogs);
usort($logs, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
$logs = array_slice($logs, 0, $limit);

ok(['logs' => $logs]);
