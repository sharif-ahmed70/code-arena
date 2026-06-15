<?php
// ============================================================
//  Organization Announcements API
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/organization.php';
require_once '../../includes/response.php';
require_once '../../includes/announcements.php';

$org = requireOrganizationApi($pdo);
$orgId = (int)$org['id'];
$method = $_SERVER['REQUEST_METHOD'];
ensureAnnouncementsSchema($pdo);

if ($method === 'GET') {
    $contestId = (int)($_GET['contest_id'] ?? 0);
    $params = [$orgId];
    $where = 'WHERE a.org_id = ? AND a.target_type IN ("org", "contest")';
    if ($contestId) {
        requireOwnedContest($pdo, $orgId, $contestId);
        $where .= ' AND a.contest_id = ?';
        $params[] = $contestId;
    }
    $stmt = $pdo->prepare(
        "SELECT a.*, c.title AS contest_title
         FROM announcements a
         LEFT JOIN contests c ON c.id = a.contest_id AND c.org_id = a.org_id
         $where
         ORDER BY a.created_at DESC
         LIMIT 100"
    );
    $stmt->execute($params);
    ok(['announcements' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $body = jsonBody();
    $contestId = !empty($body['contest_id']) ? (int)$body['contest_id'] : null;
    $title = cleanString($body['title'] ?? '', 180);
    $message = cleanString($body['message'] ?? '', 10000);
    $type = cleanString($body['type'] ?? 'announcement', 30);
    $isPublished = isset($body['is_published']) ? (int)(bool)$body['is_published'] : 1;
    if (!$title || !$message) err('title and message are required');
    if (!in_array($type, ['announcement', 'clarification', 'instruction'], true)) err('Invalid announcement type');
    if ($contestId) requireOwnedContest($pdo, $orgId, $contestId);

    $stmt = $pdo->prepare(
        'INSERT INTO announcements (title, message, target_type, org_id, contest_id, type, is_published, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $targetType = $contestId ? 'contest' : 'org';
    $stmt->execute([$title, $message, $targetType, $orgId, $contestId, $type, $isPublished, currentUserId()]);
    created(['id' => (int)$pdo->lastInsertId()], 'Announcement created');
}

if ($method === 'DELETE') {
    $body = jsonBody();
    $id = (int)($body['id'] ?? 0);
    if (!$id) err('id required');
    $pdo->prepare('DELETE FROM announcements WHERE id = ? AND org_id = ?')->execute([$id, $orgId]);
    ok(null, 'Announcement deleted');
}

err('Method not allowed', 405);
