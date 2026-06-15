<?php
// ============================================================
//  Code Arena - User Announcements Feed
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';
require_once '../../includes/announcements.php';

methodCheck('GET');
requireLogin();
ensureAnnouncementsSchema($pdo);
ensureAnnouncementReadsSchema($pdo);

$filter = cleanString($_GET['filter'] ?? 'all', 20);
if (!in_array($filter, ['all', 'global', 'org'], true)) err('Invalid filter');
$markRead = !empty($_GET['mark_read']);

$userId = currentUserId();
$params = [];
$where = ['COALESCE(a.is_published, 1) = 1'];

if (isRealAdmin()) {
    if ($filter === 'global') {
        $where[] = 'a.target_type = ?';
        $params[] = 'global';
    } elseif ($filter === 'org') {
        $where[] = 'a.target_type = ?';
        $params[] = 'org';
    }
} else {
    $orgIds = announcementUserOrgIds($pdo, (int)$userId);
    $visibility = ['a.target_type = ?'];
    $params[] = 'global';

    if ($orgIds) {
        $placeholders = implode(',', array_fill(0, count($orgIds), '?'));
        $visibility[] = "(a.target_type = ? AND a.org_id IN ($placeholders))";
        $params[] = 'org';
        foreach ($orgIds as $orgId) $params[] = $orgId;

        $visibility[] = "(a.target_type = ? AND a.org_id IN ($placeholders))";
        $params[] = 'contest';
        foreach ($orgIds as $orgId) $params[] = $orgId;
    }
    $visibility[] = '(
        a.target_type = ?
        AND a.contest_id IN (
            SELECT cp.contest_id
            FROM contest_participants cp
            WHERE cp.user_id = ?
              AND COALESCE(cp.status, "registered") NOT IN ("removed", "banned", "rejected")
        )
    )';
    $params[] = 'contest';
    $params[] = $userId;

    $where[] = '(' . implode(' OR ', $visibility) . ')';
    if ($filter === 'global') {
        $where[] = 'a.target_type = ?';
        $params[] = 'global';
    } elseif ($filter === 'org') {
        $where[] = 'a.target_type IN (?, ?)';
        $params[] = 'org';
        $params[] = 'contest';
    }
}

$stmt = $pdo->prepare(
    'SELECT a.id, a.title, a.message, a.target_type, a.org_id, a.contest_id,
            a.type, a.created_by, a.created_at,
            o.name AS organization_name,
            c.title AS contest_title,
            u.username AS creator_username
     FROM announcements a
     LEFT JOIN organizations o ON o.id = a.org_id
     LEFT JOIN contests c ON c.id = a.contest_id
     LEFT JOIN users u ON u.id = a.created_by
     WHERE ' . implode(' AND ', $where) . '
     ORDER BY a.created_at DESC, a.id DESC
     LIMIT 100'
);
$stmt->execute($params);
$announcements = $stmt->fetchAll();

if ($markRead && $announcements) {
    $insert = $pdo->prepare('INSERT IGNORE INTO announcement_reads (announcement_id, user_id) VALUES (?, ?)');
    foreach ($announcements as $announcement) {
        $insert->execute([(int)$announcement['id'], (int)$userId]);
    }
}

ok(['announcements' => $announcements]);
