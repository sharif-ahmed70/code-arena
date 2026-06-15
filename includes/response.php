<?php
require_once __DIR__ . '/security.php';
// ============================================================
//  CODE ARENA — JSON Response Helpers
//  File: includes/response.php
// ============================================================

ini_set('display_errors', '0');
ini_set('log_errors', '1');

if (!headers_sent()) {
    header('Content-Type: application/json');
}

function respond(bool $success, string $message = '', mixed $data = null, int $code = 200): void {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    http_response_code($code);
    $out = [
        'success' => $success,
        'status' => $success ? 'success' : 'error',
        'message' => $message,
    ];
    if (is_array($data)) {
        $out['verdict'] = $data['verdict'] ?? ($success ? 'OK' : 'ERROR');
        $out['passed'] = (int)($data['passed'] ?? 0);
        $out['total'] = (int)($data['total'] ?? 0);
    } elseif (!$success) {
        $out['verdict'] = 'ERROR';
        $out['passed'] = 0;
        $out['total'] = 0;
    }
    if ($data !== null) $out['data'] = $data;
    echo json_encode($out, JSON_UNESCAPED_SLASHES);
    exit;
}

function ok(mixed $data = null, string $message = 'OK'): void {
    respond(true, $message, $data, 200);
}

function success(mixed $data = null, string $message = 'OK'): void {
    ok($data, $message);
}

function created(mixed $data = null, string $message = 'Created'): void {
    respond(true, $message, $data, 201);
}

function err(string $message, int $code = 400): void {
    respond(false, $message, null, $code);
}

function error(string $message, int $code = 400): void {
    err($message, $code);
}

function methodCheck(string ...$allowed): void {
    if (!in_array($_SERVER['REQUEST_METHOD'], $allowed)) {
        err('Method not allowed', 405);
    }
}

function jsonBody(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') return [];
    $body = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($body)) {
        err('Invalid JSON request body', 400);
    }
    return $body;
}
