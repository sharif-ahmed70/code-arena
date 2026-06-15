<?php
// ============================================================
//  CODE ARENA - Student Dashboard API
//  GET /api/users/dashboard.php
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';

methodCheck('GET');
requireLogin();

$uid = currentUserId();

$userStmt = $pdo->prepare(
    'SELECT id, username, role,
            COALESCE(skill_rating, hardcore_rating, 1200) AS skill_rating,
            COALESCE(skill_mode, "hardcore") AS skill_mode,
            COALESCE(contest_rating, 1200) AS contest_rating,
            hardcore_rating, learning_rating, roadmap_day, created_at
     FROM users WHERE id = ? AND COALESCE(is_deleted, 0) = 0'
);
$userStmt->execute([$uid]);
$user = $userStmt->fetch();
if (!$user) err('User not found', 404);

$statsStmt = $pdo->prepare(
    'SELECT
        COUNT(*) AS total_submissions,
        SUM(status = "Accepted") AS accepted_submissions,
        COUNT(DISTINCT problem_id) AS attempted_problems,
        COUNT(DISTINCT CASE WHEN status = "Accepted" THEN problem_id END) AS solved_problems,
        MAX(submitted_at) AS last_submission_at
     FROM submissions
     WHERE user_id = ?'
);
$statsStmt->execute([$uid]);
$stats = $statsStmt->fetch();

$streakStmt = $pdo->prepare(
    'SELECT DATE(submitted_at) AS day
     FROM submissions
     WHERE user_id = ?
     GROUP BY DATE(submitted_at)
     ORDER BY day DESC
     LIMIT 45'
);
$streakStmt->execute([$uid]);
$activeDays = array_map(fn($row) => $row['day'], $streakStmt->fetchAll());
$activeSet = array_fill_keys($activeDays, true);
$streak = 0;
$cursor = new DateTime('today');
if (empty($activeSet[$cursor->format('Y-m-d')])) {
    $cursor->modify('-1 day');
}
while (!empty($activeSet[$cursor->format('Y-m-d')])) {
    $streak++;
    $cursor->modify('-1 day');
}

$roadmapDay = max(1, (int)($user['roadmap_day'] ?? 1));
$roadmapStmt = $pdo->prepare(
    'SELECT p.id, p.title, p.slug, p.difficulty, p.tags, p.roadmap_day,
            EXISTS (
                SELECT 1 FROM submissions s
                WHERE s.user_id = ? AND s.problem_id = p.id AND s.status = "Accepted"
            ) AS solved
     FROM problems p
     WHERE p.is_public = 1 AND COALESCE(p.is_deleted, 0) = 0 AND p.roadmap_day = ?
     ORDER BY p.id
     LIMIT 8'
);
$roadmapStmt->execute([$uid, $roadmapDay]);
$roadmapProblems = $roadmapStmt->fetchAll();
$roadmapSolved = 0;
foreach ($roadmapProblems as $p) {
    if ((int)$p['solved'] === 1) $roadmapSolved++;
}

$nextRoadmapStmt = $pdo->prepare(
    'SELECT p.id, p.title, p.slug, p.difficulty, p.tags, p.roadmap_day
     FROM problems p
     WHERE p.is_public = 1
       AND COALESCE(p.is_deleted, 0) = 0
       AND p.roadmap_day >= ?
       AND NOT EXISTS (
            SELECT 1 FROM submissions s
            WHERE s.user_id = ? AND s.problem_id = p.id AND s.status = "Accepted"
       )
     ORDER BY p.roadmap_day, FIELD(p.difficulty, "Easy", "Medium", "Hard"), p.id
     LIMIT 1'
);
$nextRoadmapStmt->execute([$roadmapDay, $uid]);
$nextRoadmap = $nextRoadmapStmt->fetch();

$savedStmt = $pdo->prepare(
    'SELECT p.id, p.title, p.slug, p.difficulty, p.tags, pb.created_at AS saved_at,
            EXISTS (
                SELECT 1 FROM submissions s
                WHERE s.user_id = ? AND s.problem_id = p.id AND s.status = "Accepted"
            ) AS solved
     FROM problem_bookmarks pb
     JOIN problems p ON p.id = pb.problem_id
     WHERE pb.user_id = ? AND p.is_public = 1 AND COALESCE(p.is_deleted, 0) = 0
     ORDER BY pb.created_at DESC
     LIMIT 6'
);
$savedStmt->execute([$uid, $uid]);
$savedProblems = $savedStmt->fetchAll();

