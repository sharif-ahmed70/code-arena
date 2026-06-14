<?php
// ============================================================
//  CODE ARENA — Problem Status for Current User
//  GET /api/problems/status.php?problem_id=N
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';

methodCheck('GET');
requireLogin();

$problemId = (int) ($_GET['problem_id'] ?? 0);
if (!$problemId) err('problem_id required');

$userId = currentUserId();

$stmt = $pdo->prepare(
    'SELECT status, hints_used, submitted_at
     FROM submissions WHERE user_id = ? AND problem_id = ?
     ORDER BY submitted_at DESC LIMIT 1'
);
$stmt->execute([$userId, $problemId]);
$last = $stmt->fetch();

$status = 'unsolved';
if ($last) {
    $status = $last['status'] === 'Accepted' ? 'solved' : 'attempted';
}

ok(['status' => $status, 'last_submission' => $last]);
