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
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'status' => 'error',
                'message' => 'Login required',
                'verdict' => 'AUTH_REQUIRED',
                'passed' => 0,
                'total' => 0,
            ], JSON_UNESCAPED_SLASHES);
            exit;
        }
        safeRedirect('/code-arena/login.php');
    }
}
function loginUser(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id']  = (int) $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email']    = $user['email'] ?? null;
    $_SESSION['role']     = $user['role'];
    $_SESSION['profile_completed'] = isset($user['profile_completed']) ? (int)$user['profile_completed'] : 1;
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
    if (!isRealAdmin()) {
        safeRedirect('/code-arena/problems.php');
    }
}
function requireInstructor(): void {
    requireLogin();
    if (!isInstructor()) {
        safeRedirect('/code-arena/problems.php');
    }
}
function safeRedirect(string $target): void {
    $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    $targetPath = parse_url($target, PHP_URL_PATH) ?: $target;
    $currentPath = rtrim($currentPath, '/') ?: '/';
    $targetPath = rtrim($targetPath, '/') ?: '/';
    if ($currentPath === $targetPath) return;
    header('Location: ' . $target);
    exit;
}
function currentUserId(): ?int {
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}
function currentUsername(): ?string {
    return $_SESSION['username'] ?? null;
}
function currentRole(): ?string {
    if (isRealAdmin()) {
        $context = adminViewMode();
        if ($context === 'user') return 'user';
        if ($context === 'org') return 'org_admin';
    }
    return $_SESSION['role'] ?? null;
}
function isRealAdmin(): bool {
    return ($_SESSION['role'] ?? '') === 'admin';
}
function realRole(): ?string {
    return $_SESSION['role'] ?? null;
}
function adminViewMode(): string {
    if (!isRealAdmin()) return '';
    $mode = $_SESSION['view_mode'] ?? ($_SESSION['admin_view_context'] ?? 'admin');
    return in_array($mode, ['admin', 'user', 'org'], true) ? $mode : 'admin';
}
function adminViewContext(): string {
    return adminViewMode();
}
function adminViewOrgId(): ?int {
    if (!isRealAdmin() || adminViewMode() !== 'org') return null;
    return isset($_SESSION['view_org_id']) ? (int)$_SESSION['view_org_id'] : null;
}
function adminViewUserId(): ?int {
    if (!isRealAdmin() || adminViewMode() !== 'user') return null;
    return isset($_SESSION['view_user_id']) ? (int)$_SESSION['view_user_id'] : null;
}
function setAdminViewContext(string $context, ?int $orgId = null, ?int $userId = null): void {
    if (!isRealAdmin()) return;
    $mode = in_array($context, ['admin', 'user', 'org'], true) ? $context : 'admin';
    $_SESSION['view_mode'] = $mode;
    $_SESSION['admin_view_context'] = $mode;
    unset($_SESSION['view_org_id'], $_SESSION['view_user_id']);
    if ($mode === 'org' && $orgId) {
        $_SESSION['view_org_id'] = $orgId;
    }
    if ($mode === 'user' && $userId) {
        $_SESSION['view_user_id'] = $userId;
    }
}
function clearAdminViewContext(): void {
    unset($_SESSION['admin_view_context'], $_SESSION['view_mode'], $_SESSION['view_org_id'], $_SESSION['view_user_id']);
}
function isAdmin(): bool {
    return isRealAdmin() && adminViewMode() === 'admin';
}
function isInstructor(): bool {
    return in_array(currentRole() ?? '', ['admin', 'instructor', 'org_admin'], true);
}
function isOrgAdmin(): bool {
    return ($_SESSION['role'] ?? '') === 'org_admin';
}
function isAdminOrgView(): bool {
    return isRealAdmin() && adminViewMode() === 'org';
}
function isAdminUserView(): bool {
    return isRealAdmin() && adminViewMode() === 'user';
}
function canAccessOrganizationDashboard(): bool {
    return isOrgAdmin() || isAdminOrgView();
}
function authDashboardPath(?string $role = null): string {
    $role = $role ?? currentRole();
    if (isRealAdmin() && $role === 'admin') return '/code-arena/admin.php';
    return match ($role) {
        'admin' => '/code-arena/admin.php',
        'org_admin' => '/code-arena/organization/dashboard.php',
        default => '/code-arena/user/dashboard.php',
    };
}
function authRedirectPath(array $user): string {
    $role = $user['role'] ?? 'user';
    if ($role !== 'admin' && empty($user['profile_completed'])) {
        return '/code-arena/profile_complete.php';
    }
    return authDashboardPath($role);
}
