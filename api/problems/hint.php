<?php
// ============================================================
//  CODE ARENA — Hint Unlock
//  POST /api/problems/hint.php  {problem_id, tier}
//  Returns hint text for the requested tier (1-3).
//  Tracks usage in session; client includes count at submit time.
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';

methodCheck('POST');
requireLogin();
if (isAdmin()) err('Admins cannot unlock hints', 403);

$body      = jsonBody();
$problemId = (int) ($body['problem_id'] ?? 0);
$tier      = (int) ($body['tier']       ?? 0);

if (!$problemId || !in_array($tier, [1, 2, 3])) err('Invalid request');

$col  = "hint_tier$tier";
$stmt = $pdo->prepare("SELECT id, $col AS hint FROM problems WHERE id = ? AND is_public = 1 AND COALESCE(is_deleted, 0) = 0");
$stmt->execute([$problemId]);
$row = $stmt->fetch();

if (!$row)         err('Problem not found', 404);
if (!$row['hint']) err('No hint available for this tier');

// Track highest tier unlocked for this problem in session
$key = 'hints_' . $problemId;
$prev = $_SESSION[$key] ?? 0;
if ($tier > $prev) $_SESSION[$key] = $tier;

ok(['hint' => $row['hint'], 'tier' => $tier, 'hints_used' => $_SESSION[$key]]);
