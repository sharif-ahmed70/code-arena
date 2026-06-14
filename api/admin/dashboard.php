<?php
// ============================================================
//  CODE ARENA - Admin Dashboard API
//  Optimization notes:
//  - submissions(submitted_at), submissions(status), submissions(user_id),
//    submissions(problem_id, status) should stay indexed for these analytics.
//  - users(is_blocked) and problems(total_accepted) help overview/top cards.
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/adminAuthMiddleware.php';

requireAdminApi();
methodCheck('GET');

function adminTableExists(PDO $pdo, string $table): bool {
    try {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
        );
        $stmt->execute([$table]);
        return (int)$stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log('dashboard table check failed: ' . $e->getMessage());
        return false;
    }
}

function adminColumnExists(PDO $pdo, string $table, string $column): bool {
    try {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log('dashboard column check failed: ' . $e->getMessage());
        return false;
    }
}

function adminFetchValue(PDO $pdo, string $sql, array $params = [], int|float $fallback = 0): int|float {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $value = $stmt->fetchColumn();
        return is_numeric($value) ? $value + 0 : $fallback;
    } catch (PDOException $e) {
        error_log('dashboard value query failed: ' . $e->getMessage());
        return $fallback;
    }
}

function adminFetchRows(PDO $pdo, string $sql, array $params = []): array {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('dashboard rows query failed: ' . $e->getMessage());
        return [];
    }
}

function adminRecentDays(PDO $pdo): array {
    $rows = adminFetchRows(
        $pdo,
        'SELECT DATE(submitted_at) AS date, COUNT(*) AS count
         FROM submissions
         WHERE submitted_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
         GROUP BY DATE(submitted_at)
         ORDER BY date ASC'
    );

    $byDate = [];
    foreach ($rows as $row) {
        $byDate[$row['date']] = (int)$row['count'];
    }

    $trend = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $trend[] = ['date' => $date, 'count' => $byDate[$date] ?? 0];
    }
    return $trend;
}

$hasUsers = adminTableExists($pdo, 'users');
$hasProblems = adminTableExists($pdo, 'problems');
$hasSubmissions = adminTableExists($pdo, 'submissions');
$hasContests = adminTableExists($pdo, 'contests');
$hasAuditLogs = adminTableExists($pdo, 'audit_logs');

$totalUsers = $hasUsers ? (int)adminFetchValue($pdo, 'SELECT COUNT(*) FROM users') : 0;
$totalProblems = $hasProblems ? (int)adminFetchValue($pdo, 'SELECT COUNT(*) FROM problems') : 0;
$totalSubmissions = $hasSubmissions ? (int)adminFetchValue($pdo, 'SELECT COUNT(*) FROM submissions') : 0;
$totalContests = $hasContests ? (int)adminFetchValue($pdo, 'SELECT COUNT(*) FROM contests') : 0;

$submissionsToday = $hasSubmissions
    ? (int)adminFetchValue($pdo, 'SELECT COUNT(*) FROM submissions WHERE submitted_at >= CURDATE()')
    : 0;
$submissionsLast7Days = $hasSubmissions
    ? (int)adminFetchValue($pdo, 'SELECT COUNT(*) FROM submissions WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)')
    : 0;
$acceptedToday = $hasSubmissions
    ? (int)adminFetchValue($pdo, "SELECT COUNT(*) FROM submissions WHERE status = ? AND submitted_at >= CURDATE()", ['Accepted'])
    : 0;
$acceptedTotal = $hasSubmissions
    ? (int)adminFetchValue($pdo, 'SELECT COUNT(*) FROM submissions WHERE status = ?', ['Accepted'])
    : 0;

$successRate = $totalSubmissions > 0 ? round(($acceptedTotal / $totalSubmissions) * 100, 2) : 0.0;
$activeUsers7d = $hasSubmissions
    ? (int)adminFetchValue($pdo, 'SELECT COUNT(DISTINCT user_id) FROM submissions WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)')
    : 0;
$blockedUsers = ($hasUsers && adminColumnExists($pdo, 'users', 'is_blocked'))
    ? (int)adminFetchValue($pdo, 'SELECT COUNT(*) FROM users WHERE COALESCE(is_blocked, 0) = 1')
    : 0;

