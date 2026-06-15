<?php
// ============================================================
//  CODE ARENA - Skill Mode Toggle
//  POST /api/users/skill_mode.php  {mode: "hardcore"|"learning"}
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';

methodCheck('POST');
requireLogin();
if (isAdmin()) err('Admins cannot change skill mode', 403);

$body = jsonBody();
$mode = cleanString($body['mode'] ?? '', 20);
if (!in_array($mode, ['hardcore', 'learning'], true)) {
    err('Invalid skill mode');
}

$stmt = $pdo->prepare(
    'UPDATE users SET skill_mode = ? WHERE id = ? AND COALESCE(is_deleted, 0) = 0'
);
$stmt->execute([$mode, currentUserId()]);

ok(['skill_mode' => $mode], 'Skill mode updated');
