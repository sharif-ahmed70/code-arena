<?php
// ============================================================
//  CODE ARENA — Submissions List / Single
//  GET /api/submissions/verdict.php           → my submissions
//  GET /api/submissions/verdict.php?id=N      → single submission
//  GET /api/submissions/verdict.php?problem_id=N
//  Admins can pass ?all=1 to see everyone's
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';

methodCheck('GET');
requireLogin();

$userId  = currentUserId();
$isAdmin = isAdmin();

// ── Single submission ────────────────────────────────────────
if (!empty($_GET['id'])) {
    $id   = (int) $_GET['id'];
    $stmt = $pdo->prepare(
        'SELECT s.*, p.title AS problem_title, p.slug AS problem_slug,
                u.username
         FROM submissions s
         JOIN problems p ON p.id = s.problem_id
         JOIN users    u ON u.id = s.user_id
         WHERE s.id = ?'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row)                                    err('Submission not found', 404);
    if (!$isAdmin && $row['user_id'] !== $userId) err('Forbidden', 403);
    $row['test_results'] = json_decode($row['test_results'] ?? '[]', true) ?: [];
    ok($row);
}

// ── List ─────────────────────────────────────────────────────
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 30;
$offset  = ($page - 1) * $perPage;

$where  = [];
$params = [];

if (!$isAdmin || empty($_GET['all'])) {
    $where[]  = 's.user_id = ?';
    $params[] = $userId;
}
if (!empty($_GET['problem_id'])) {
    $where[]  = 's.problem_id = ?';
    $params[] = (int) $_GET['problem_id'];
}
if (!empty($_GET['status'])) {
    $where[]  = 's.status = ?';
    $params[] = $_GET['status'];
}
if (!empty($_GET['language'])) {
    $where[]  = 's.language = ?';
    $params[] = $_GET['language'];
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countSt = $pdo->prepare("SELECT COUNT(*) FROM submissions s $whereSQL");
$countSt->execute($params);
$total = (int) $countSt->fetchColumn();

$listSt = $pdo->prepare(
    "SELECT s.id, s.user_id, s.problem_id, s.language, s.status,
            s.hints_used, s.runtime_ms, s.submitted_at, s.is_practice,
            p.title AS problem_title, p.slug AS problem_slug, p.difficulty,
            u.username
     FROM submissions s
     JOIN problems p ON p.id = s.problem_id
     JOIN users    u ON u.id = s.user_id
     $whereSQL
     ORDER BY s.submitted_at DESC
     LIMIT $perPage OFFSET $offset"
);
$listSt->execute($params);
$submissions = $listSt->fetchAll();

ok([
    'submissions' => $submissions,
    'total'       => $total,
    'page'        => $page,
    'per_page'    => $perPage,
    'pages'       => (int) ceil($total / $perPage),
]);
