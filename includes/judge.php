<?php
ini_set('display_errors', '0');
ini_set('log_errors', '1');
// ============================================================
//  Code Arena - Judge execution helpers
// ============================================================

define('PISTON_ENDPOINT', 'https://emkc.org/api/v2/piston/execute');
define('ONLINE_COMPILER_ENDPOINT', 'https://api.onlinecompiler.io/api/run-code-sync/');
define('ONLINE_COMPILER_API_KEY', getenv('ONLINE_COMPILER_API_KEY') ?: '');
define('JUDGE_TIMEOUT_S', 25);
define('CODE_RUN_TIMEOUT_MS', 5000);
define('JUDGE_OUTPUT_LIMIT', 12000);
define('LOCAL_JUDGE_ENABLED', (getenv('CODEARENA_LOCAL_JUDGE') ?: '1') !== '0');

const PISTON_LANGUAGES = [
    'javascript' => ['language' => 'javascript', 'version' => '18.15.0'],
    'typescript' => ['language' => 'typescript', 'version' => '5.0.3'],
    'python'     => ['language' => 'python', 'version' => '3.10.0'],
    'cpp'        => ['language' => 'c++', 'version' => '10.2.0'],
    'c'          => ['language' => 'c', 'version' => '10.2.0'],
    'java'       => ['language' => 'java', 'version' => '15.0.2'],
    'go'         => ['language' => 'go', 'version' => '1.16.2'],
    'rust'       => ['language' => 'rust', 'version' => '1.50.0'],
    'php'        => ['language' => 'php', 'version' => '8.2.3'],
];

const ONLINE_COMPILER_LANGUAGES = [
    'javascript' => 'nodejs-22',
    'typescript' => 'typescript-deno',
    'python'     => 'python-3.14',
    'cpp'        => 'g++-15',
    'c'          => 'gcc-15',
    'java'       => 'openjdk-25',
    'go'         => 'go-1.26',
    'rust'       => 'rust-1.93',
    'php'        => 'php-8.5',
];

function judgeLog(string $event, array $context = []): void {
    foreach ($context as $key => $value) {
        if (is_string($value)) {
            $context[$key] = mb_substr($value, 0, 600);
        }
    }
    error_log('[CodeArenaJudge] ' . $event . ' ' . json_encode($context, JSON_UNESCAPED_SLASHES));
}

function pistonRun(string $code, string $language, string $stdin = ''): array {
    if (!isset(PISTON_LANGUAGES[$language])) {
        return ['success' => false, 'transport_error' => "Unsupported language: $language"];
    }

    if (LOCAL_JUDGE_ENABLED && localJudgeSupports($language)) {
        $local = executeLocally($code, $language, $stdin);
        if ($local['success']) {
            return $local;
        }
        judgeLog('local_judge_failed_trying_remote', [
            'language' => $language,
            'error' => $local['transport_error'] ?? '',
        ]);
    }

    $piston = executeWithPiston($code, $language, $stdin);
    if ($piston['success']) {
        return $piston;
    }

    if (LOCAL_JUDGE_ENABLED) {
        judgeLog('piston_failed_trying_local_judge', [
            'language' => $language,
            'error' => $piston['transport_error'] ?? '',
        ]);
        $local = executeLocally($code, $language, $stdin);
        if ($local['success'] || ONLINE_COMPILER_API_KEY === '') {
            if (!$local['success']) {
                $local['transport_error'] = ($piston['transport_error'] ?? 'Piston failed')
                    . '; local: ' . ($local['transport_error'] ?? 'Local judge failed');
            }
            return $local;
        }
    }

    if (ONLINE_COMPILER_API_KEY === '') {
        return $piston;
    }

    judgeLog('remote_and_local_failed_trying_online_compiler', [
        'language' => $language,
        'error' => $piston['transport_error'] ?? '',
    ]);

    $fallback = executeWithOnlineCompiler($code, $language, $stdin);
    if (!$fallback['success']) {
        $fallback['transport_error'] = ($piston['transport_error'] ?? 'Piston failed')
            . '; fallback: ' . ($fallback['transport_error'] ?? 'OnlineCompiler failed');
    }
    return $fallback;
}

