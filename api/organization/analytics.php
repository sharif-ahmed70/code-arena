<?php
// ============================================================
//  Organization Analytics API
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/organization.php';
require_once '../../includes/response.php';

$org = requireOrganizationApi($pdo);
$orgId = (int)$org['id'];
methodCheck('GET');

function orgRows(PDO $pdo, string $sql, array $params): array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

$summary = orgRows(
    $pdo,
    'SELECT COUNT(DISTINCT c.id) AS contests,
            COUNT(DISTINCT cp.user_id) AS participants,
            COUNT(DISTINCT s.id) AS submissions,
            SUM(s.status = "Accepted") AS accepted
     FROM contests c
     LEFT JOIN contest_participants cp ON cp.contest_id = c.id AND cp.org_id = c.org_id
     LEFT JOIN submissions s ON s.contest_id = c.id AND s.org_id = c.org_id
     WHERE c.org_id = ?',
    [$orgId]
)[0] ?? [];

$difficulty = orgRows(
    $pdo,
    'SELECT p.difficulty,
            COUNT(s.id) AS submissions,
            SUM(s.status = "Accepted") AS accepted
     FROM submissions s
     JOIN problems p ON p.id = s.problem_id
     WHERE s.org_id = ?
     GROUP BY p.difficulty
     ORDER BY FIELD(p.difficulty, "Easy", "Medium", "Hard")',
    [$orgId]
);

$mostSolved = orgRows(
    $pdo,
    'SELECT p.id, p.title, p.difficulty, COUNT(s.id) AS accepted_count
     FROM submissions s
     JOIN problems p ON p.id = s.problem_id
     WHERE s.org_id = ? AND s.status = "Accepted"
     GROUP BY p.id
     ORDER BY accepted_count DESC
     LIMIT 10',
    [$orgId]
);

$engagement = orgRows(
    $pdo,
    'SELECT DATE(s.submitted_at) AS day, COUNT(*) AS submissions, COUNT(DISTINCT s.user_id) AS users
     FROM submissions s
     WHERE s.org_id = ? AND s.submitted_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
     GROUP BY DATE(s.submitted_at)
     ORDER BY day ASC',
    [$orgId]
);

$contestParticipation = orgRows(
    $pdo,
    'SELECT c.id, c.title,
            COUNT(DISTINCT cp.user_id) AS participants,
            COUNT(DISTINCT s.id) AS submissions,
            SUM(s.status = "Accepted") AS accepted
     FROM contests c
     LEFT JOIN contest_participants cp
        ON cp.contest_id = c.id
       AND (cp.org_id = c.org_id OR cp.org_id IS NULL)
       AND COALESCE(cp.status, "registered") NOT IN ("removed", "banned", "rejected")
     LEFT JOIN submissions s
        ON s.contest_id = c.id
       AND (s.org_id = c.org_id OR s.org_id IS NULL)
       AND COALESCE(s.is_practice, 0) = 0
     WHERE c.org_id = ?
     GROUP BY c.id
     ORDER BY c.start_time DESC
     LIMIT 10',
    [$orgId]
);

$verdictDistribution = orgRows(
    $pdo,
    'SELECT status AS verdict, COUNT(*) AS total
     FROM submissions
     WHERE org_id = ? AND COALESCE(is_practice, 0) = 0
     GROUP BY status
     ORDER BY total DESC',
    [$orgId]
);

$problemPerformance = orgRows(
    $pdo,
    'SELECT p.id, p.title, p.difficulty,
            COUNT(s.id) AS submissions,
            SUM(s.status = "Accepted") AS accepted,
            ROUND(CASE WHEN COUNT(s.id) = 0 THEN 0 ELSE SUM(s.status = "Accepted") * 100 / COUNT(s.id) END) AS success_rate
     FROM submissions s
     JOIN problems p ON p.id = s.problem_id
     WHERE s.org_id = ? AND COALESCE(s.is_practice, 0) = 0
     GROUP BY p.id
     HAVING submissions > 0
     ORDER BY submissions DESC, success_rate ASC
     LIMIT 10',
    [$orgId]
);

$topPerformers = orgRows(
    $pdo,
    'SELECT u.id, u.username,
            COUNT(s.id) AS submissions,
            SUM(s.status = "Accepted") AS accepted,
            ROUND(CASE WHEN COUNT(s.id) = 0 THEN 0 ELSE SUM(s.status = "Accepted") * 100 / COUNT(s.id) END) AS success_rate
     FROM submissions s
     JOIN users u ON u.id = s.user_id
     WHERE s.org_id = ? AND COALESCE(s.is_practice, 0) = 0
     GROUP BY u.id
     ORDER BY accepted DESC, success_rate DESC, submissions ASC
     LIMIT 10',
    [$orgId]
);

$hourlyActivity = orgRows(
    $pdo,
    'SELECT HOUR(submitted_at) AS hour, COUNT(*) AS submissions, COUNT(DISTINCT user_id) AS users
     FROM submissions
     WHERE org_id = ? AND COALESCE(is_practice, 0) = 0
     GROUP BY HOUR(submitted_at)
     ORDER BY hour ASC',
    [$orgId]
);

$hardestProblem = orgRows(
    $pdo,
    'SELECT p.title, p.difficulty,
            COUNT(s.id) AS submissions,
            SUM(s.status = "Accepted") AS accepted,
            ROUND(CASE WHEN COUNT(s.id) = 0 THEN 0 ELSE SUM(s.status = "Accepted") * 100 / COUNT(s.id) END) AS success_rate
     FROM submissions s
     JOIN problems p ON p.id = s.problem_id
     WHERE s.org_id = ? AND COALESCE(s.is_practice, 0) = 0
     GROUP BY p.id
     HAVING submissions >= 3
     ORDER BY success_rate ASC, submissions DESC
     LIMIT 1',
    [$orgId]
);

ok([
    'summary' => $summary,
    'difficulty' => $difficulty,
    'most_solved_problems' => $mostSolved,
    'engagement' => $engagement,
    'contest_participation' => $contestParticipation,
    'verdict_distribution' => $verdictDistribution,
    'problem_performance' => $problemPerformance,
    'top_performers' => $topPerformers,
    'hourly_activity' => $hourlyActivity,
    'hardest_problem' => $hardestProblem[0] ?? null,
]);
