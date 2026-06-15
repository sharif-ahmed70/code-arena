<?php
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ob_start();
set_exception_handler(function (Throwable $e): void {
    error_log('[CodeArenaRunException] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => 'Judge execution failed',
        'verdict' => 'SYSTEM_ERROR',
        'passed' => 0,
        'total' => 0,
    ], JSON_UNESCAPED_SLASHES);
    exit;
});
register_shutdown_function(function (): void {
    $error = error_get_last();
    if (!$error || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }
    error_log('[CodeArenaRunFatal] ' . json_encode($error, JSON_UNESCAPED_SLASHES));
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => 'Judge execution failed',
        'verdict' => 'SYSTEM_ERROR',
        'passed' => 0,
        'total' => 0,
    ], JSON_UNESCAPED_SLASHES);
});
// ============================================================
//  CODE ARENA — Run Code (no judging, no submission record)
//  POST /api/submissions/run.php
//  Body: { code, language, stdin? }
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';
require_once '../../includes/judge.php';
require_once '../../includes/rate_limit.php';

methodCheck('POST');

$body     = jsonBody();
$code     = $body['code'] ?? '';
$language = cleanString($body['language'] ?? 'javascript', 30);
$stdin    = (string)($body['stdin'] ?? '');

if (!$code || !$language) err('code and language are required');
if (!is_string($code)) err('code must be a string');
if (!validLanguage($language, supportedLanguages())) err('Unsupported language');
if (strlen($code) > 65536) err('Code too long (max 64 KB)');
if (strlen($stdin) > 8192) err('stdin too long (max 8 KB)');

enforceRateLimit($pdo, rateLimitKey('run', (string)(currentUserId() ?? 'guest')), 30, 60);

$result = runCode($code, $language, $stdin);

if (!$result['success'] && isset($result['error'])) {
    respond(false, 'Judge execution failed', [
        'verdict' => 'SYSTEM_ERROR',
        'passed' => 0,
        'total' => 0,
        'error' => $result['error'],
    ], 503);
}

ok($result);
