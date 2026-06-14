<?php
// ============================================================
//  CODE ARENA — Automated Judge (Piston API)
//  File: includes/judge.php
//
//  Endpoint: https://emkc.org/api/v2/piston/execute
//  Method:   POST
//  Headers:  Content-Type: application/json
//  Body:     { language, version, files: [{content}], stdin }
//  Response: { run: { stdout, stderr, code, signal } }
// ============================================================

define('PISTON_ENDPOINT',      'https://emkc.org/api/v2/piston/execute');
define('JUDGE_TIMEOUT_S',       15);
define('CODE_RUN_TIMEOUT_MS',   5000);

// Internal language slug → Piston language name + version
const PISTON_LANGUAGES = [
    'javascript' => ['language' => 'javascript', 'version' => '18.15.0'],
    'python'     => ['language' => 'python',      'version' => '3.10.0'],
    'cpp'        => ['language' => 'c++',         'version' => '10.2.0'],
    'c'          => ['language' => 'c',           'version' => '10.2.0'],
    'java'       => ['language' => 'java',        'version' => '15.0.2'],
    'go'         => ['language' => 'go',          'version' => '1.16.2'],
    'rust'       => ['language' => 'rust',        'version' => '1.50.0'],
    'php'        => ['language' => 'php',         'version' => '8.2.3'],
];

// ── Internal: single execution via Piston ───────────────────
/**
 * Execute $code in $language with $stdin via Piston.
 *
 * Returns:
 *   success         bool
 *   output          string   combined stdout/stderr
 *   stderr          string   stderr only
 *   exit_code       int
 *   transport_error string   set only on curl / HTTP failure
 */
function pistonRun(string $code, string $language, string $stdin = ''): array {
    if (!isset(PISTON_LANGUAGES[$language])) {
        return ['success' => false, 'transport_error' => "Unsupported language: $language"];
    }

    $lang    = PISTON_LANGUAGES[$language];
    $payload = json_encode([
        'language' => $lang['language'],
        'version'  => $lang['version'],
        'files'    => [['content' => $code]],
        'stdin'    => $stdin,
    ]);

    $ch = curl_init(PISTON_ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => JUDGE_TIMEOUT_S,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);

    $raw      = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr || !$raw) {
        return ['success' => false, 'transport_error' => 'Judge service unreachable: ' . $curlErr];
    }
    if ($httpCode !== 200) {
        return ['success' => false, 'transport_error' => "Judge returned HTTP $httpCode"];
    }

    $result = json_decode($raw, true);
    if (!is_array($result) || !isset($result['run'])) {
        return ['success' => false, 'transport_error' => 'Malformed judge response'];
    }

    $run = $result['run'];

    // Non-zero exit code or signal = runtime error
    $exitCode = $run['code'] ?? 0;
    $signal   = $run['signal'] ?? null;
    $stdout   = $run['stdout'] ?? '';
    $stderr   = $run['stderr'] ?? '';
    $output   = $stdout . ($stderr ? "\n" . $stderr : '');

    if ($signal) {
        return [
            'success'   => false,
            'transport_error' => "Process killed by signal: $signal",
            'output'    => $output,
            'stderr'    => $stderr,
            'exit_code' => $exitCode,
        ];
    }

    return [
        'success'   => true,
        'output'    => $stdout,
        'stderr'    => $stderr,
        'exit_code' => $exitCode,
    ];
}

// ── Public: judge a full submission ─────────────────────────
/**
 * Judge $code against every test case using Piston.
 *
 * $testCases: array of ['input' => string, 'expected_output' => string]
 *
 * Returns:
 *   verdict    string   Accepted | Wrong Answer | Runtime Error |
 *                       Time Limit Exceeded
 *   passed     int
 *   total      int
 *   runtime_ms int      max wall-clock ms across all test cases
 *   results    array    per-test detail
 *   error      string   first human-readable error message
 */
