<?php
// ============================================================
//  CODE ARENA - Editorial Manager API
//  GET  ?problem_id=X
//  POST { problem_id, approach, complexity, reference_solution }
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';

requireInstructor();

$userId = currentUserId();
$method = $_SERVER['REQUEST_METHOD'];

function loadEditableProblem(PDO $pdo, int $problemId, int $userId): array {
    $stmt = $pdo->prepare('SELECT id, title, slug, difficulty, created_by FROM problems WHERE id = ?');
    $stmt->execute([$problemId]);
    $problem = $stmt->fetch();
    if (!$problem) err('Problem not found', 404);
    if (!isAdmin() && (int)$problem['created_by'] !== $userId) err('Forbidden', 403);
    return $problem;
}

if ($method === 'GET') {
    $problemId = (int)($_GET['problem_id'] ?? 0);
    if (!$problemId) err('problem_id required');

    $problem = loadEditableProblem($pdo, $problemId, $userId);
    $stmt = $pdo->prepare(
        'SELECT id, approach, complexity, reference_solution, updated_at
         FROM problem_editorials WHERE problem_id = ?'
    );
    $stmt->execute([$problemId]);
    $editorial = $stmt->fetch() ?: [
        'id' => null,
        'approach' => '',
        'complexity' => '',
        'reference_solution' => '',
        'updated_at' => null,
    ];

    ok(['problem' => $problem, 'editorial' => $editorial]);
}

if ($method === 'POST') {
    $body = jsonBody();
    $problemId = (int)($body['problem_id'] ?? 0);
    if (!$problemId) err('problem_id required');
    loadEditableProblem($pdo, $problemId, $userId);

    $approach = trim($body['approach'] ?? '');
    if ($approach === '') err('Approach is required');

    $complexity = trim($body['complexity'] ?? '');
    $reference = trim($body['reference_solution'] ?? '');

    $stmt = $pdo->prepare(
        'INSERT INTO problem_editorials (problem_id, approach, complexity, reference_solution)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            approach = VALUES(approach),
            complexity = VALUES(complexity),
            reference_solution = VALUES(reference_solution)'
    );
    $stmt->execute([$problemId, $approach, $complexity, $reference]);

    ok(null, 'Editorial saved');
}

err('Method not allowed', 405);
