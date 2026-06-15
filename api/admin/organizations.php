<?php
// ============================================================
//  CODE ARENA - Admin Organization Management API
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/adminAuthMiddleware.php';
require_once '../../includes/adminActivityLogger.php';

requireAdminApi();

$method = $_SERVER['REQUEST_METHOD'];

function adminEnsureOrganizationDeleteColumn(PDO $pdo): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    try {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?'
        );
        $stmt->execute(['organizations', 'is_deleted']);
        if ((int)$stmt->fetchColumn() === 0) {
            $pdo->exec('ALTER TABLE organizations ADD COLUMN is_deleted TINYINT(1) NOT NULL DEFAULT 0');
        }
    } catch (Throwable $e) {
        error_log('admin organization schema check failed: ' . $e->getMessage());
        err('Organization schema check failed', 500);
    }
}

function adminOrganizationColumnExists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?
           AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

adminEnsureOrganizationDeleteColumn($pdo);

if ($method === 'GET') {
    $search = cleanString($_GET['search'] ?? '', 100);
    $where = ['COALESCE(o.is_deleted, 0) = 0'];
    $params = [];
    if ($search) {
        $where[] = '(o.name LIKE ? OR o.type LIKE ? OR u.username LIKE ? OR u.email LIKE ?)';
        $params = ["%$search%", "%$search%", "%$search%", "%$search%"];
    }
    $whereSql = 'WHERE ' . implode(' AND ', $where);

    $stmt = $pdo->prepare(
        "SELECT o.id, o.name, o.type, o.description, o.logo, o.owner_id, o.created_at,
                u.username AS owner_username, u.email AS owner_email,
                COUNT(DISTINCT om.user_id) AS member_count,
                COUNT(DISTINCT c.id) AS contest_count
         FROM organizations o
         LEFT JOIN users u ON u.id = o.owner_id
         LEFT JOIN organization_members om ON om.org_id = o.id
         LEFT JOIN contests c ON c.org_id = o.id
         $whereSql
         GROUP BY o.id
         ORDER BY o.created_at DESC
         LIMIT 200"
    );
    $stmt->execute($params);
    ok(['organizations' => $stmt->fetchAll()]);
}

if ($method === 'PUT') {
    $body = jsonBody();
    $id = (int)($body['id'] ?? 0);
    $name = cleanString($body['name'] ?? '', 160);
    $type = cleanString($body['type'] ?? '', 30);
    $description = cleanString($body['description'] ?? '', 1000);
    if (!$id || !$name || !in_array($type, ['university', 'company', 'community'], true)) {
        err('id, name and valid type are required');
    }

    $stmt = $pdo->prepare('UPDATE organizations SET name = ?, type = ?, description = ? WHERE id = ?');
    $stmt->execute([$name, $type, $description ?: null, $id]);
    logAdminAction(currentUserId(), 'UPDATE_ORGANIZATION', 'organization', $id, "Updated organization $name");
    ok(null, 'Organization updated');
}

if ($method === 'DELETE') {
    $body = jsonBody();
    $id = (int)($body['id'] ?? 0);
    if (!$id) err('id required');

    $stmt = $pdo->prepare('SELECT id, name, owner_id FROM organizations WHERE id = ? AND COALESCE(is_deleted, 0) = 0');
    $stmt->execute([$id]);
    $organization = $stmt->fetch();
    if (!$organization) err('Organization not found', 404);

    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE organizations SET is_deleted = 1 WHERE id = ?')->execute([$id]);
        $pdo->prepare('UPDATE users SET org_id = NULL WHERE org_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM organization_members WHERE org_id = ?')->execute([$id]);
        $contestUpdates = [];
        if (adminOrganizationColumnExists($pdo, 'contests', 'is_published')) {
            $contestUpdates[] = 'is_published = 0';
        }
        if (adminOrganizationColumnExists($pdo, 'contests', 'status')) {
            $contestUpdates[] = 'status = CASE WHEN status = "active" THEN "ended" ELSE status END';
        }
        if ($contestUpdates && adminOrganizationColumnExists($pdo, 'contests', 'org_id')) {
            $pdo->prepare('UPDATE contests SET ' . implode(', ', $contestUpdates) . ' WHERE org_id = ?')->execute([$id]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('admin organization delete failed: ' . $e->getMessage());
        err('Organization deletion failed', 500);
    }

    logAdminAction(currentUserId(), 'SOFT_DELETE_ORGANIZATION', 'organization', $id, 'Soft deleted organization ' . $organization['name']);
    appLog($pdo, 'admin_organization_deleted', ['organization_id' => $id]);
    ok(null, 'Organization deleted');
}

err('Method not allowed', 405);