function judgeSubmission(string $code, string $language, array $testCases): array {
    if (empty($testCases)) {
        return [
            'verdict'    => 'Accepted',
            'passed'     => 0,
            'total'      => 0,
            'runtime_ms' => 0,
            'results'    => [],
            'error'      => '',
        ];
    }

    $results  = [];
    $passed   = 0;
    $total    = count($testCases);
    $verdict  = 'Accepted';
    $errorMsg = '';
    $maxRunMs = 0;

    foreach ($testCases as $i => $tc) {
        $input    = $tc['input']                ?? '';
        $expected = trim($tc['expected_output'] ?? '');

        $t0      = microtime(true);
        $run     = pistonRun($code, $language, $input);
        $elapsed = (int) round((microtime(true) - $t0) * 1000);
        $maxRunMs = max($maxRunMs, $elapsed);

        // ── Transport / service failure ───────────────────────
        if (!$run['success']) {
            if ($verdict === 'Accepted') {
                $verdict  = 'Runtime Error';
                $errorMsg = $run['transport_error'];
            }
            $results[] = [
                'test'     => $i + 1,
                'passed'   => false,
                'input'    => $input,
                'expected' => $expected,
                'got'      => $run['output'] ?? '',
                'error'    => $run['transport_error'],
            ];
            continue;
        }

        // ── Runtime error (non-zero exit code) ────────────────
        if ($run['exit_code'] !== 0) {
            if ($verdict === 'Accepted') {
                $verdict  = 'Runtime Error';
                $errorMsg = $run['stderr'] ?: 'Non-zero exit code: ' . $run['exit_code'];
            }
            $results[] = [
                'test'     => $i + 1,
                'passed'   => false,
                'input'    => $input,
                'expected' => $expected,
                'got'      => $run['output'],
                'error'    => $run['stderr'] ?: 'Runtime Error',
            ];
            continue;
        }

        // ── Time limit exceeded (wall-clock) ──────────────────
        if ($elapsed >= CODE_RUN_TIMEOUT_MS) {
            if ($verdict === 'Accepted') {
                $verdict  = 'Time Limit Exceeded';
                $errorMsg = 'Test case ' . ($i + 1) . ' exceeded the time limit.';
            }
            $results[] = [
                'test'     => $i + 1,
                'passed'   => false,
                'input'    => $input,
                'expected' => $expected,
                'got'      => 'TLE',
                'error'    => 'Time Limit Exceeded',
            ];
            continue;
        }

        // ── Compare output: Accepted or Wrong Answer ──────────
        $actual     = normalizeOutput($run['output']);
        $expectedN  = normalizeOutput($expected);
        $testPassed = ($actual === $expectedN);

        if ($testPassed) {
            $passed++;
        } elseif ($verdict === 'Accepted') {
            $verdict = 'Wrong Answer';
        }

        $results[] = [
            'test'     => $i + 1,
            'passed'   => $testPassed,
            'input'    => $input,
            'expected' => $expected,
            'got'      => $run['output'],
            'error'    => $run['stderr'] ?? '',
        ];
    }

    return [
        'verdict'    => $verdict,
        'passed'     => $passed,
        'total'      => $total,
        'runtime_ms' => $maxRunMs,
        'results'    => $results,
        'error'      => $errorMsg,
    ];
}

// ── Public: run code for the "Run" button (no submission) ───
/**
 * Execute $code with $stdin once and return raw output.
 * Does not record a submission or affect ratings.
 */
function runCode(string $code, string $language, string $stdin = ''): array {
    $run = pistonRun($code, $language, $stdin);

    if (!$run['success']) {
        return ['success' => false, 'error' => $run['transport_error']];
    }

    return [
        'success'   => true,
        'verdict'   => 'OK',
        'output'    => $run['output'],
        'stderr'    => $run['stderr'],
        'exit_code' => $run['exit_code'],
    ];
}

// ── Helpers ──────────────────────────────────────────────────
function normalizeOutput(string $s): string {
    $s     = str_replace("\r\n", "\n", $s);
    $lines = explode("\n", trim($s));
    $lines = array_map('rtrim', $lines);
    return implode("\n", $lines);
}

function supportedLanguages(): array {
    return array_keys(PISTON_LANGUAGES);
}
