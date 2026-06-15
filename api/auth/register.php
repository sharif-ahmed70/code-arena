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
$hasExplicitAccountType = array_key_exists('account_type', $body);
$accountType = cleanString($body['account_type'] ?? 'individual', 30);
$fullName = cleanString($body['full_name'] ?? ($body['name'] ?? ($body['username'] ?? '')), 120);
$username = cleanString($body['username'] ?? '', 50);
$email = strtolower(cleanString($body['email'] ?? '', 100));
$password = (string)($body['password'] ?? '');
$country = cleanString($body['country'] ?? '', 80);
$university = cleanString($body['university'] ?? '', 160);
$orgId = !empty($body['org_id']) ? (int)$body['org_id'] : null;
$organizationName = cleanString($body['organization_name'] ?? '', 160);
$organizationType = cleanString($body['organization_type'] ?? '', 30);
$description = cleanString($body['description'] ?? '', 1000);
$logo = cleanString($body['logo'] ?? '', 255);

if (!in_array($accountType, ['individual', 'organization'], true)) err('Invalid account type');
if (!$fullName || !$email || !$password) err('All required fields must be completed');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) err('Invalid email address');
if (!validPassword($password)) err('Password must be 8-255 characters');
if ($hasExplicitAccountType && $accountType === 'individual' && !$country) err('Country is required');
if ($accountType === 'organization') {
    if (!$organizationName) err('Organization name is required');
    if (!in_array($organizationType, ['university', 'company', 'community'], true)) err('Invalid organization type');
}

if (!$username) {
    $base = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', explode('@', $email)[0]) ?? 'user');
    $base = trim($base, '_') ?: 'user';
    $username = mb_substr($base, 0, 42);
}
if (!validUsername($username)) err('Username must be 3-50 characters and contain only letters, numbers, or underscores');

enforceRateLimit($pdo, rateLimitKey('register', $email), 5, 60 * 60);

try {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ((int)$stmt->fetchColumn() > 0) err('Email already registered', 409);

    $baseUsername = $username;
    $suffix = 0;
    do {
        $checkUsername = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
        $checkUsername->execute([$username]);
        if ((int)$checkUsername->fetchColumn() === 0) break;
        $suffix++;
        $username = mb_substr($baseUsername, 0, 42) . '_' . $suffix;
    } while ($suffix < 1000);

    if ($orgId) {
        $orgCheck = $pdo->prepare('SELECT id FROM organizations WHERE id = ?');
        $orgCheck->execute([$orgId]);
        if (!$orgCheck->fetch()) err('Selected organization was not found', 404);
    }

    $pdo->beginTransaction();

    $role = $accountType === 'organization' ? 'org_admin' : 'user';
    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $profileCompleted = $accountType === 'organization' ? 1 : 0;
    $stmt = $pdo->prepare(
        'INSERT INTO users
            (name, username, email, password, role, country, university, org_id, profile_completed)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $fullName,
        $username,
        $email,
        $hashed,
        $role,
        $country ?: null,
        $university ?: null,
        $orgId,
        $profileCompleted,
    ]);

    $newUserId = (int) $pdo->lastInsertId();
    if ($accountType === 'organization') {
        $orgStmt = $pdo->prepare(
            'INSERT INTO organizations (name, type, description, logo, owner_id)
             VALUES (?, ?, ?, ?, ?)'
        );
        $orgStmt->execute([
            $organizationName,
            $organizationType,
            $description ?: null,
            $logo ?: null,
            $newUserId,
        ]);
        $orgId = (int)$pdo->lastInsertId();

        $pdo->prepare('UPDATE users SET org_id = ? WHERE id = ?')->execute([$orgId, $newUserId]);
        $pdo->prepare(
            'INSERT INTO organization_members (user_id, org_id, role)
             VALUES (?, ?, ?)'
        )->execute([$newUserId, $orgId, 'org_owner']);
    } elseif ($orgId) {
        $pdo->prepare(
            'INSERT INTO organization_members (user_id, org_id, role)
             VALUES (?, ?, ?)'
        )->execute([$newUserId, $orgId, 'org_member']);
    }

    $userForSession = [
        'id' => $newUserId,
        'username' => $username,
        'email' => $email,
        'role' => $role,
        'profile_completed' => $profileCompleted,
    ];
    loginUser([
        'id' => $newUserId,
        'username' => $username,
        'email' => $email,
        'role' => $role,
        'profile_completed' => $profileCompleted,
    ]);
    $pdo->commit();
    appLog($pdo, 'register_success', ['user_id' => $newUserId]);

    created([
        'user' => [
            'id'       => $newUserId,
            'username' => $username,
            'name'     => $fullName,
            'role'     => $role,
            'org_id'   => $orgId,
        ],
        'redirect_url' => authRedirectPath($userForSession),
    ], 'Registration successful');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('registration failed: ' . $e->getMessage());
    err('Registration failed, try again', 500);
}
