<?php
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
    err($result['error'], 503);
}

ok($result);
