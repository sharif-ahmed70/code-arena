<?php
// ============================================================
//  CODE ARENA - Leaderboard API
//  GET /api/leaderboard/index.php?sort=hardcore|learning|solved
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';

methodCheck('GET');

$sort = $_GET['sort'] ?? 'hardcore';
$allowedSort = [
    'hardcore' => 'u.hardcore_rating',
    'learning' => 'u.learning_rating',
    'solved'   => 'solved_count',
];
$sortSql = $allowedSort[$sort] ?? $allowedSort['hardcore'];

$stmt = $pdo->query(
    "SELECT
        u.id,
        u.username,
        u.role,
        u.hardcore_rating,
        u.learning_rating,
        COUNT(DISTINCT CASE WHEN s.status = 'Accepted' AND s.is_practice = 0 THEN s.problem_id END) AS solved_count,
        COUNT(CASE WHEN s.is_practice = 0 THEN s.id END) AS submission_count,
        MAX(s.submitted_at) AS last_submission_at
     FROM users u
     LEFT JOIN submissions s ON s.user_id = u.id
     WHERE u.role <> 'admin'
     GROUP BY u.id
     ORDER BY $sortSql DESC, solved_count DESC, u.username ASC
     LIMIT 100"
);

$rows = $stmt->fetchAll();
$rank = 1;
foreach ($rows as &$row) {
    $row['rank'] = $rank++;
    $row['hardcore_rating'] = (int) $row['hardcore_rating'];
    $row['learning_rating'] = (int) $row['learning_rating'];
    $row['solved_count'] = (int) $row['solved_count'];
    $row['submission_count'] = (int) $row['submission_count'];
}
unset($row);

ok(['users' => $rows, 'sort' => $sort]);