$overview = [
    'total_users' => $totalUsers,
    'total_problems' => $totalProblems,
    'total_submissions' => $totalSubmissions,
    'total_contests' => $totalContests,
    'submissions_today' => $submissionsToday,
    'submissions_last_7_days' => $submissionsLast7Days,
    'accepted_submissions_today' => $acceptedToday,
    'success_rate_percentage' => $successRate,
    'active_users_7d' => $activeUsers7d,
    'blocked_users' => $blockedUsers,
];

$stats = [
    'total_users' => $overview['total_users'],
    'total_problems' => $overview['total_problems'],
    'total_submissions' => $overview['total_submissions'],
    'total_contests' => $overview['total_contests'],
    'active_users_7d' => $overview['active_users_7d'],
    'blocked_users' => $overview['blocked_users'],
    'submissions_today' => $overview['submissions_today'],
    'submissions_last_7_days' => $overview['submissions_last_7_days'],
    'accepted_submissions_today' => $overview['accepted_submissions_today'],
    'success_rate_percentage' => $overview['success_rate_percentage'],
];

$mostActiveUsers = ($hasUsers && $hasSubmissions)
    ? adminFetchRows(
        $pdo,
        'SELECT u.id, u.username, u.email, COUNT(s.id) AS submissions_count
         FROM submissions s
         JOIN users u ON u.id = s.user_id
         GROUP BY u.id, u.username, u.email
         ORDER BY submissions_count DESC
         LIMIT 5'
    )
    : [];

$mostSolvedProblems = ($hasProblems && $hasSubmissions)
    ? adminFetchRows(
        $pdo,
        'SELECT p.id, p.title, p.slug, p.difficulty, COUNT(s.id) AS accepted_count
         FROM submissions s
         JOIN problems p ON p.id = s.problem_id
         WHERE s.status = ?
         GROUP BY p.id, p.title, p.slug, p.difficulty
         ORDER BY accepted_count DESC
         LIMIT 5',
        ['Accepted']
    )
    : [];

$mostFailedProblems = ($hasProblems && $hasSubmissions)
    ? adminFetchRows(
        $pdo,
        'SELECT p.id, p.title, p.slug, p.difficulty, COUNT(s.id) AS failed_count
         FROM submissions s
         JOIN problems p ON p.id = s.problem_id
         WHERE s.status IN (?, ?)
         GROUP BY p.id, p.title, p.slug, p.difficulty
         ORDER BY failed_count DESC
         LIMIT 5',
        ['Wrong Answer', 'Time Limit Exceeded']
    )
    : [];

$activityInsights = [
    'most_active_users' => $mostActiveUsers,
    'most_solved_problems' => $mostSolvedProblems,
    'most_failed_problems' => $mostFailedProblems,
];

$languageStats = $hasSubmissions
    ? adminFetchRows(
        $pdo,
        'SELECT COALESCE(NULLIF(language, \'\'), \'Unknown\') AS language, COUNT(*) AS count
         FROM submissions
         GROUP BY COALESCE(NULLIF(language, \'\'), \'Unknown\')
         ORDER BY count DESC'
    )
    : [];

$dailyTrends = $hasSubmissions ? adminRecentDays($pdo) : [];

$recentActivity = $hasAuditLogs
    ? adminFetchRows(
        $pdo,
        'SELECT event, user_id, ip_address, context, created_at
         FROM audit_logs
         ORDER BY created_at DESC
         LIMIT 10'
    )
    : [];

$recentSubmissions = ($hasUsers && $hasProblems && $hasSubmissions)
    ? adminFetchRows(
        $pdo,
        'SELECT s.id, s.status, s.language, s.submitted_at,
                u.username, p.title AS problem_title, p.slug AS problem_slug
         FROM submissions s
         JOIN users u ON u.id = s.user_id
         JOIN problems p ON p.id = s.problem_id
         ORDER BY s.submitted_at DESC
         LIMIT 8'
    )
    : [];

ok([
    'stats' => $stats,
    'recent_activity' => $recentActivity,
    'recent_submissions' => $recentSubmissions,
    'overview' => $overview,
    'activity' => $activityInsights,
    'language_stats' => $languageStats,
    'daily_trends' => $dailyTrends,
]);