function executeLocally(string $code, string $language, string $stdin): array {
    $dir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'codearena_judge_' . bin2hex(random_bytes(6));
    if (!mkdir($dir, 0700, true) && !is_dir($dir)) {
        return ['success' => false, 'transport_error' => 'Could not create local judge workspace'];
    }

    try {
        $source = $dir . DIRECTORY_SEPARATOR . sourceFileName($language);
        file_put_contents($source, $code);

        $command = null;
        if ($language === 'python') {
            $command = 'python ' . escapeshellarg($source);
        } elseif ($language === 'javascript') {
            $command = 'node ' . escapeshellarg($source);
        } elseif ($language === 'php') {
            $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($source);
        } elseif ($language === 'cpp') {
            $exe = $dir . DIRECTORY_SEPARATOR . 'main.exe';
            $compile = runLocalProcess('g++ -std=c++17 -O2 ' . escapeshellarg($source) . ' -o ' . escapeshellarg($exe), '', $dir, CODE_RUN_TIMEOUT_MS);
            if (!$compile['success'] || $compile['exit_code'] !== 0) {
                return ['success' => true, 'output' => $compile['output'], 'stderr' => $compile['stderr'] ?: 'Compilation Error', 'exit_code' => 1, 'signal' => null, 'compile_error' => true];
            }
            $command = escapeshellarg($exe);
        } elseif ($language === 'c') {
            $exe = $dir . DIRECTORY_SEPARATOR . 'main.exe';
            $compile = runLocalProcess('gcc -O2 ' . escapeshellarg($source) . ' -o ' . escapeshellarg($exe), '', $dir, CODE_RUN_TIMEOUT_MS);
            if (!$compile['success'] || $compile['exit_code'] !== 0) {
                return ['success' => true, 'output' => $compile['output'], 'stderr' => $compile['stderr'] ?: 'Compilation Error', 'exit_code' => 1, 'signal' => null, 'compile_error' => true];
            }
            $command = escapeshellarg($exe);
        } else {
            return ['success' => false, 'transport_error' => "Local judge does not support $language"];
        }

        $run = runLocalProcess($command, $stdin, $dir, CODE_RUN_TIMEOUT_MS);
        judgeLog('local_judge_result', [
            'language' => $language,
            'success' => $run['success'],
            'exit_code' => $run['exit_code'],
            'timed_out' => $run['timed_out'],
        ]);

        if (!$run['success']) {
            return ['success' => false, 'transport_error' => $run['error'] ?? 'Local judge failed'];
        }

        return [
            'success' => true,
            'output' => mb_substr($run['output'], 0, JUDGE_OUTPUT_LIMIT),
            'stderr' => mb_substr($run['stderr'], 0, JUDGE_OUTPUT_LIMIT),
            'exit_code' => $run['timed_out'] ? 124 : $run['exit_code'],
            'signal' => $run['timed_out'] ? 'TIMEOUT' : null,
            'compile_error' => false,
        ];
    } finally {
        removeJudgeWorkspace($dir);
    }
}

function localJudgeSupports(string $language): bool {
    return in_array($language, ['python', 'javascript', 'php', 'cpp', 'c'], true);
}

