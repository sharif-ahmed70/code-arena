<?php
// ============================================================
//  Organization Submission Control API
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/organization.php';
require_once '../../includes/response.php';

$org = requireOrganizationApi($pdo);
$orgId = (int)$org['id'];
methodCheck('GET');

$contestId = (int)($_GET['contest_id'] ?? 0);
$user = cleanString($_GET['user'] ?? '', 100);
$problem = cleanString($_GET['problem'] ?? '', 100);
$status = cleanString($_GET['status'] ?? '', 40);

$where = ['s.org_id = ?'];
$params = [$orgId];
if ($contestId) {
    requireOwnedContest($pdo, $orgId, $contestId);
    $where[] = 's.contest_id = ?';
    $params[] = $contestId;
}
if ($user) {
    $where[] = '(u.username LIKE ? OR u.email LIKE ? OR u.name LIKE ?)';
    $params[] = "%$user%";
    $params[] = "%$user%";
    $params[] = "%$user%";
}
if ($problem) {
    $where[] = '(p.title LIKE ? OR p.slug LIKE ?)';
    $params[] = "%$problem%";
    $params[] = "%$problem%";
}
if ($status) {
    if (!in_array($status, ['Accepted', 'Wrong Answer', 'Runtime Error', 'Time Limit Exceeded', 'Compilation Error', 'Pending'], true)) err('Invalid status');
    $where[] = 's.status = ?';
    $params[] = $status;
}

$stmt = $pdo->prepare(
    'SELECT s.id, s.user_id, s.contest_id, s.problem_id, s.language, s.status, s.score,
            s.runtime_ms, s.hints_used, s.submitted_at,
            u.username, u.email, p.title AS problem_title, c.title AS contest_title
     FROM submissions s
     JOIN users u ON u.id = s.user_id AND COALESCE(u.is_deleted, 0) = 0
     JOIN problems p ON p.id = s.problem_id
     JOIN contests c ON c.id = s.contest_id AND c.org_id = s.org_id
     WHERE ' . implode(' AND ', $where) . '
     ORDER BY s.submitted_at DESC
     LIMIT 300'
);
$stmt->execute($params);

$suspiciousStmt = $pdo->prepare(
    'SELECT user_id, contest_id, COUNT(*) AS submission_count
     FROM submissions
     WHERE org_id = ? AND submitted_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
     GROUP BY user_id, contest_id
     HAVING submission_count >= 10'
);
$suspiciousStmt->execute([$orgId]);

ok([
    'submissions' => $stmt->fetchAll(),
    'suspicious_activity' => $suspiciousStmt->fetchAll(),
]);
