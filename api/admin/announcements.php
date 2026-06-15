<?php
// ============================================================
//  Code Arena - Admin Announcements API
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/adminAuthMiddleware.php';
require_once '../../includes/adminActivityLogger.php';
require_once '../../includes/announcements.php';

requireAdminApi();
ensureAnnouncementsSchema($pdo);

$method = $_SERVER['REQUEST_METHOD'];

function adminAnnouncementPayload(PDO $pdo): array {
    $body = jsonBody();
    $title = cleanString($body['title'] ?? '', 180);
    $message = cleanString($body['message'] ?? '', 10000);
    $targetType = cleanString($body['target_type'] ?? 'global', 20);
    $orgId = !empty($body['org_id']) ? (int)$body['org_id'] : null;
    $contestId = !empty($body['contest_id']) ? (int)$body['contest_id'] : null;
    $type = cleanString($body['type'] ?? 'announcement', 30);
    $isPublished = isset($body['is_published']) ? (int)(bool)$body['is_published'] : 1;

    if (!$title || !$message) err('title and message are required');
    if (!in_array($targetType, ['global', 'org', 'contest'], true)) err('Invalid target type');
    if (!in_array($type, ['announcement', 'clarification', 'instruction'], true)) err('Invalid announcement type');
    if ($targetType === 'global') {
        $orgId = null;
        $contestId = null;
    }
    if ($targetType === 'org' && !$orgId) err('Organization is required for org announcements');
    if ($targetType === 'contest' && !$contestId) err('Contest is required for contest announcements');

    if ($orgId) {
        $stmt = $pdo->prepare('SELECT id FROM organizations WHERE id = ?');
        $stmt->execute([$orgId]);
        if (!$stmt->fetch()) err('Organization not found', 404);
    }

    if ($contestId) {
        $stmt = $pdo->prepare('SELECT id, org_id FROM contests WHERE id = ?');
        $stmt->execute([$contestId]);
        $contest = $stmt->fetch();
        if (!$contest) err('Contest not found', 404);
        if (!empty($contest['org_id'])) {
            if ($orgId && (int)$orgId !== (int)$contest['org_id']) err('Contest does not belong to selected organization');
            $orgId = (int)$contest['org_id'];
        }
    }

    return compact('title', 'message', 'targetType', 'orgId', 'contestId', 'type', 'isPublished');
}

if ($method === 'GET') {
    $targetType = cleanString($_GET['target_type'] ?? '', 20);
    $search = cleanString($_GET['search'] ?? '', 100);
    $where = [];
    $params = [];

    if ($targetType) {
        if (!in_array($targetType, ['global', 'org', 'contest'], true)) err('Invalid target type');
        $where[] = 'a.target_type = ?';
        $params[] = $targetType;
    }
    if ($search) {
        $where[] = '(a.title LIKE ? OR a.message LIKE ? OR o.name LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $stmt = $pdo->prepare(
        "SELECT a.id, a.title, a.message, a.target_type, a.org_id, a.contest_id,
                a.type, a.is_published, a.created_by, a.created_at,
                o.name AS organization_name,
                c.title AS contest_title,
                u.username AS creator_username
         FROM announcements a
         LEFT JOIN organizations o ON o.id = a.org_id
         LEFT JOIN contests c ON c.id = a.contest_id
         LEFT JOIN users u ON u.id = a.created_by
         $whereSql
         ORDER BY a.created_at DESC, a.id DESC
         LIMIT 200"
    );
    $stmt->execute($params);
    ok(['announcements' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $p = adminAnnouncementPayload($pdo);
    $stmt = $pdo->prepare(
        'INSERT INTO announcements
            (title, message, target_type, org_id, contest_id, type, is_published, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $p['title'],
        $p['message'],
        $p['targetType'],
        $p['orgId'],
        $p['contestId'],
        $p['type'],
        $p['isPublished'],
        currentUserId(),
    ]);
    $id = (int)$pdo->lastInsertId();
    logAdminAction(currentUserId(), 'CREATE_ANNOUNCEMENT', 'announcement', $id, 'Created announcement: ' . $p['title']);
    appLog($pdo, 'admin_announcement_created', ['announcement_id' => $id, 'target_type' => $p['targetType']]);
    created(['id' => $id], 'Announcement created');
}

if ($method === 'PUT') {
    $body = jsonBody();
    $id = (int)($body['id'] ?? 0);
    if (!$id) err('id required');
    $p = adminAnnouncementPayload($pdo);

    $exists = $pdo->prepare('SELECT id FROM announcements WHERE id = ?');
    $exists->execute([$id]);
    if (!$exists->fetch()) err('Announcement not found', 404);

    $stmt = $pdo->prepare(
        'UPDATE announcements
         SET title = ?, message = ?, target_type = ?, org_id = ?, contest_id = ?,
             type = ?, is_published = ?
         WHERE id = ?'
    );
    $stmt->execute([
        $p['title'],
        $p['message'],
        $p['targetType'],
        $p['orgId'],
        $p['contestId'],
        $p['type'],
        $p['isPublished'],
        $id,
    ]);
    logAdminAction(currentUserId(), 'UPDATE_ANNOUNCEMENT', 'announcement', $id, 'Updated announcement: ' . $p['title']);
    ok(['id' => $id], 'Announcement updated');
}

if ($method === 'DELETE') {
    $body = jsonBody();
    $id = (int)($body['id'] ?? 0);
    if (!$id) err('id required');

    $stmt = $pdo->prepare('SELECT title FROM announcements WHERE id = ?');
    $stmt->execute([$id]);
    $title = $stmt->fetchColumn();
    if (!$title) err('Announcement not found', 404);

    $pdo->prepare('DELETE FROM announcements WHERE id = ?')->execute([$id]);
    logAdminAction(currentUserId(), 'DELETE_ANNOUNCEMENT', 'announcement', $id, 'Deleted announcement: ' . $title);
    appLog($pdo, 'admin_announcement_deleted', ['announcement_id' => $id]);
    ok(null, 'Announcement deleted');
}

err('Method not allowed', 405);
