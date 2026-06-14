<?php
// ============================================================
//  CODE ARENA - Contest Manager API
//  GET  ?contest_id=X
//  POST { action, contest_id, ... }
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';

requireInstructor();

$method = $_SERVER['REQUEST_METHOD'];
$userId = currentUserId();

function loadManagedContest(PDO $pdo, int $contestId, int $userId): array {
    $stmt = $pdo->prepare(
        'SELECT c.*, u.username AS author
         FROM contests c JOIN users u ON u.id = c.created_by AND COALESCE(u.is_deleted, 0) = 0
         WHERE c.id = ?'
    );
    $stmt->execute([$contestId]);
    $contest = $stmt->fetch();
    if (!$contest) err('Contest not found', 404);
    if (!isAdmin() && (int)$contest['created_by'] !== $userId) err('Forbidden', 403);
    return $contest;
}

function contestPayload(PDO $pdo, array $contest): array {
    $pStmt = $pdo->prepare(
        'SELECT cp.id AS contest_problem_id, cp.problem_id, cp.points, cp.order_index,
                p.title, p.slug, p.difficulty
         FROM contest_problems cp
         JOIN problems p ON p.id = cp.problem_id AND COALESCE(p.is_deleted, 0) = 0
         WHERE cp.contest_id = ?
         ORDER BY cp.order_index, cp.id'
    );
    $pStmt->execute([$contest['id']]);

    $partStmt = $pdo->prepare(
        'SELECT cp.id AS participant_id, cp.user_id, u.username, u.email, cp.registered_at
         FROM contest_participants cp
         JOIN users u ON u.id = cp.user_id AND COALESCE(u.is_deleted, 0) = 0
         WHERE cp.contest_id = ?
         ORDER BY cp.registered_at DESC'
    );
    $partStmt->execute([$contest['id']]);

    return [
        'contest' => $contest,
        'problems' => $pStmt->fetchAll(),
        'participants' => $partStmt->fetchAll(),
    ];
}

if ($method === 'GET') {
    $contestId = (int)($_GET['contest_id'] ?? 0);
    if (!$contestId) err('contest_id required');
    $contest = loadManagedContest($pdo, $contestId, $userId);
    ok(contestPayload($pdo, $contest));
}

if ($method !== 'POST') err('Method not allowed', 405);

$body = jsonBody();
$action = $body['action'] ?? '';
$contestId = (int)($body['contest_id'] ?? 0);
if (!$contestId || !$action) err('contest_id and action are required');

$contest = loadManagedContest($pdo, $contestId, $userId);
if ($contest['status'] === 'active' && in_array($action, ['update_contest', 'add_problem', 'remove_problem', 'update_problem'])) {
    err('Cannot modify contest setup while contest is active', 403);
}

if ($action === 'update_contest') {
    $title = trim($body['title'] ?? '');
    $start = trim($body['start_time'] ?? '');
    $end = trim($body['end_time'] ?? '');
    if (!$title || !$start || !$end) err('title, start_time and end_time are required');

    $pdo->prepare(
        'UPDATE contests SET title = ?, description = ?, start_time = ?, end_time = ?, is_rated = ?
         WHERE id = ?'
    )->execute([
        $title,
        $body['description'] ?? '',
        $start,
        $end,
        isset($body['is_rated']) ? (int)(bool)$body['is_rated'] : 1,
        $contestId,
    ]);
    ok(null, 'Contest updated');
}

if ($action === 'add_problem') {
    $problemId = (int)($body['problem_id'] ?? 0);
    $points = max(1, (int)($body['points'] ?? 100));
    if (!$problemId) err('problem_id required');

    $check = $pdo->prepare('SELECT id FROM problems WHERE id = ? AND is_public = 1 AND COALESCE(is_deleted, 0) = 0');
    $check->execute([$problemId]);
    if (!$check->fetchColumn()) err('Problem not found', 404);

    $orderStmt = $pdo->prepare('SELECT COALESCE(MAX(order_index), 0) + 1 FROM contest_problems WHERE contest_id = ?');
    $orderStmt->execute([$contestId]);
    $orderIndex = (int)$orderStmt->fetchColumn();

    try {
        $pdo->prepare(
            'INSERT INTO contest_problems (contest_id, problem_id, points, order_index)
             VALUES (?, ?, ?, ?)'
        )->execute([$contestId, $problemId, $points, $orderIndex]);
    } catch (PDOException $e) {
        err('Problem is already in this contest');
    }
    ok(null, 'Problem added');
}

if ($action === 'update_problem') {
    $contestProblemId = (int)($body['contest_problem_id'] ?? 0);
    $points = max(1, (int)($body['points'] ?? 100));
    $orderIndex = max(1, (int)($body['order_index'] ?? 1));
    if (!$contestProblemId) err('contest_problem_id required');

    $pdo->prepare(
        'UPDATE contest_problems SET points = ?, order_index = ?
         WHERE id = ? AND contest_id = ?'
    )->execute([$points, $orderIndex, $contestProblemId, $contestId]);
    ok(null, 'Problem updated');
}

if ($action === 'remove_problem') {
    $contestProblemId = (int)($body['contest_problem_id'] ?? 0);
    if (!$contestProblemId) err('contest_problem_id required');
    $pdo->prepare('DELETE FROM contest_problems WHERE id = ? AND contest_id = ?')
        ->execute([$contestProblemId, $contestId]);
    ok(null, 'Problem removed');
}

if ($action === 'remove_participant') {
    $participantId = (int)($body['participant_id'] ?? 0);
    if (!$participantId) err('participant_id required');
    $pdo->prepare('DELETE FROM contest_participants WHERE id = ? AND contest_id = ?')
        ->execute([$participantId, $contestId]);
    ok(null, 'Participant removed');
}

err('Unknown action', 400);
