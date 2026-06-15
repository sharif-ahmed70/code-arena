<?php
// ============================================================
//  CODE ARENA - Dual Leaderboard API
//  GET /api/leaderboard/index.php?type=practice|contest
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';

methodCheck('GET');

$type = $_GET['type'] ?? 'practice';

if ($type === 'contest') {
    $contestId = (int)($_GET['contest_id'] ?? 0);
    if (!$contestId) {
        $latest = $pdo->query(
            'SELECT contest_id FROM contest_leaderboard
             GROUP BY contest_id
             ORDER BY MAX(created_at) DESC
             LIMIT 1'
        )->fetchColumn();
        $contestId = (int)$latest;
    }

    if (!$contestId) {
        ok(['type' => 'contest', 'contest' => null, 'rows' => []]);
    }

    $contestStmt = $pdo->prepare('SELECT id, title, status, start_time, end_time FROM contests WHERE id = ?');
    $contestStmt->execute([$contestId]);
    $contest = $contestStmt->fetch();
    if (!$contest) err('Contest not found', 404);

    $stmt = $pdo->prepare(
        'SELECT
            cl.`rank`, cl.score, cl.penalty, cl.solved_count, cl.rating_change,
            cl.created_at, u.id AS user_id, u.username
         FROM contest_leaderboard cl
         JOIN users u ON u.id = cl.user_id AND COALESCE(u.is_deleted, 0) = 0
         WHERE cl.contest_id = ?
         ORDER BY cl.`rank` ASC, cl.penalty ASC, u.username ASC
         LIMIT 200'
    );
    $stmt->execute([$contestId]);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$row) {
        $row['rank'] = (int)$row['rank'];
        $row['score'] = (int)$row['score'];
        $row['penalty'] = (int)$row['penalty'];
        $row['solved_count'] = (int)$row['solved_count'];
        $row['rating_change'] = (int)$row['rating_change'];
        $row['user_id'] = (int)$row['user_id'];
    }
    unset($row);

    ok(['type' => 'contest', 'contest' => $contest, 'rows' => $rows]);
}

$sort = $_GET['sort'] ?? 'rating';
$allowedSort = [
    'rating' => 'COALESCE(u.skill_rating, u.hardcore_rating, 1200)',
    'solved' => 'ups.total_solved',
    'accuracy' => 'ups.accuracy',
    'streak' => 'ups.streak_days',
    'contest' => 'COALESCE(u.contest_rating, 1200)',
];
$sortSql = $allowedSort[$sort] ?? $allowedSort['rating'];

$stmt = $pdo->query(
    "SELECT
        u.id,
        u.username,
        u.role,
        COALESCE(u.skill_rating, u.hardcore_rating, 1200) AS skill_rating,
        COALESCE(u.skill_mode, 'hardcore') AS skill_mode,
        COALESCE(u.contest_rating, 1200) AS contest_rating,
        u.hardcore_rating,
        u.learning_rating,
        COALESCE(ups.total_solved, 0) AS total_solved,
        COALESCE(ups.total_solved, 0) AS solved_count,
        COALESCE(ups.accuracy, 0) AS accuracy,
        COALESCE(ups.streak_days, 0) AS streak_days,
        COALESCE(ups.rating, 1200) AS practice_rating,
        ups.last_active_date,
        COALESCE(ps.submission_count, 0) AS submission_count,
        ps.last_submission_at
     FROM users u
     LEFT JOIN user_practice_stats ups ON ups.user_id = u.id
     LEFT JOIN (
        SELECT user_id, COUNT(*) AS submission_count, MAX(submitted_at) AS last_submission_at
        FROM practice_submissions
        GROUP BY user_id
     ) ps ON ps.user_id = u.id
     WHERE u.role <> 'admin' AND COALESCE(u.is_deleted, 0) = 0
     ORDER BY $sortSql DESC, ups.total_solved DESC, u.username ASC
     LIMIT 100"
);

$rows = $stmt->fetchAll();
$rank = 1;
foreach ($rows as &$row) {
    $row['rank'] = $rank++;
    $row['id'] = (int)$row['id'];
    $row['skill_rating'] = (int)$row['skill_rating'];
    $row['contest_rating'] = (int)$row['contest_rating'];
    $row['hardcore_rating'] = (int)$row['hardcore_rating'];
    $row['learning_rating'] = (int)$row['learning_rating'];
    $row['total_solved'] = (int)$row['total_solved'];
    $row['solved_count'] = (int)$row['solved_count'];
    $row['accuracy'] = (float)$row['accuracy'];
    $row['streak_days'] = (int)$row['streak_days'];
    $row['practice_rating'] = (int)$row['practice_rating'];
    $row['submission_count'] = (int)$row['submission_count'];
}
unset($row);

ok(['type' => 'practice', 'users' => $rows, 'sort' => $sort]);
