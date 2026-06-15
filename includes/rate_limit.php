<?php
// ============================================================
//  CODE ARENA - DB-backed Rate Limiting
// ============================================================

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/response.php';

function rateLimitKey(string $scope, string $identifier = ''): string {
    $identifier = $identifier !== '' ? strtolower($identifier) : 'anon';
    return hash('sha256', $scope . '|' . clientIp() . '|' . $identifier);
}

function enforceRateLimit(PDO $pdo, string $key, int $maxAttempts, int $windowSeconds): void {
    $now = time();
    $windowStart = date('Y-m-d H:i:s', $now - $windowSeconds);

    try {
        $pdo->prepare('DELETE FROM rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)')->execute();

        $countStmt = $pdo->prepare(
            'SELECT COUNT(*) FROM rate_limits
             WHERE rate_key = ? AND created_at >= ?'
        );
        $countStmt->execute([$key, $windowStart]);
        $attempts = (int)$countStmt->fetchColumn();

        if ($attempts >= $maxAttempts) {
            err('Too many attempts. Please wait and try again.', 429);
        }

        $ins = $pdo->prepare('INSERT INTO rate_limits (rate_key, ip_address) VALUES (?, ?)');
        $ins->execute([$key, clientIp()]);
    } catch (PDOException $e) {
        error_log('rate limit failed: ' . $e->getMessage());
        err('Temporary service protection error. Try again shortly.', 503);
    }
}

function clearRateLimit(PDO $pdo, string $key): void {
    try {
        $stmt = $pdo->prepare('DELETE FROM rate_limits WHERE rate_key = ?');
        $stmt->execute([$key]);
    } catch (PDOException $e) {
        error_log('rate limit reset failed: ' . $e->getMessage());
    }
}

function clearRateLimits(PDO $pdo, array $keys): void {
    foreach (array_unique(array_filter($keys)) as $key) {
        clearRateLimit($pdo, $key);
    }
}
