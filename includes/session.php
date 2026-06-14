<?php
require_once __DIR__ . '/security.php';
// ============================================================
//  CODE ARENA — Session Helper
//  File: includes/session.php
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}
function requireLogin(): void {
    if (!isLoggedIn()) {
        if (str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Login required']);
            exit;
        }
        header('Location: /code-arena/login.php');
        exit;
    }
}
function loginUser(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id']  = (int) $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email']    = $user['email'] ?? null;
    $_SESSION['role']     = $user['role'];
}
function logoutUser(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
function requireAdmin(): void {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: /code-arena/problems.php');
        exit;
    }
}
function requireInstructor(): void {
    requireLogin();
    if (!in_array($_SESSION['role'], ['admin', 'instructor'])) {
        header('Location: /code-arena/problems.php');
        exit;
    }
}
function currentUserId(): ?int {
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}
function currentUsername(): ?string {
    return $_SESSION['username'] ?? null;
}
function currentRole(): ?string {
    return $_SESSION['role'] ?? null;
}
function isAdmin(): bool {
    return ($_SESSION['role'] ?? '') === 'admin';
}
function isInstructor(): bool {
    return in_array($_SESSION['role'] ?? '', ['admin', 'instructor']);
}
