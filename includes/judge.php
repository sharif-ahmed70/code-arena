<?php
// ============================================================
//  CODE ARENA — Automated Judge (OnlineCompiler.io API)
//  File: includes/judge.php
//
//  Endpoint: https://api.onlinecompiler.io/api/run-code-sync/
//  Method:   POST
//  Headers:  Authorization: API_KEY, Content-Type: application/json
//  Body:     { compiler, code, input }
//  Response: { output, error, status, exit_code, signal, time, memory }
// ============================================================

define('OC_ENDPOINT',        'https://api.onlinecompiler.io/api/run-code-sync/');
define('OC_API_KEY',         getenv('ONLINE_COMPILER_API_KEY') ?: '');
define('JUDGE_TIMEOUT_S',     30);
define('CODE_RUN_TIMEOUT_MS', 5000);
define('JUDGE_OUTPUT_LIMIT',  12000);

const OC_LANGUAGES = [
    'javascript' => 'typescript-deno',
    'typescript' => 'typescript-deno',
    'python'     => 'python-3.14',
    'cpp'        => 'g++-15',
    'c'          => 'gcc-15',
    'java'       => 'openjdk-25',
    'go'         => 'go-1.26',
    'rust'       => 'rust-1.93',
    'php'        => 'php-8.5',
];

function pistonRun(string $code, string $language, string $stdin = ''): array {
    if (!isset(OC_LANGUAGES[$language])) {
        return ['success' => false, 'transport_error' => "Unsupported language: $language"];
    }
    $compiler = OC_LANGUAGES[$language];
    $payload  = json_encode([
        'compiler' => $compiler,
        'code'     => $code,
        'input'    => $stdin,
    ]);
    if ($payload === false) {
        return ['success' => false, 'transport_error' => 'Could not encode judge payload'];
    }
    $ch = curl_init(OC_ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Authorization: ' . OC_API_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => JUDGE_TIMEOUT_S,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT      => 'CodeArenaJudge/1.0',
    ]);
    $raw      = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr || !$raw) {
        return ['success' => false, 'transport_error' => 'Judge service unreachable: ' . $curlErr];
    }
    if ($httpCode !== 200) {
        error_log("Judge returned HTTP $httpCode: " . substr($raw, 0, 500));
        return ['success' => false, 'transport_error' => 'Judge service returned an error'];
    }
    $result = json_decode($raw, true);
    if (!is_array($result) || !isset($result['output'])) {
        error_log('Malformed judge response: ' . substr($raw, 0, 500));
        return ['success' => false, 'transport_error' => 'Malformed judge response'];
    }

    $exitCode = $result['exit_code'] ?? 0;
    $signal   = $result['signal']   ?? null;
    $stdout   = mb_substr((string)($result['output'] ?? ''), 0, JUDGE_OUTPUT_LIMIT);
    $stderr   = mb_substr((string)($result['error'] ?? ''), 0, JUDGE_OUTPUT_LIMIT);

    if ($signal) {
        return ['success' => false, 'transport_error' => "Process killed by signal: $signal", 'output' => $stdout, 'stderr' => $stderr, 'exit_code' => $exitCode];
    }
    return ['success' => true, 'output' => $stdout, 'stderr' => $stderr, 'exit_code' => $exitCode];
}

function judgeSubmission(string $code, string $language, array $testCases): array {
    if (empty($testCases)) {
        return ['verdict' => 'Accepted', 'passed' => 0, 'total' => 0, 'runtime_ms' => 0, 'results' => [], 'error' => ''];
    }
    $results  = [];
    $passed   = 0;
    $total    = count($testCases);
    $verdict  = 'Accepted';
    $errorMsg = '';
    $maxRunMs = 0;

    foreach ($testCases as $i => $tc) {
        if (!is_array($tc)) {
            $verdict = 'Runtime Error';
            $results[] = ['test' => $i+1, 'passed' => false, 'input' => '', 'expected' => '', 'got' => '', 'error' => 'Invalid test case'];
            continue;
        }
        $input    = mb_substr((string)($tc['input'] ?? ''), 0, 8192);
        $expected = trim(mb_substr((string)($tc['expected_output'] ?? ''), 0, JUDGE_OUTPUT_LIMIT));
        $t0       = microtime(true);
        $run      = pistonRun($code, $language, $input);
        $elapsed  = (int) round((microtime(true) - $t0) * 1000);
        $maxRunMs = max($maxRunMs, $elapsed);

        if (!$run['success']) {
            if ($verdict === 'Accepted') { $verdict = 'Runtime Error'; $errorMsg = $run['transport_error']; }
            $results[] = ['test' => $i+1, 'passed' => false, 'input' => $input, 'expected' => $expected, 'got' => $run['output'] ?? '', 'error' => $run['transport_error']];
            continue;
        }
        if ($run['exit_code'] !== 0) {
            if ($verdict === 'Accepted') { $verdict = 'Runtime Error'; $errorMsg = $run['stderr'] ?: 'Non-zero exit code: ' . $run['exit_code']; }
            $results[] = ['test' => $i+1, 'passed' => false, 'input' => $input, 'expected' => $expected, 'got' => $run['output'], 'error' => $run['stderr'] ?: 'Runtime Error'];
            continue;
        }
        if ($elapsed >= CODE_RUN_TIMEOUT_MS) {
            if ($verdict === 'Accepted') { $verdict = 'Time Limit Exceeded'; $errorMsg = 'Test case ' . ($i+1) . ' exceeded the time limit.'; }
            $results[] = ['test' => $i+1, 'passed' => false, 'input' => $input, 'expected' => $expected, 'got' => 'TLE', 'error' => 'Time Limit Exceeded'];
            continue;
        }
        $actual     = normalizeOutput($run['output']);
        $expectedN  = normalizeOutput($expected);
        $testPassed = ($actual === $expectedN);
        if ($testPassed) { $passed++; } elseif ($verdict === 'Accepted') { $verdict = 'Wrong Answer'; }
        $results[] = ['test' => $i+1, 'passed' => $testPassed, 'input' => $input, 'expected' => $expected, 'got' => $run['output'], 'error' => $run['stderr'] ?? ''];
    }
    return ['verdict' => $verdict, 'passed' => $passed, 'total' => $total, 'runtime_ms' => $maxRunMs, 'results' => $results, 'error' => $errorMsg];
}

function runCode(string $code, string $language, string $stdin = ''): array {
    $run = pistonRun($code, $language, $stdin);
    if (!$run['success']) {
        return ['success' => false, 'error' => $run['transport_error']];
    }
    return ['success' => true, 'verdict' => 'OK', 'output' => $run['output'], 'stderr' => $run['stderr'], 'exit_code' => $run['exit_code']];
}

function normalizeOutput(string $s): string {
    $s     = str_replace("\r\n", "\n", $s);
    $lines = explode("\n", trim($s));
    $lines = array_map('rtrim', $lines);
    return implode("\n", $lines);
}

function supportedLanguages(): array {
    return array_keys(OC_LANGUAGES);
}
