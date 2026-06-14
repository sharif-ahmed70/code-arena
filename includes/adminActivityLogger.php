<?php
// ============================================================
//  CODE ARENA - Admin Activity Logger
// ============================================================

require_once __DIR__ . '/security.php';

function logAdminAction(
    int $admin_id,
    string $action,
    string $target_type,
    ?int $target_id = null,
    string $description = ''
): void {
    global $pdo;

    try {
        if (!$pdo instanceof PDO) {
            throw new RuntimeException('PDO connection is not available.');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO admin_activity_logs
                (admin_id, action, target_type, target_id, description, ip_address)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $admin_id,
            cleanString($action, 100),
            cleanString($target_type, 50),
            $target_id,
            cleanString($description, 2000),
            clientIp(),
        ]);
    } catch (Throwable $e) {
        error_log('admin activity log failed: ' . $e->getMessage());
    }
}