function runLocalProcess(string $command, string $stdin, string $cwd, int $timeoutMs): array {
    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $process = proc_open($command, $descriptorSpec, $pipes, $cwd);
    if (!is_resource($process)) {
        return ['success' => false, 'error' => 'Could not start local process', 'output' => '', 'stderr' => '', 'exit_code' => 1, 'timed_out' => false];
    }

    fwrite($pipes[0], $stdin);
    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    $output = '';
    $stderr = '';
    $start = microtime(true);
    $timedOut = false;

    while (true) {
        $output .= stream_get_contents($pipes[1]);
        $stderr .= stream_get_contents($pipes[2]);
        $status = proc_get_status($process);
        if (!$status['running']) {
            break;
        }
        if (((microtime(true) - $start) * 1000) >= $timeoutMs) {
            $timedOut = true;
            proc_terminate($process);
            break;
        }
        usleep(20000);
    }

    $output .= stream_get_contents($pipes[1]);
    $stderr .= stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    return [
        'success' => true,
        'output' => mb_substr($output, 0, JUDGE_OUTPUT_LIMIT),
        'stderr' => mb_substr($stderr, 0, JUDGE_OUTPUT_LIMIT),
        'exit_code' => $timedOut ? 124 : $exitCode,
        'timed_out' => $timedOut,
    ];
}

function removeJudgeWorkspace(string $dir): void {
    if (!is_dir($dir) || !str_contains($dir, 'codearena_judge_')) {
        return;
    }
    foreach (scandir($dir) ?: [] as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_file($path)) {
            @unlink($path);
        }
    }
    @rmdir($dir);
}

function executeWithPiston(string $code, string $language, string $stdin): array {
    $lang = PISTON_LANGUAGES[$language];
    $payload = json_encode([
        'language' => $lang['language'],
        'version' => $lang['version'],
        'files' => [['name' => sourceFileName($language), 'content' => $code]],
        'stdin' => $stdin,
    ], JSON_UNESCAPED_SLASHES);

    if ($payload === false) {
        return ['success' => false, 'transport_error' => 'Could not encode judge payload'];
    }

    judgeLog('execute_piston', [
        'language' => $language,
        'runtime' => $lang['language'] . '@' . $lang['version'],
        'code_bytes' => strlen($code),
        'stdin_bytes' => strlen($stdin),
    ]);

    $ch = curl_init(PISTON_ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => JUDGE_TIMEOUT_S,
        CURLOPT_CONNECTTIMEOUT => 7,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => 'CodeArenaJudge/1.1',
    ]);
    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr || !$raw) {
        judgeLog('piston_transport_error', ['http_code' => $httpCode, 'curl_error' => $curlErr]);
        return ['success' => false, 'transport_error' => 'Judge service unreachable: ' . ($curlErr ?: 'empty response')];
    }
    if ($httpCode < 200 || $httpCode >= 300) {
        judgeLog('piston_http_error', ['http_code' => $httpCode, 'response' => $raw]);
        return ['success' => false, 'transport_error' => "Judge returned HTTP $httpCode"];
    }

    $result = json_decode($raw, true);
    if (!is_array($result) || !isset($result['run'])) {
        judgeLog('piston_malformed_response', ['response' => $raw]);
        return ['success' => false, 'transport_error' => 'Malformed judge response'];
    }

    $run = $result['run'];
    return [
        'success' => true,
        'output' => mb_substr((string)($run['stdout'] ?? ''), 0, JUDGE_OUTPUT_LIMIT),
        'stderr' => mb_substr((string)($run['stderr'] ?? ''), 0, JUDGE_OUTPUT_LIMIT),
        'exit_code' => (int)($run['code'] ?? 0),
        'signal' => $run['signal'] ?? null,
    ];
}

