<?php
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ob_start();
set_exception_handler(function (Throwable $e): void {
    error_log('[CodeArenaSubmitException] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
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
    error_log('[CodeArenaSubmitFatal] ' . json_encode($error, JSON_UNESCAPED_SLASHES));
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
//  CODE ARENA — Submit Solution  (HIGH effort)
//  POST /api/submissions/submit.php
//  Body: { problem_id, code, language, hints_used?, contest_id? }
//
//  Flow:
//    1. Validate input
//    2. Load problem + test_cases
//    3. Judge via Piston API
//    4. Persist submission
//    5. If Accepted → update ratings + roadmap
//    6. Return verdict
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';
require_once '../../includes/judge.php';
require_once '../../includes/rate_limit.php';
require_once '../../includes/contest.php';
require_once '../../includes/leaderboard.php';

methodCheck('POST');
requireLogin();
if (isAdmin()) err('Admins cannot submit solutions', 403);
syncContestStatuses($pdo);

$body       = jsonBody();
$problemId  = (int) ($body['problem_id'] ?? 0);
$code       = $body['code']      ?? '';
$language   = cleanString($body['language'] ?? 'javascript', 30);
$contestId  = !empty($body['contest_id'])  ? (int) $body['contest_id']  : null;
$isPractice = !empty($body['is_practice']) ? true : false;
$solveMode  = cleanString($body['solve_mode'] ?? 'hardcore', 20);
if (!in_array($solveMode, ['hardcore', 'practice'], true)) $solveMode = 'hardcore';

if (!$problemId || !$code || !$language) err('problem_id, code and language are required');
if (!is_string($code)) err('code must be a string');
if (!validLanguage($language, supportedLanguages())) err('Unsupported language');
if (strlen($code) > 65536) err('Code too long (max 64 KB)');
if ($contestId !== null && $contestId <= 0) err('Invalid contest_id');

// ── Practice guard ───────────────────────────────────────────
// If flagged as practice, verify the session exists and hasn't expired.
if ($isPractice && $contestId) {
    $uid = currentUserId();
    $ps  = $pdo->prepare(
        'SELECT started_at, duration_seconds FROM practice_sessions
         WHERE user_id = ? AND contest_id = ?'
    );
    $ps->execute([$uid, $contestId]);
    $sess = $ps->fetch();
    if (!$sess) err('No active practice session for this contest', 403);
    $elapsed = time() - strtotime($sess['started_at']);
    if ($elapsed >= (int)$sess['duration_seconds']) err('Practice session has expired', 403);
} elseif ($isPractice) {
    err('contest_id required for practice submissions', 400);
}

$userId = currentUserId();
enforceRateLimit($pdo, rateLimitKey('submit', (string)$userId), 20, 60);
$submissionOrgId = null;

// ── Load problem ─────────────────────────────────────────────
$stmt = $pdo->prepare(
    'SELECT id, title, difficulty, test_cases, total_submissions, total_accepted,
            time_limit_ms, roadmap_day
     FROM problems WHERE id = ? AND is_public = 1 AND COALESCE(is_deleted, 0) = 0'
);
$stmt->execute([$problemId]);
$problem = $stmt->fetch();
if (!$problem) err('Problem not found', 404);

$testCases = loadProblemTestCases($pdo, $problemId, $problem['test_cases'] ?? null);
error_log('[CodeArenaSubmit] payload ' . json_encode([
    'user_id' => $userId,
    'problem_id' => $problemId,
    'contest_id' => $contestId,
    'language' => $language,
    'code_bytes' => strlen($code),
    'test_case_count' => count($testCases),
], JSON_UNESCAPED_SLASHES));
if (empty($testCases)) {
    err('No test cases are configured for this problem. Please contact an admin.', 500);
}
if (count($testCases) > 50) err('Too many test cases configured for this problem', 500);

// Normal contest submissions must belong to an active contest and a registered
// participant. Practice submissions are guarded separately above.
if ($contestId && !$isPractice) {
    $contestStmt = $pdo->prepare('SELECT id, status, org_id FROM contests WHERE id = ?');
    $contestStmt->execute([$contestId]);
    $contest = $contestStmt->fetch();
    if (!$contest) err('Contest not found', 404);
    if ($contest['status'] !== 'active') err('Contest is not active', 403);
    $submissionOrgId = $contest['org_id'] ?? null;

    $regStmt = $pdo->prepare('SELECT status FROM contest_participants WHERE contest_id = ? AND user_id = ?');
    $regStmt->execute([$contestId, $userId]);
    $participantStatus = $regStmt->fetchColumn();
    if (!$participantStatus) err('Register for this contest before submitting', 403);
    if (in_array($participantStatus, ['removed', 'banned', 'rejected'], true)) {
        err('Your contest participation is not active', 403);
    }

    $cpStmt = $pdo->prepare('SELECT COUNT(*) FROM contest_problems WHERE contest_id = ? AND problem_id = ?');
    $cpStmt->execute([$contestId, $problemId]);
    if (!$cpStmt->fetchColumn()) err('Problem is not part of this contest', 403);
}

// ── Hints used (from session, or client-provided as override) ─
$sessionKey = 'hints_' . $problemId;
$hintsUsed  = (int) max(
    $_SESSION[$sessionKey] ?? 0,
    (int) ($body['hints_used'] ?? 0)
);

// ── Judge ────────────────────────────────────────────────────
$judgeResult = judgeSubmission($code, $language, $testCases);
error_log('[CodeArenaSubmit] judge_result ' . json_encode([
    'problem_id' => $problemId,
    'contest_id' => $contestId,
    'verdict' => $judgeResult['verdict'] ?? null,
    'passed' => $judgeResult['passed'] ?? null,
    'total' => $judgeResult['total'] ?? null,
    'runtime_ms' => $judgeResult['runtime_ms'] ?? null,
    'error' => $judgeResult['error'] ?? '',
], JSON_UNESCAPED_SLASHES));

$verdict   = $judgeResult['verdict'];
$runtimeMs = $judgeResult['runtime_ms'];

// ── Persist submission ───────────────────────────────────────
try {
    $pdo->beginTransaction();

$ins = $pdo->prepare(
    'INSERT INTO submissions
        (user_id, problem_id, code, language, status, hints_used, contest_id, org_id,
         runtime_ms, test_results, is_practice)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$ins->execute([
    $userId,
    $problemId,
    $code,
    $language,
    $verdict,
    $hintsUsed,
    $contestId,
    $submissionOrgId,
    $runtimeMs,
    json_encode($judgeResult['results']),
    $isPractice ? 1 : 0,
]);
$submissionId = (int) $pdo->lastInsertId();

if ($contestId === null && !$isPractice) {
    recordPracticeLeaderboardSubmission($pdo, $userId, $problemId, $verdict, $language);
}

// ── Update problem counters ──────────────────────────────────
$pdo->prepare('UPDATE problems SET total_submissions = total_submissions + 1 WHERE id = ? AND COALESCE(is_deleted, 0) = 0')
    ->execute([$problemId]);
if ($verdict === 'Accepted') {
    $pdo->prepare('UPDATE problems SET total_accepted = total_accepted + 1 WHERE id = ? AND COALESCE(is_deleted, 0) = 0')
        ->execute([$problemId]);
}

// ── Post-accept logic (rating + roadmap) ─────────────────────
// Practice submissions NEVER update ratings, roadmap, or leaderboard.
$ratingDeltas = ['skill' => 0, 'mode' => null, 'hardcore' => 0, 'learning' => 0];

if ($verdict === 'Accepted' && !$isPractice && $contestId === null) {
    // Check if this is the FIRST accepted submission for this problem by this user
    // (exclude practice submissions from the "first accept" check)
    $firstAccept = $pdo->prepare(
        'SELECT COUNT(*) FROM submissions
         WHERE user_id = ? AND problem_id = ? AND status = "Accepted"
           AND id != ? AND is_practice = 0'
    );
    $firstAccept->execute([$userId, $problemId, $submissionId]);
    $isFirstAccept = ($firstAccept->fetchColumn() == 0);

    if ($isFirstAccept) {
        $ratingDeltas = updateRatings($pdo, $userId, $problem, $hintsUsed, $solveMode);
        checkRoadmapUnlock($pdo, $userId, $problem);
    }

    // Clear hint session key after a successful solve
    unset($_SESSION[$sessionKey]);
}

$pdo->commit();
appLog($pdo, 'submission_created', [
    'submission_id' => $submissionId,
    'problem_id' => $problemId,
    'verdict' => $verdict,
    'contest_id' => $contestId,
]);

ok([
    'submission_id' => $submissionId,
    'verdict'       => $verdict,
    'passed'        => $judgeResult['passed'],
    'total'         => $judgeResult['total'],
    'runtime_ms'    => $runtimeMs,
    'results'       => $judgeResult['results'],
    'error'         => $judgeResult['error'],
    'rating_delta'  => $ratingDeltas,
]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('submission persistence failed: ' . $e->getMessage());
    err('Submission could not be saved. Please try again.', 500);
}

// ── Rating update (ELO-style) ─────────────────────────────────
/**
 * Learning rating: awarded on every first-acceptance, reduced by hints.
 *   Base delta:  Easy=15, Medium=25, Hard=40
 *   Hint mult:   0 hints→1.0, 1→0.75, 2→0.5, 3→0.25
 *
 * Hardcore rating: only if 0 hints used.
 *   Base delta:  Easy=10, Medium=20, Hard=35
 *   Penalty:     -2 per prior WA (this problem), min 1
 */
function loadProblemTestCases(PDO $pdo, int $problemId, ?string $jsonCases): array {
    $cases = normalizeProblemTestCases(json_decode($jsonCases ?: '[]', true));
    if ($cases) {
        return $cases;
    }

    if (!tableExists($pdo, 'test_cases')) {
        return [];
    }

    $stmt = $pdo->prepare(
        'SELECT input, expected_output
         FROM test_cases
         WHERE problem_id = ?
         ORDER BY id ASC'
    );
    $stmt->execute([$problemId]);
    return normalizeProblemTestCases($stmt->fetchAll());
}

function normalizeProblemTestCases(mixed $rawCases): array {
    if (!is_array($rawCases)) {
        return [];
    }

    $clean = [];
    foreach ($rawCases as $case) {
        if (!is_array($case)) {
            continue;
        }
        $expected = $case['expected_output'] ?? $case['output'] ?? null;
        if ($expected === null) {
            continue;
        }
        $clean[] = [
            'input' => (string)($case['input'] ?? ''),
            'expected_output' => (string)$expected,
        ];
    }
    return $clean;
}

function tableExists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
    $stmt->execute([$table]);
    return (bool)$stmt->fetchColumn();
}

function updateRatings(PDO $pdo, int $userId, array $problem, int $hintsUsed, string $solveMode = 'hardcore'): array {
    $diff = $problem['difficulty'];

    $waStmt = $pdo->prepare(
        'SELECT COUNT(*) FROM submissions
         WHERE user_id = ? AND problem_id = ? AND status = "Wrong Answer"'
    );
    $waStmt->execute([$userId, $problem['id']]);
    $waCount = (int) $waStmt->fetchColumn();

    $rRow = $pdo->prepare('SELECT skill_rating FROM users WHERE id = ? AND COALESCE(is_deleted, 0) = 0');
    $rRow->execute([$userId]);
    $ratings = $rRow->fetch();
    if (!$ratings) return ['skill' => 0, 'mode' => null, 'hardcore' => 0, 'learning' => 0];

    $mode = $solveMode === 'practice' ? 'practice' : 'hardcore';

    if ($mode === 'hardcore') {
        $base = ['Easy' => 18, 'Medium' => 32, 'Hard' => 50][$diff] ?? 18;
        $delta = max(1, $base - ($hintsUsed * 10) - ($waCount * 3));
    } else {
        $base = ['Easy' => 9, 'Medium' => 16, 'Hard' => 28][$diff] ?? 9;
        $delta = max(1, $base - ($hintsUsed * 3) - $waCount);
    }

    $oldSkill = (int) ($ratings['skill_rating'] ?? 1200);
    $newSkill = max(800, $oldSkill + $delta);

    $pdo->prepare('UPDATE users SET skill_rating = ? WHERE id = ?')
        ->execute([$newSkill, $userId]);

    $hist = $pdo->prepare(
        'INSERT INTO rating_history
            (user_id, problem_id, rating_type, old_rating, new_rating, delta)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    if ($delta !== 0) {
        $historyType = $mode === 'practice' ? 'learning' : 'hardcore';
        $hist->execute([$userId, $problem['id'], $historyType, $oldSkill, $newSkill, $delta]);
    }

    return [
        'skill' => $delta,
        'mode' => $mode,
        'hardcore' => $mode === 'hardcore' ? $delta : 0,
        'learning' => $mode === 'practice' ? $delta : 0,
    ];
}

// ── Roadmap unlock ────────────────────────────────────────────
/**
 * If the problem belongs to a roadmap day, check whether the user has
 * now solved ALL problems for that day. If so:
 *   - Insert into roadmap_progress
 *   - Advance users.roadmap_day to day+1
 */
function checkRoadmapUnlock(PDO $pdo, int $userId, array $problem): void {
    $day = $problem['roadmap_day'];
    if (!$day) return;

    // All problems for this roadmap day
    $stmt = $pdo->prepare('SELECT id FROM problems WHERE roadmap_day = ? AND is_public = 1 AND COALESCE(is_deleted, 0) = 0');
    $stmt->execute([$day]);
    $dayProblems = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($dayProblems)) return;

    // Check how many the user has accepted
    $in       = implode(',', array_fill(0, count($dayProblems), '?'));
    $solvedSt = $pdo->prepare(
        "SELECT COUNT(DISTINCT problem_id) FROM submissions
         WHERE user_id = ? AND problem_id IN ($in) AND status = 'Accepted'"
    );
    $solvedSt->execute(array_merge([$userId], $dayProblems));
    $solvedCount = (int) $solvedSt->fetchColumn();

    if ($solvedCount < count($dayProblems)) return;

    // Already recorded?
    $check = $pdo->prepare('SELECT id FROM roadmap_progress WHERE user_id = ? AND day = ?');
    $check->execute([$userId, $day]);
    if ($check->fetch()) return;

    // Record day completion
    $pdo->prepare('INSERT INTO roadmap_progress (user_id, day) VALUES (?, ?)')
        ->execute([$userId, $day]);

    // Advance user's roadmap_day
    $pdo->prepare('UPDATE users SET roadmap_day = GREATEST(roadmap_day, ?) WHERE id = ? AND COALESCE(is_deleted, 0) = 0')
        ->execute([$day + 1, $userId]);
}
