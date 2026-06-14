<?php
// ============================================================
//  CODE ARENA — Delete Contest (admin only)
//  POST/DELETE  /api/contests/delete.php  { contest_id: X }
// ============================================================
require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'])) err('Method not allowed', 405);
if (!isLoggedIn()) err('Login required', 401);
if (!isAdmin())    err('Admin access required', 403);

$body      = jsonBody();
$contestId = (int) ($body['contest_id'] ?? 0);
if (!$contestId) err('contest_id required');

$stmt = $pdo->prepare('SELECT id, status FROM contests WHERE id = ?');
$stmt->execute([$contestId]);
$contest = $stmt->fetch();
if (!$contest)                      err('Contest not found', 404);
if ($contest['status'] === 'active') err('Cannot delete a live contest', 403);

try {
    $pdo->beginTransaction();
    $pdo->prepare('DELETE FROM contest_problems     WHERE contest_id = ?')->execute([$contestId]);
    $pdo->prepare('DELETE FROM contest_participants WHERE contest_id = ?')->execute([$contestId]);
    $pdo->prepare('DELETE FROM contests             WHERE id = ?')->execute([$contestId]);
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    err('Deletion failed, please try again', 500);
}

ok(null, 'Contest deleted');