function executeWithOnlineCompiler(string $code, string $language, string $stdin): array {
    if (!isset(ONLINE_COMPILER_LANGUAGES[$language])) {
        return ['success' => false, 'transport_error' => "Unsupported fallback language: $language"];
    }

    $payload = json_encode([
        'compiler' => ONLINE_COMPILER_LANGUAGES[$language],
        'code' => $code,
        'input' => $stdin,
    ], JSON_UNESCAPED_SLASHES);
    if ($payload === false) {
        return ['success' => false, 'transport_error' => 'Could not encode fallback payload'];
    }

    $ch = curl_init(ONLINE_COMPILER_ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Authorization: ' . ONLINE_COMPILER_API_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => JUDGE_TIMEOUT_S,
        CURLOPT_CONNECTTIMEOUT => 7,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => 'CodeArenaJudge/1.1',
    ]);
    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr || !$raw) {
        judgeLog('online_compiler_transport_error', ['http_code' => $httpCode, 'curl_error' => $curlErr]);
        return ['success' => false, 'transport_error' => 'Judge fallback unreachable: ' . ($curlErr ?: 'empty response')];
    }
    if ($httpCode < 200 || $httpCode >= 300) {
        judgeLog('online_compiler_http_error', ['http_code' => $httpCode, 'response' => $raw]);
        return ['success' => false, 'transport_error' => "Judge fallback returned HTTP $httpCode"];
    }

    $result = json_decode($raw, true);
    if (!is_array($result)) {
        judgeLog('online_compiler_malformed_response', ['response' => $raw]);
        return ['success' => false, 'transport_error' => 'Malformed fallback response'];
    }

    return [
        'success' => true,
        'output' => mb_substr((string)($result['output'] ?? ''), 0, JUDGE_OUTPUT_LIMIT),
        'stderr' => mb_substr((string)($result['error'] ?? ''), 0, JUDGE_OUTPUT_LIMIT),
        'exit_code' => (int)($result['exit_code'] ?? 0),
        'signal' => $result['signal'] ?? null,
    ];
}

function judgeSubmission(string $code, string $language, array $testCases): array {
    if (empty($testCases)) {
        judgeLog('empty_test_cases_rejected', ['language' => $language, 'code_bytes' => strlen($code)]);
        return [
            'verdict' => 'Runtime Error',
            'passed' => 0,
            'total' => 0,
            'runtime_ms' => 0,
            'results' => [],
            'error' => 'No test cases are configured for this problem.',
        ];
    }

    $results = [];
    $passed = 0;
    $total = count($testCases);
    $verdict = 'Accepted';
    $errorMsg = '';
    $maxRunMs = 0;

    judgeLog('judge_submission_start', [
        'language' => $language,
        'test_case_count' => $total,
        'code_bytes' => strlen($code),
    ]);

    foreach ($testCases as $i => $tc) {
        if (!is_array($tc)) {
            $verdict = 'Runtime Error';
            $results[] = ['test' => $i + 1, 'passed' => false, 'input' => '', 'expected' => '', 'got' => '', 'error' => 'Invalid test case'];
            continue;
        }

        $input = mb_substr((string)($tc['input'] ?? ''), 0, 8192);
        $expected = trim(mb_substr((string)($tc['expected_output'] ?? $tc['output'] ?? ''), 0, JUDGE_OUTPUT_LIMIT));
        $t0 = microtime(true);
        $run = pistonRun($code, $language, $input);
        $elapsed = (int)round((microtime(true) - $t0) * 1000);
        $maxRunMs = max($maxRunMs, $elapsed);

        judgeLog('test_case_result', [
            'test' => $i + 1,
            'success' => $run['success'] ?? false,
            'exit_code' => $run['exit_code'] ?? null,
            'signal' => $run['signal'] ?? null,
            'elapsed_ms' => $elapsed,
            'error' => $run['transport_error'] ?? ($run['stderr'] ?? ''),
        ]);

        if (!$run['success']) {
            if ($verdict === 'Accepted') {
                $verdict = 'Runtime Error';
                $errorMsg = $run['transport_error'] ?? 'Judge execution failed';
            }
            $results[] = ['test' => $i + 1, 'passed' => false, 'input' => $input, 'expected' => $expected, 'got' => $run['output'] ?? '', 'error' => $run['transport_error'] ?? 'Judge execution failed'];
            continue;
        }
        if ($elapsed >= CODE_RUN_TIMEOUT_MS || (($run['signal'] ?? null) === 'TIMEOUT')) {
            if ($verdict === 'Accepted') {
                $verdict = 'Time Limit Exceeded';
                $errorMsg = 'Test case ' . ($i + 1) . ' exceeded the time limit.';
            }
            $results[] = ['test' => $i + 1, 'passed' => false, 'input' => $input, 'expected' => $expected, 'got' => 'TLE', 'error' => 'Time Limit Exceeded'];
            continue;
        }
        if (!empty($run['signal'])) {
            if ($verdict === 'Accepted') {
                $verdict = 'Runtime Error';
                $errorMsg = 'Process killed by signal: ' . $run['signal'];
            }
            $results[] = ['test' => $i + 1, 'passed' => false, 'input' => $input, 'expected' => $expected, 'got' => $run['output'] ?? '', 'error' => $errorMsg];
            continue;
        }
        if (!empty($run['compile_error'])) {
            if ($verdict === 'Accepted') {
                $verdict = 'Compilation Error';
                $errorMsg = $run['stderr'] ?: 'Compilation Error';
            }
            $results[] = ['test' => $i + 1, 'passed' => false, 'input' => $input, 'expected' => $expected, 'got' => $run['output'] ?? '', 'error' => $run['stderr'] ?: 'Compilation Error'];
            continue;
        }
        if ((int)$run['exit_code'] !== 0) {
            if ($verdict === 'Accepted') {
                $verdict = 'Runtime Error';
                $errorMsg = $run['stderr'] ?: 'Non-zero exit code: ' . $run['exit_code'];
            }
            $results[] = ['test' => $i + 1, 'passed' => false, 'input' => $input, 'expected' => $expected, 'got' => $run['output'], 'error' => $run['stderr'] ?: 'Runtime Error'];
            continue;
        }
        $actual = normalizeOutput($run['output']);
        $expectedN = normalizeOutput($expected);
        $testPassed = ($actual === $expectedN);
        if ($testPassed) {
            $passed++;
        } elseif ($verdict === 'Accepted') {
            $verdict = 'Wrong Answer';
        }
        $results[] = ['test' => $i + 1, 'passed' => $testPassed, 'input' => $input, 'expected' => $expected, 'got' => $run['output'], 'error' => $run['stderr'] ?? ''];
    }

    return ['verdict' => $verdict, 'passed' => $passed, 'total' => $total, 'runtime_ms' => $maxRunMs, 'results' => $results, 'error' => $errorMsg];
}