$weakRawStmt = $pdo->prepare(
    'SELECT p.tags,
            COUNT(*) AS attempts,
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
        if (!$tag || in_array($tag, $skipTags, true)) continue;
        if (!isset($tagStats[$tag])) $tagStats[$tag] = ['attempts' => 0, 'failures' => 0];
        $tagStats[$tag]['attempts'] += (int)$row['attempts'];
        $tagStats[$tag]['failures'] += (int)$row['failures'];
    }
}
$weakTopic = null;
foreach ($tagStats as $tag => $row) {
    if ($row['attempts'] < 3) continue;
    $rate = $row['failures'] / max(1, $row['attempts']);
    if (!$weakTopic || $rate > $weakTopic['failure_rate']) {
        $weakTopic = [
            'tag' => $tag,
            'attempts' => $row['attempts'],
            'failures' => $row['failures'],
            'failure_rate' => round($rate * 100),
        ];
    }
}

$recommended = null;
if ($weakTopic) {
    $recStmt = $pdo->prepare(
        'SELECT p.id, p.title, p.slug, p.difficulty, p.tags, p.roadmap_day
         FROM problems p
         WHERE p.is_public = 1
           AND COALESCE(p.is_deleted, 0) = 0
           AND p.tags LIKE ?
           AND NOT EXISTS (
                SELECT 1 FROM submissions s
                WHERE s.user_id = ? AND s.problem_id = p.id AND s.status = "Accepted"
           )
         ORDER BY FIELD(p.difficulty, "Easy", "Medium", "Hard"), p.total_accepted DESC, p.id
         LIMIT 1'
    );
    $recStmt->execute(['%' . $weakTopic['tag'] . '%', $uid]);
    $recommended = $recStmt->fetch();
}
if (!$recommended) {
    $recStmt = $pdo->prepare(
        'SELECT p.id, p.title, p.slug, p.difficulty, p.tags, p.roadmap_day
         FROM problems p
         WHERE p.is_public = 1
           AND COALESCE(p.is_deleted, 0) = 0
           AND NOT EXISTS (
                SELECT 1 FROM submissions s
                WHERE s.user_id = ? AND s.problem_id = p.id AND s.status = "Accepted"
           )
         ORDER BY FIELD(p.difficulty, "Easy", "Medium", "Hard"), p.total_accepted DESC, p.id
         LIMIT 1'
    );
    $recStmt->execute([$uid]);
    $recommended = $recStmt->fetch();
}

$contestStmt = $pdo->prepare(
    'SELECT c.id, c.title, c.slug, c.status, c.start_time, c.end_time,
            EXISTS (
                SELECT 1 FROM contest_participants cp
                WHERE cp.contest_id = c.id AND cp.user_id = ?
            ) AS registered
     FROM contests c
     WHERE c.status IN ("upcoming", "active")
     ORDER BY c.start_time ASC
     LIMIT 5'
);
$contestStmt->execute([$uid]);
$contests = $contestStmt->fetchAll();

$recentStmt = $pdo->prepare(
    'SELECT s.id, s.status, s.language, s.submitted_at, s.contest_id,
            p.title AS problem_title, p.slug AS problem_slug, p.difficulty
     FROM submissions s
     JOIN problems p ON p.id = s.problem_id AND COALESCE(p.is_deleted, 0) = 0
     WHERE s.user_id = ?
     ORDER BY s.submitted_at DESC
     LIMIT 8'
);
$recentStmt->execute([$uid]);
$recent = $recentStmt->fetchAll();

ok([
    'user' => $user,
    'stats' => [
        'total_submissions' => (int)($stats['total_submissions'] ?? 0),
        'accepted_submissions' => (int)($stats['accepted_submissions'] ?? 0),
        'attempted_problems' => (int)($stats['attempted_problems'] ?? 0),
        'solved_problems' => (int)($stats['solved_problems'] ?? 0),
        'last_submission_at' => $stats['last_submission_at'] ?? null,
        'streak_days' => $streak,
    ],
    'roadmap' => [
        'day' => $roadmapDay,
        'solved' => $roadmapSolved,
        'total' => count($roadmapProblems),
        'problems' => $roadmapProblems,
        'next_problem' => $nextRoadmap,
    ],
    'saved_problems' => $savedProblems,
    'weak_topic' => $weakTopic,
    'recommended_problem' => $recommended,
    'contests' => $contests,
    'recent_submissions' => $recent,
]);
