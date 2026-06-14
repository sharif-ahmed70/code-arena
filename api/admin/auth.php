<?php
// ============================================================
//  CODE ARENA - Admin Authentication API
//  GET    /api/admin/auth.php    -> current admin session
//  POST   /api/admin/auth.php    -> admin login
//  DELETE /api/admin/auth.php    -> logout
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';
require_once '../../includes/rate_limit.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (!isLoggedIn() || !isAdmin()) err('Admin login required', 401);
    ok([
        'admin' => [
            'id' => currentUserId(),
            'username' => currentUsername(),
            'role' => currentRole(),
        ],
    ]);
}

if ($method === 'POST') {
    $body = jsonBody();
    $identifier = cleanString($body['identifier'] ?? '', 100);
    $password = (string)($body['password'] ?? '');

    if (!$identifier || !$password) err('Identifier and password are required');

    enforceRateLimit($pdo, rateLimitKey('admin_login', $identifier), 6, 15 * 60);

    $stmt = $pdo->prepare(
        'SELECT id, username, email, password, role, COALESCE(is_blocked, 0) AS is_blocked
         FROM users
         WHERE (username = ? OR email = ?) AND COALESCE(is_deleted, 0) = 0'
    );
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        appLog($pdo, 'admin_login_failed', ['identifier' => $identifier]);
        err('Invalid admin credentials', 401);
    }
    if ($user['role'] !== 'admin') err('Admin access required', 403);
    if ((int)$user['is_blocked'] === 1) err('This admin account is blocked', 403);

    loginUser($user);
    appLog($pdo, 'admin_login_success', ['admin_id' => (int)$user['id']]);

    ok([
        'admin' => [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
        ],
    ], 'Admin login successful');
}

if ($method === 'DELETE') {
    logoutUser();
    ok(null, 'Logged out');
}

err('Method not allowed', 405);
