<?php
// ============================================================
//  CODE ARENA - Complete Profile
//  POST /api/users/complete_profile.php
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';

methodCheck('POST');
requireLogin();
if (isAdmin()) err('Admins do not use user profile completion', 403);

$body = jsonBody();
$name = cleanString($body['name'] ?? '', 120);
$country = cleanString($body['country'] ?? '', 80);
$university = cleanString($body['university'] ?? '', 160);

if (!$name || !$country) err('Name and country are required');

$stmt = $pdo->prepare(
    'UPDATE users
     SET name = ?, country = ?, university = ?, profile_completed = 1
     WHERE id = ? AND COALESCE(is_deleted, 0) = 0'
);
$stmt->execute([$name, $country, $university ?: null, currentUserId()]);
$_SESSION['profile_completed'] = 1;

ok(['redirect_url' => authDashboardPath(currentRole())], 'Profile completed');
