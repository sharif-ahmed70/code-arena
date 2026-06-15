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

ok([
    'summary' => $summary,
    'difficulty' => $difficulty,
    'most_solved_problems' => $mostSolved,
    'engagement' => $engagement,
]);
