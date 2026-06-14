<?php
// ============================================================
//  CODE ARENA — Register
//  File: api/auth/register.php
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';
require_once '../../includes/rate_limit.php';

methodCheck('POST');

$body = jsonBody();
$username = cleanString($body['username'] ?? '', 50);
$email    = strtolower(cleanString($body['email'] ?? '', 100));
$password = (string)($body['password'] ?? '');

if (!$username || !$email || !$password) err('All fields are required');
if (!validUsername($username)) err('Username must be 3-50 characters and contain only letters, numbers, or underscores');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) err('Invalid email address');
if (!validPassword($password)) err('Password must be 8-255 characters');

enforceRateLimit($pdo, rateLimitKey('register', $email), 5, 60 * 60);

try {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$username, $email]);
    if ((int)$stmt->fetchColumn() > 0) err('Username or email already taken', 409);

    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $stmt   = $pdo->prepare('INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)');
    $stmt->execute([$username, $email, $hashed, 'student']);

    $newUserId = (int) $pdo->lastInsertId();
    loginUser([
        'id' => $newUserId,
        'username' => $username,
        'email' => $email,
        'role' => 'student',
    ]);
    appLog($pdo, 'register_success', ['user_id' => $newUserId]);

    created([
        'user' => [
            'id'       => $newUserId,
            'username' => $username,
            'role'     => 'student',
        ],
    ], 'Registration successful');
} catch (PDOException $e) {
    error_log('registration failed: ' . $e->getMessage());
    err('Registration failed, try again', 500);
}
