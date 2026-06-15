<?php
// ============================================================
//  CODE ARENA - Admin Organization Management API
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/adminAuthMiddleware.php';
require_once '../../includes/adminActivityLogger.php';

requireAdminApi();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $search = cleanString($_GET['search'] ?? '', 100);
    $where = '';
    $params = [];
    if ($search) {
        $where = 'WHERE o.name LIKE ? OR o.type LIKE ? OR u.username LIKE ? OR u.email LIKE ?';
        $params = ["%$search%", "%$search%", "%$search%", "%$search%"];
    }

    $stmt = $pdo->prepare(
        "SELECT o.id, o.name, o.type, o.description, o.logo, o.owner_id, o.created_at,
                u.username AS owner_username, u.email AS owner_email,
                COUNT(DISTINCT om.user_id) AS member_count,
                COUNT(DISTINCT c.id) AS contest_count
         FROM organizations o
         LEFT JOIN users u ON u.id = o.owner_id
         LEFT JOIN organization_members om ON om.org_id = o.id
         LEFT JOIN contests c ON c.created_by = o.owner_id
         $where
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

err('Method not allowed', 405);
