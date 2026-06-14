<?php
// ============================================================
//  CODE ARENA — Login
//  File: api/auth/login.php
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';
require_once '../../includes/rate_limit.php';

methodCheck('POST');

$body = jsonBody();
$identifier = cleanString($body['identifier'] ?? '', 100);
$password   = (string)($body['password'] ?? '');

if (!$identifier || !$password) err('All fields are required');

enforceRateLimit($pdo, rateLimitKey('login', $identifier), 8, 15 * 60);

try {
    $stmt = $pdo->prepare(
        'SELECT id, username, email, password, role, COALESCE(is_blocked, 0) AS is_blocked
         FROM users WHERE username = ? OR email = ?'
    );
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        appLog($pdo, 'login_failed', ['identifier' => $identifier]);
        err('Invalid username or password', 401);
    }
    if ((int)$user['is_blocked'] === 1) {
        appLog($pdo, 'blocked_login_attempt', ['user_id' => (int)$user['id']]);
        err('This account is blocked', 403);
    }

    loginUser($user);
    appLog($pdo, 'login_success', ['user_id' => (int)$user['id']]);

    ok([
        'user' => [
            'id'       => (int)$user['id'],
            'username' => $user['username'],
            'role'     => $user['role'],
        ],
    ], 'Login successful');
} catch (PDOException $e) {
    error_log('login failed: ' . $e->getMessage());
    err('Login failed. Please try again.', 500);
}