function runCode(string $code, string $language, string $stdin = ''): array {
    judgeLog('run_code', ['language' => $language, 'code_bytes' => strlen($code), 'stdin_bytes' => strlen($stdin)]);
    $run = pistonRun($code, $language, $stdin);
    if (!$run['success']) {
        return ['success' => false, 'error' => $run['transport_error'] ?? 'Judge execution failed'];
    }
    $verdict = 'OK';
    if (!empty($run['compile_error'])) {
        $verdict = 'Compilation Error';
    } elseif (!empty($run['signal']) || (int)$run['exit_code'] !== 0) {
        $verdict = 'Runtime Error';
    }
    return ['success' => true, 'verdict' => $verdict, 'output' => $run['output'], 'stderr' => $run['stderr'], 'exit_code' => $run['exit_code']];
}

function normalizeOutput(string $s): string {
    $s = str_replace("\r\n", "\n", $s);
    $lines = explode("\n", trim($s));
    $lines = array_map('rtrim', $lines);
    return implode("\n", $lines);
}

function sourceFileName(string $language): string {
    return match ($language) {
        'cpp' => 'main.cpp',
        'c' => 'main.c',
        'java' => 'Main.java',
        'go' => 'main.go',
        'rust' => 'main.rs',
        'php' => 'main.php',
        'typescript' => 'main.ts',
        default => 'main.js',
    };
}

function supportedLanguages(): array {
    return array_keys(PISTON_LANGUAGES);
}
