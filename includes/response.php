<?php
require_once __DIR__ . '/security.php';
// ============================================================
//  CODE ARENA — JSON Response Helpers
//  File: includes/response.php
// ============================================================

header('Content-Type: application/json');

function respond(bool $success, string $message = '', mixed $data = null, int $code = 200): void {
    http_response_code($code);
    $out = ['success' => $success, 'message' => $message];
    if ($data !== null) $out['data'] = $data;
    echo json_encode($out);
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
