<?php
// ============================================================
//  CODE ARENA - Mistake Review API
//  GET /api/users/review.php
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';

methodCheck('GET');
requireLogin();

$uid = currentUserId();

$statsStmt = $pdo->prepare(
    'SELECT
        COUNT(*) AS failed_submissions,
        COUNT(DISTINCT s.problem_id) AS failed_problems,
        SUM(s.status = "Wrong Answer") AS wrong_answer,
        SUM(s.status = "Runtime Error") AS runtime_error,
        SUM(s.status = "Time Limit Exceeded") AS tle,
        SUM(s.status = "Compilation Error") AS compilation_error
     FROM submissions s
     WHERE s.user_id = ? AND s.status <> "Accepted"'
);
$statsStmt->execute([$uid]);
$stats = $statsStmt->fetch();

$retryStmt = $pdo->prepare(
    'SELECT p.id, p.title, p.slug, p.difficulty, p.tags,
            COUNT(*) AS failed_attempts,
            MAX(s.submitted_at) AS last_failed_at,
            SUBSTRING_INDEX(
                GROUP_CONCAT(s.status ORDER BY s.submitted_at DESC SEPARATOR "||"),
                "||",
                1
            ) AS last_status,
            SUM(s.hints_used) AS hints_used
     FROM submissions s
     JOIN problems p ON p.id = s.problem_id
     WHERE s.user_id = ?
       AND s.status <> "Accepted"
       AND p.is_public = 1
       AND NOT EXISTS (
            SELECT 1 FROM submissions ok
            WHERE ok.user_id = s.user_id
              AND ok.problem_id = s.problem_id
              AND ok.status = "Accepted"
       )
     GROUP BY p.id, p.title, p.slug, p.difficulty, p.tags
     ORDER BY failed_attempts DESC, last_failed_at DESC
     LIMIT 12'
);
$retryStmt->execute([$uid]);
$retryQueue = $retryStmt->fetchAll();

$recentStmt = $pdo->prepare(
    'SELECT s.id, s.problem_id, s.status, s.language, s.runtime_ms, s.hints_used,
            s.submitted_at, s.is_practice,
            p.title AS problem_title, p.slug AS problem_slug, p.difficulty, p.tags
     FROM submissions s
     JOIN problems p ON p.id = s.problem_id
     WHERE s.user_id = ? AND s.status <> "Accepted"
     ORDER BY s.submitted_at DESC
     LIMIT 10'
);
$recentStmt->execute([$uid]);
$recentFailures = $recentStmt->fetchAll();

$topicStmt = $pdo->prepare(
    'SELECT p.tags,
            COUNT(*) AS attempts,
            SUM(s.status <> "Accepted") AS failures,
            COUNT(DISTINCT s.problem_id) AS problems
     FROM submissions s
     JOIN problems p ON p.id = s.problem_id
     WHERE s.user_id = ?
     GROUP BY p.tags'
);
$topicStmt->execute([$uid]);
$skipTags = ['beginner', 'loops', 'conditionals', 'math', 'recursion'];
$topics = [];
foreach ($topicStmt->fetchAll() as $row) {
    foreach (array_map('trim', explode(',', $row['tags'] ?? '')) as $tag) {
        if (!$tag || in_array($tag, $skipTags, true)) continue;
        if (!isset($topics[$tag])) {
            $topics[$tag] = ['tag' => $tag, 'attempts' => 0, 'failures' => 0, 'problems' => 0];
        }
        $topics[$tag]['attempts'] += (int)$row['attempts'];
        $topics[$tag]['failures'] += (int)$row['failures'];
        $topics[$tag]['problems'] += (int)$row['problems'];
    }
}
$topicReview = [];
foreach ($topics as $topic) {
    if ($topic['attempts'] < 2 || $topic['failures'] === 0) continue;
    $topic['failure_rate'] = round($topic['failures'] / max(1, $topic['attempts']) * 100);
    $topicReview[] = $topic;
}
usort($topicReview, fn($a, $b) => [$b['failure_rate'], $b['failures']] <=> [$a['failure_rate'], $a['failures']]);
$topicReview = array_slice($topicReview, 0, 8);

$statusBreakdown = [
    'Wrong Answer' => (int)($stats['wrong_answer'] ?? 0),
    'Runtime Error' => (int)($stats['runtime_error'] ?? 0),
    'Time Limit Exceeded' => (int)($stats['tle'] ?? 0),
    'Compilation Error' => (int)($stats['compilation_error'] ?? 0),
];

ok([
    'stats' => [
        'failed_submissions' => (int)($stats['failed_submissions'] ?? 0),
        'failed_problems' => (int)($stats['failed_problems'] ?? 0),
        'status_breakdown' => $statusBreakdown,
    ],
    'retry_queue' => $retryQueue,
    'topic_review' => $topicReview,
    'recent_failures' => $recentFailures,
]);
