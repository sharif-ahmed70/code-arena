<?php
// ============================================================
//  CODE ARENA - Admin Analytics API
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/adminAuthMiddleware.php';

requireAdminApi();
methodCheck('GET');

function rows(PDO $pdo, string $sql, array $params = []): array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

$userGrowth = rows(
    $pdo,
    'SELECT DATE(created_at) AS day, COUNT(*) AS count
     FROM users
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
       AND COALESCE(is_deleted, 0) = 0
     GROUP BY DATE(created_at)
     ORDER BY day ASC'
);

$submissionTrend = rows(
    $pdo,
    'SELECT DATE(submitted_at) AS day, COUNT(*) AS count,
            SUM(status = "Accepted") AS accepted
     FROM submissions
     WHERE submitted_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
     GROUP BY DATE(submitted_at)
     ORDER BY day ASC'
);

$contestTrend = rows(
    $pdo,
    'SELECT DATE(c.start_time) AS day,
            COUNT(DISTINCT c.id) AS contests,
            COUNT(cp.id) AS participants
     FROM contests c
     LEFT JOIN contest_participants cp ON cp.contest_id = c.id
     WHERE c.start_time >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
     GROUP BY DATE(c.start_time)
     ORDER BY day ASC'
);

$roleBreakdown = rows(
    $pdo,
    'SELECT role, COUNT(*) AS count
     FROM users
     WHERE COALESCE(is_deleted, 0) = 0
     GROUP BY role
     ORDER BY count DESC'
);

ok([
    'user_growth' => $userGrowth,
    'submission_trend' => $submissionTrend,
    'contest_trend' => $contestTrend,
    'role_breakdown' => $roleBreakdown,
]);
