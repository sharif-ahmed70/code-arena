<?php
// ============================================================
//  CODE ARENA - Admin Submission Monitoring API
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/adminAuthMiddleware.php';
require_once '../../includes/adminActivityLogger.php';

requireAdminApi();
methodCheck('GET');

if (!empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare(
        'SELECT s.*, u.username, u.email,
                p.title AS problem_title, p.slug AS problem_slug, p.difficulty
         FROM submissions s
         JOIN users u ON u.id = s.user_id
         JOIN problems p ON p.id = s.problem_id
         WHERE s.id = ?'
    );
    $stmt->execute([$id]);
    $submission = $stmt->fetch();
    if (!$submission) err('Submission not found', 404);
    $submission['test_results'] = json_decode($submission['test_results'] ?? '[]', true) ?: [];
    logAdminAction(
        currentUserId(),
        'VIEW_SUBMISSION_DEBUG',
        'submission',
        $id,
        'Viewed submission debug details for submission ID ' . $id
    );
    ok($submission);
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = min(100, max(10, (int)($_GET['per_page'] ?? 30)));
$offset = ($page - 1) * $perPage;

$status = cleanString($_GET['status'] ?? '', 40);
$language = cleanString($_GET['language'] ?? '', 30);
$user = cleanString($_GET['user'] ?? '', 100);
$userId = isset($_GET['user_id']) && $_GET['user_id'] !== '' ? (int)$_GET['user_id'] : 0;
$email = cleanString($_GET['email'] ?? '', 100);
$problem = cleanString($_GET['problem'] ?? '', 100);
$problemId = isset($_GET['problem_id']) && $_GET['problem_id'] !== '' ? (int)$_GET['problem_id'] : 0;
$startDate = cleanString($_GET['start_date'] ?? '', 10);
$endDate = cleanString($_GET['end_date'] ?? '', 10);

$where = [];
$params = [];

$statusMap = [
    'AC' => 'Accepted',
    'WA' => 'Wrong Answer',
    'TLE' => 'Time Limit Exceeded',
    'RE' => 'Runtime Error',
    'CE' => 'Compilation Error',
];
$normalizedStatus = strtoupper($status);
if (isset($statusMap[$normalizedStatus])) {
    $status = $statusMap[$normalizedStatus];
}

if ($status) {
    if (!in_array($status, ['Accepted', 'Wrong Answer', 'Runtime Error', 'Time Limit Exceeded', 'Compilation Error', 'Pending'], true)) {
        err('Invalid status filter');
    }
    $where[] = 's.status = ?';
    $params[] = $status;
}
if ($language) {
    $where[] = 's.language = ?';
    $params[] = $language;
}
if ($userId > 0) {
    $where[] = 's.user_id = ?';
    $params[] = $userId;
}
if ($user) {
    $where[] = '(u.username LIKE ? OR u.email LIKE ?)';
    $params[] = "%$user%";
    $params[] = "%$user%";
}
if ($email) {
    $where[] = 'u.email LIKE ?';
    $params[] = "%$email%";
}
if ($problemId > 0) {
    $where[] = 's.problem_id = ?';
    $params[] = $problemId;
}
if ($problem) {
    $where[] = '(p.title LIKE ? OR p.slug LIKE ?)';
    $params[] = "%$problem%";
    $params[] = "%$problem%";
}
if ($startDate) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) err('Invalid start_date');
    $where[] = 's.submitted_at >= ?';
    $params[] = $startDate . ' 00:00:00';
}
if ($endDate) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) err('Invalid end_date');
    $where[] = 's.submitted_at <= ?';
    $params[] = $endDate . ' 23:59:59';
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$count = $pdo->prepare(
    "SELECT COUNT(*)
     FROM submissions s
     JOIN users u ON u.id = s.user_id
     JOIN problems p ON p.id = s.problem_id
     $whereSQL"
);
$count->execute($params);
$total = (int)$count->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT s.id, s.user_id, s.problem_id, s.language, s.status, s.hints_used,
            s.runtime_ms, s.submitted_at, s.is_practice, s.contest_id,
            u.username, p.title AS problem_title, p.slug AS problem_slug, p.difficulty
     FROM submissions s
     JOIN users u ON u.id = s.user_id
     JOIN problems p ON p.id = s.problem_id
     $whereSQL
     ORDER BY s.submitted_at DESC
     LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);

ok([
    'submissions' => $stmt->fetchAll(),
    'total' => $total,
    'page' => $page,
    'per_page' => $perPage,
    'pages' => (int)ceil($total / $perPage),
]);
