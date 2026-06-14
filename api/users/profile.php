<?php
// ============================================================
//  CODE ARENA — User Profile API
//  GET /api/users/profile.php          → own profile
//  GET /api/users/profile.php?username=X → public profile
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';

methodCheck('GET');

$username = trim($_GET['username'] ?? '');
$profileId = (int)($_GET['id'] ?? 0);

if ($profileId) {
    $stmt = $pdo->prepare(
        'SELECT id, username, role, hardcore_rating, learning_rating, roadmap_day, created_at
         FROM users WHERE id = ? AND COALESCE(is_deleted, 0) = 0'
    );
    $stmt->execute([$profileId]);
} elseif ($username) {
    $stmt = $pdo->prepare(
        'SELECT id, username, role, hardcore_rating, learning_rating, roadmap_day, created_at
         FROM users WHERE username = ? AND COALESCE(is_deleted, 0) = 0'
    );
    $stmt->execute([$username]);
} else {
    requireLogin();
    $stmt = $pdo->prepare(
        'SELECT id, username, email, role, hardcore_rating, learning_rating, roadmap_day, created_at
         FROM users WHERE id = ? AND COALESCE(is_deleted, 0) = 0'
    );
    $stmt->execute([currentUserId()]);
}

$user = $stmt->fetch();
if (!$user) err('User not found', 404);

$uid = $user['id'];

// Submission stats
$statsStmt = $pdo->prepare(
    'SELECT
        COUNT(*) AS total,
        SUM(status = "Accepted") AS accepted,
        SUM(status = "Wrong Answer") AS wrong_answer,
        SUM(status = "Runtime Error") AS runtime_error,
        COUNT(DISTINCT problem_id) AS problems_attempted,
        COUNT(DISTINCT CASE WHEN status = "Accepted" THEN problem_id END) AS problems_solved
     FROM submissions WHERE user_id = ?'
);
$statsStmt->execute([$uid]);
$stats = $statsStmt->fetch();

// Solved by difficulty
$diffStmt = $pdo->prepare(
    'SELECT p.difficulty, COUNT(DISTINCT s.problem_id) AS cnt
     FROM submissions s
     JOIN problems p ON p.id = s.problem_id AND COALESCE(p.is_deleted, 0) = 0
     WHERE s.user_id = ? AND s.status = "Accepted"
     GROUP BY p.difficulty'
);
$diffStmt->execute([$uid]);
$diffMap = ['Easy' => 0, 'Medium' => 0, 'Hard' => 0];
foreach ($diffStmt->fetchAll() as $r) {
    $diffMap[$r['difficulty']] = (int) $r['cnt'];
}

// Recent submissions (10)
$recStmt = $pdo->prepare(
    'SELECT s.id, s.status, s.language, s.submitted_at,
            p.title AS problem_title, p.slug AS problem_slug, p.difficulty
     FROM submissions s
     JOIN problems p ON p.id = s.problem_id AND COALESCE(p.is_deleted, 0) = 0
     WHERE s.user_id = ?
     ORDER BY s.submitted_at DESC LIMIT 10'
);
$recStmt->execute([$uid]);
$recent = $recStmt->fetchAll();

// Rating history (last 20 changes)
$ratingStmt = $pdo->prepare(
    'SELECT rating_type, old_rating, new_rating, delta, changed_at
     FROM rating_history WHERE user_id = ?
     ORDER BY changed_at DESC LIMIT 20'
);
$ratingStmt->execute([$uid]);
$ratingHistory = $ratingStmt->fetchAll();

// Language usage
$langStmt = $pdo->prepare(
    'SELECT language, COUNT(*) AS submissions,
            SUM(status = "Accepted") AS accepted
     FROM submissions
     WHERE user_id = ?
     GROUP BY language
     ORDER BY submissions DESC, language ASC'
);
$langStmt->execute([$uid]);
$languageStats = $langStmt->fetchAll();

// Daily activity for the last 35 active-window days
$activityStmt = $pdo->prepare(
    'SELECT DATE(submitted_at) AS day,
            COUNT(*) AS submissions,
            SUM(status = "Accepted") AS accepted
     FROM submissions
     WHERE user_id = ? AND submitted_at >= DATE_SUB(CURDATE(), INTERVAL 35 DAY)
     GROUP BY DATE(submitted_at)
     ORDER BY day ASC'
);
$activityStmt->execute([$uid]);
$activity = $activityStmt->fetchAll();

// Contest performance
$contestStmt = $pdo->prepare(
    'SELECT c.id, c.title, c.status,
            COUNT(DISTINCT s.problem_id) AS attempted,
            COUNT(DISTINCT CASE WHEN s.status = "Accepted" THEN s.problem_id END) AS solved,
            MAX(s.submitted_at) AS last_submission_at
     FROM contest_participants cp
     JOIN contests c ON c.id = cp.contest_id
     LEFT JOIN submissions s ON s.contest_id = c.id AND s.user_id = cp.user_id AND s.is_practice = 0
     WHERE cp.user_id = ?
     GROUP BY c.id
     ORDER BY COALESCE(MAX(s.submitted_at), c.start_time) DESC
     LIMIT 8'
);
$contestStmt->execute([$uid]);
$contestStats = $contestStmt->fetchAll();

// Roadmap progress
$roadmapStmt = $pdo->prepare(
    'SELECT day, completed_at FROM roadmap_progress WHERE user_id = ? ORDER BY day'
);
$roadmapStmt->execute([$uid]);
$roadmapProgress = $roadmapStmt->fetchAll();

// Weak areas — per-problem attempt/failure counts, then expand tags in PHP
$weakRawStmt = $pdo->prepare(
    'SELECT p.tags,
            COUNT(*) AS total_attempts,
            SUM(s.status != "Accepted") AS failures
     FROM submissions s
     JOIN problems p ON p.id = s.problem_id AND COALESCE(p.is_deleted, 0) = 0
     WHERE s.user_id = ?
     GROUP BY s.problem_id, p.tags'
);
$weakRawStmt->execute([$uid]);
$skipTags = ['beginner', 'loops', 'conditionals', 'math', 'recursion'];
$tagStats = [];
foreach ($weakRawStmt->fetchAll() as $row) {
    foreach (array_map('trim', explode(',', $row['tags'] ?? '')) as $tag) {
        if (!$tag || in_array($tag, $skipTags)) continue;
        if (!isset($tagStats[$tag])) $tagStats[$tag] = ['attempts' => 0, 'failures' => 0];
        $tagStats[$tag]['attempts'] += (int) $row['total_attempts'];
        $tagStats[$tag]['failures'] += (int) $row['failures'];
    }
}
$weakAreas = [];
foreach ($tagStats as $tag => $s) {
    if ($s['attempts'] < 3) continue;
    $weakAreas[] = [
        'tag'          => $tag,
        'attempts'     => $s['attempts'],
        'failures'     => $s['failures'],
        'failure_rate' => round($s['failures'] / $s['attempts'] * 100),
    ];
}
usort($weakAreas, fn($a, $b) => $b['failure_rate'] - $a['failure_rate']);
$weakAreas = array_slice($weakAreas, 0, 5);

ok([
    'user'            => $user,
    'stats'           => $stats,
    'solved_by_diff'  => $diffMap,
    'recent'          => $recent,
    'rating_history'  => $ratingHistory,
    'language_stats'  => $languageStats,
    'activity'        => $activity,
    'contest_stats'   => $contestStats,
    'roadmap_progress'=> $roadmapProgress,
    'weak_areas'      => $weakAreas,
]);
