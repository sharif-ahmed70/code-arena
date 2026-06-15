<?php
// ============================================================
//  Code Arena - Unread Announcement Count
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';
require_once '../../includes/announcements.php';

methodCheck('GET');
requireLogin();
ensureAnnouncementsSchema($pdo);
ensureAnnouncementReadsSchema($pdo);

$userId = (int)currentUserId();
$params = [$userId, 'global'];
$visibility = ['a.target_type = ?'];

if (!isRealAdmin()) {
    $orgIds = announcementUserOrgIds($pdo, $userId);
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
} else {
    $visibility[] = 'a.target_type IN ("org", "contest")';
}

$stmt = $pdo->prepare(
    'SELECT COUNT(*)
     FROM announcements a
     LEFT JOIN announcement_reads ar ON ar.announcement_id = a.id AND ar.user_id = ?
     WHERE COALESCE(a.is_published, 1) = 1
       AND ar.id IS NULL
       AND (' . implode(' OR ', $visibility) . ')'
);
$stmt->execute($params);

ok(['count' => (int)$stmt->fetchColumn()]);
