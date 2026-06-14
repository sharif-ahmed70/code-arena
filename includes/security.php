<?php
// ============================================================
//  CODE ARENA - Security Utilities
// ============================================================

function applySecurityHeaders(): void {
    if (headers_sent()) return;

    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self' https://fonts.googleapis.com https://fonts.gstatic.com; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; connect-src 'self' https://cdn.jsdelivr.net; img-src 'self' data:; worker-src 'self' blob:;");

    if (str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($origin && parse_url($origin, PHP_URL_HOST) === $host) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Credentials: true');
            header('Vary: Origin');
        }
    }
}

function clientIp(): string {
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

function cleanString(mixed $value, int $maxLen = 255): string {
    $value = trim((string)$value);
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value) ?? '';
    return mb_substr($value, 0, $maxLen);
}

function validUsername(string $username): bool {
    return (bool) preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username);
}

function validPassword(string $password): bool {
    return strlen($password) >= 8 && strlen($password) <= 255;
}

function validLanguage(string $language, array $allowed): bool {
    return in_array($language, $allowed, true);
}

function appLog(PDO $pdo, string $event, array $context = []): void {
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO audit_logs (event, user_id, ip_address, context)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $event,
            currentUserId(),
            clientIp(),
            json_encode($context, JSON_UNESCAPED_SLASHES),
        ]);
    } catch (Throwable $e) {
        error_log('audit log failed: ' . $e->getMessage());
    }
}

applySecurityHeaders();
