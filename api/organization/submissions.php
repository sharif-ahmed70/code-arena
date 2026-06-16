<?php
// ============================================================
//  Organization Contest Analytics + Submission Control API
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/organization.php';
require_once '../../includes/response.php';

$org = requireOrganizationApi($pdo);
$orgId = (int)$org['id'];
methodCheck('GET');

function orgAnalyticsRows(PDO $pdo, string $sql, array $params): array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function orgAnalyticsOne(PDO $pdo, string $sql, array $params): array {
    $rows = orgAnalyticsRows($pdo, $sql, $params);
    return $rows[0] ?? [];
}

function contestStatusLabel(array $contest): string {
    $orgStatus = $contest['org_status'] ?? '';
    if ($orgStatus) {
        return match ($orgStatus) {
            'live' => 'Live',
            'ended', 'archived' => 'Ended',
            default => 'Upcoming',
        };
    }

    $status = $contest['status'] ?? 'upcoming';
    return match ($status) {
        'active' => 'Live',
        'ended' => 'Ended',
        default => 'Upcoming',
    };
}

function orgProblemTags(?string $rawTags): array {
    $rawTags = trim((string)$rawTags);
    if ($rawTags === '') return [];

    $decoded = json_decode($rawTags, true);
    if (is_array($decoded)) {
        return array_values(array_filter(array_map('trim', array_map('strval', $decoded))));
    }

    return array_values(array_filter(array_map('trim', explode(',', $rawTags))));
}

function submissionFilterWhere(PDO $pdo, int $orgId, array $query, array &$params): array {
    $where = ['s.org_id = ?'];
    $params = [$orgId];

    $contestId = (int)($query['contest_id'] ?? 0);
    if ($contestId) {
        requireOwnedContest($pdo, $orgId, $contestId);
        $where[] = 's.contest_id = ?';
        $params[] = $contestId;
    }

    $problemId = (int)($query['problem_id'] ?? 0);
    $problem = cleanString($query['problem'] ?? '', 100);
    if ($problemId) {
        $where[] = 's.problem_id = ?';
        $params[] = $problemId;
    } elseif ($problem) {
        $where[] = '(p.title LIKE ? OR p.slug LIKE ?)';
        $params[] = "%$problem%";
        $params[] = "%$problem%";
    }

    $user = cleanString($query['user'] ?? '', 100);
    if ($user) {
        $where[] = '(u.username LIKE ? OR u.email LIKE ? OR u.name LIKE ?)';
        $params[] = "%$user%";
        $params[] = "%$user%";
        $params[] = "%$user%";
    }

    $status = cleanString($query['status'] ?? '', 40);
    if ($status) {
        if (!in_array($status, ['Accepted', 'Wrong Answer', 'Runtime Error', 'Time Limit Exceeded', 'Compilation Error', 'Pending'], true)) err('Invalid status');
        $where[] = 's.status = ?';
        $params[] = $status;
    }

    $dateFrom = cleanString($query['date_from'] ?? '', 20);
    $dateTo = cleanString($query['date_to'] ?? '', 20);
    if ($dateFrom && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
        $where[] = 's.submitted_at >= ?';
        $params[] = $dateFrom . ' 00:00:00';
    }
    if ($dateTo && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
        $where[] = 's.submitted_at <= ?';
        $params[] = $dateTo . ' 23:59:59';
    }

    return $where;
}

$mode = cleanString($_GET['mode'] ?? 'contests', 30);
$contestId = (int)($_GET['contest_id'] ?? 0);

if ($mode === 'contests') {
    $stmt = $pdo->prepare(
        'SELECT c.id, c.title, c.slug, c.status, c.org_status, c.start_time, c.end_time,
                (SELECT COUNT(DISTINCT cp.user_id)
                 FROM contest_participants cp
                 WHERE cp.contest_id = c.id
                   AND (cp.org_id = c.org_id OR cp.org_id IS NULL)
                   AND COALESCE(cp.status, "registered") NOT IN ("removed", "banned", "rejected")) AS total_participants,
                (SELECT COUNT(DISTINCT s.id)
                 FROM submissions s
                 WHERE s.contest_id = c.id
                   AND s.org_id = c.org_id
                   AND COALESCE(s.is_practice, 0) = 0) AS total_submissions,
                (SELECT COUNT(DISTINCT s.id)
                 FROM submissions s
                 WHERE s.contest_id = c.id
                   AND s.org_id = c.org_id
                   AND COALESCE(s.is_practice, 0) = 0
                   AND s.status = "Accepted") AS accepted_submissions
         FROM contests c
         WHERE c.org_id = ?
         ORDER BY c.start_time DESC, c.id DESC'
    );
    $stmt->execute([$orgId]);
    $contests = array_map(function (array $row): array {
        $statusLabel = contestStatusLabel($row);
        $total = (int)($row['total_submissions'] ?? 0);
        $accepted = (int)($row['accepted_submissions'] ?? 0);
        if ($statusLabel === 'Upcoming') {
            $total = 0;
            $accepted = 0;
        }
        return [
            'id' => (int)$row['id'],
            'title' => $row['title'],
            'slug' => $row['slug'],
            'status' => $row['status'],
            'status_label' => $statusLabel,
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time'],
            'total_participants' => (int)($row['total_participants'] ?? 0),
            'total_submissions' => $total,
            'accepted_submissions' => $accepted,
            'acceptance_rate' => $total > 0 ? round(($accepted / $total) * 100, 1) : 0,
            'analytics_available' => $statusLabel !== 'Upcoming',
        ];
    }, $stmt->fetchAll());
    ok(['contests' => $contests]);
}

if ($mode === 'analytics') {
    if (!$contestId) err('contest_id required');
    $contest = requireOwnedContest($pdo, $orgId, $contestId);
    $statusLabel = contestStatusLabel($contest);

    $filterParams = [];
    $where = submissionFilterWhere($pdo, $orgId, $_GET, $filterParams);
    $whereSql = implode(' AND ', $where);

    $overview = orgAnalyticsOne(
        $pdo,
        "SELECT COUNT(DISTINCT s.id) AS total_submissions,
                SUM(s.status = 'Accepted') AS accepted_submissions,
                SUM(s.status <> 'Accepted') AS wrong_submissions
         FROM submissions s
         JOIN users u ON u.id = s.user_id AND COALESCE(u.is_deleted, 0) = 0
         JOIN problems p ON p.id = s.problem_id
         WHERE $whereSql",
        $filterParams
    );
    $participantOverview = orgAnalyticsOne(
        $pdo,
        'SELECT COUNT(DISTINCT cp.user_id) AS total_participants
         FROM contest_participants cp
         JOIN contests c ON c.id = cp.contest_id AND c.org_id = ?
         WHERE cp.contest_id = ?
           AND (cp.org_id = ? OR cp.org_id IS NULL)
           AND cp.status NOT IN ("removed", "banned", "rejected")',
        [$orgId, $contestId, $orgId]
    );

    $problems = orgAnalyticsRows(
        $pdo,
        'SELECT DISTINCT p.id, p.title
         FROM contest_problems cp
         JOIN problems p ON p.id = cp.problem_id
         JOIN contests c ON c.id = cp.contest_id AND c.org_id = ?
         WHERE cp.contest_id = ?
         ORDER BY cp.order_index, p.title',
        [$orgId, $contestId]
    );

    if ($statusLabel === 'Upcoming') {
        ok([
            'contest' => [
                'id' => (int)$contest['id'],
                'title' => $contest['title'],
                'status' => $contest['status'],
                'org_status' => $contest['org_status'] ?? null,
                'status_label' => $statusLabel,
                'start_time' => $contest['start_time'],
                'end_time' => $contest['end_time'],
                'analytics_available' => false,
            ],
            'overview' => [
                'total_participants' => (int)($participantOverview['total_participants'] ?? 0),
                'total_submissions' => 0,
                'accepted_submissions' => 0,
                'wrong_submissions' => 0,
                'acceptance_rate' => 0,
            ],
            'charts' => [
                'submission_trend' => [],
                'verdict_distribution' => [],
                'difficulty_success' => [],
                'participation_distribution' => [],
            ],
            'participants' => [
                'top_performers' => [],
                'most_active' => [],
                'first_ac' => [],
                'highest_wrong' => [],
            ],
            'insights' => [
                'hardest_problem' => null,
                'most_failed_problem' => null,
                'peak_submission_time' => null,
                'weak_topic_area' => null,
            ],
            'submissions' => [],
            'problems' => $problems,
            'message' => 'Submission analytics will become available when this contest goes live.',
        ]);
    }

    $trend = orgAnalyticsRows(
        $pdo,
        "SELECT DATE_FORMAT(s.submitted_at, '%Y-%m-%d %H:00:00') AS bucket,
                COUNT(*) AS submissions,
                SUM(s.status = 'Accepted') AS accepted
         FROM submissions s
         JOIN users u ON u.id = s.user_id AND COALESCE(u.is_deleted, 0) = 0
         JOIN problems p ON p.id = s.problem_id
         WHERE $whereSql
         GROUP BY DATE_FORMAT(s.submitted_at, '%Y-%m-%d %H')
         ORDER BY bucket ASC",
        $filterParams
    );

    $verdictDistribution = orgAnalyticsRows(
        $pdo,
        "SELECT s.status, COUNT(*) AS count
         FROM submissions s
         JOIN users u ON u.id = s.user_id AND COALESCE(u.is_deleted, 0) = 0
         JOIN problems p ON p.id = s.problem_id
         WHERE $whereSql
         GROUP BY s.status
         ORDER BY count DESC",
        $filterParams
    );

    $difficultySuccess = orgAnalyticsRows(
        $pdo,
        "SELECT p.difficulty, COUNT(s.id) AS submissions,
                SUM(s.status = 'Accepted') AS accepted,
                ROUND(SUM(s.status = 'Accepted') / GREATEST(COUNT(s.id), 1) * 100, 1) AS success_rate
         FROM submissions s
         JOIN users u ON u.id = s.user_id AND COALESCE(u.is_deleted, 0) = 0
         JOIN problems p ON p.id = s.problem_id
         WHERE $whereSql
         GROUP BY p.difficulty
         ORDER BY FIELD(p.difficulty, 'Easy', 'Medium', 'Hard')",
        $filterParams
    );

    $participationDistribution = orgAnalyticsRows(
        $pdo,
        "SELECT u.id, u.username, COUNT(s.id) AS submissions,
                SUM(s.status = 'Accepted') AS accepted,
                SUM(s.status <> 'Accepted') AS wrong
         FROM submissions s
         JOIN users u ON u.id = s.user_id AND COALESCE(u.is_deleted, 0) = 0
         JOIN problems p ON p.id = s.problem_id
         WHERE $whereSql
         GROUP BY u.id, u.username
         ORDER BY submissions DESC
         LIMIT 12",
        $filterParams
    );

    $topPerformers = orgAnalyticsRows(
        $pdo,
        "SELECT u.id, u.username, COUNT(DISTINCT CASE WHEN s.status = 'Accepted' THEN s.problem_id END) AS solved,
                COUNT(s.id) AS submissions,
                ROUND(SUM(s.status = 'Accepted') / GREATEST(COUNT(s.id), 1) * 100, 1) AS acceptance_rate
         FROM submissions s
         JOIN users u ON u.id = s.user_id AND COALESCE(u.is_deleted, 0) = 0
         JOIN problems p ON p.id = s.problem_id
         WHERE $whereSql
         GROUP BY u.id, u.username
         ORDER BY solved DESC, acceptance_rate DESC, submissions ASC
         LIMIT 8",
        $filterParams
    );

    $firstAc = orgAnalyticsRows(
        $pdo,
        "SELECT p.title AS problem_title, u.username, MIN(s.submitted_at) AS first_ac_at
         FROM submissions s
         JOIN users u ON u.id = s.user_id AND COALESCE(u.is_deleted, 0) = 0
         JOIN problems p ON p.id = s.problem_id
         WHERE $whereSql AND s.status = 'Accepted'
         GROUP BY p.id, p.title, u.id, u.username
         ORDER BY first_ac_at ASC
         LIMIT 8",
        $filterParams
    );

    $highestWrong = orgAnalyticsRows(
        $pdo,
        "SELECT u.id, u.username, SUM(s.status <> 'Accepted') AS wrong_submissions, COUNT(s.id) AS submissions
         FROM submissions s
         JOIN users u ON u.id = s.user_id AND COALESCE(u.is_deleted, 0) = 0
         JOIN problems p ON p.id = s.problem_id
         WHERE $whereSql
         GROUP BY u.id, u.username
         HAVING wrong_submissions > 0
         ORDER BY wrong_submissions DESC, submissions DESC
         LIMIT 8",
        $filterParams
    );

    $problemStats = orgAnalyticsRows(
        $pdo,
        "SELECT p.id, p.title, p.difficulty, p.tags,
                COUNT(s.id) AS submissions,
                SUM(s.status = 'Accepted') AS accepted,
                SUM(s.status <> 'Accepted') AS wrong,
                ROUND(SUM(s.status = 'Accepted') / GREATEST(COUNT(s.id), 1) * 100, 1) AS success_rate
         FROM submissions s
         JOIN users u ON u.id = s.user_id AND COALESCE(u.is_deleted, 0) = 0
         JOIN problems p ON p.id = s.problem_id
         WHERE $whereSql
         GROUP BY p.id, p.title, p.difficulty, p.tags
         ORDER BY wrong DESC, success_rate ASC",
        $filterParams
    );

    $peak = orgAnalyticsOne(
        $pdo,
        "SELECT DATE_FORMAT(s.submitted_at, '%Y-%m-%d %H:00') AS hour_label, COUNT(*) AS submissions
         FROM submissions s
         JOIN users u ON u.id = s.user_id AND COALESCE(u.is_deleted, 0) = 0
         JOIN problems p ON p.id = s.problem_id
         WHERE $whereSql
         GROUP BY DATE_FORMAT(s.submitted_at, '%Y-%m-%d %H')
         ORDER BY submissions DESC
         LIMIT 1",
        $filterParams
    );

    $hardest = null;
    $mostFailed = null;
    $weakTags = [];
    foreach ($problemStats as $row) {
        if (!$hardest || (float)$row['success_rate'] < (float)$hardest['success_rate']) $hardest = $row;
        if (!$mostFailed || (int)$row['wrong'] > (int)$mostFailed['wrong']) $mostFailed = $row;
        foreach (orgProblemTags($row['tags'] ?? '') as $tag) {
            $weakTags[$tag] = ($weakTags[$tag] ?? 0) + (int)$row['wrong'];
        }
    }
    arsort($weakTags);

    $submissionParams = [];
    $submissionWhere = submissionFilterWhere($pdo, $orgId, $_GET, $submissionParams);
    $submissionStmt = $pdo->prepare(
        'SELECT s.id, s.user_id, s.contest_id, s.problem_id, s.language, s.status, s.score,
                s.runtime_ms, s.hints_used, s.submitted_at,
                u.username, u.email, p.title AS problem_title, c.title AS contest_title
         FROM submissions s
         JOIN users u ON u.id = s.user_id AND COALESCE(u.is_deleted, 0) = 0
         JOIN problems p ON p.id = s.problem_id
         JOIN contests c ON c.id = s.contest_id AND c.org_id = s.org_id
         WHERE ' . implode(' AND ', $submissionWhere) . '
         ORDER BY s.submitted_at DESC
         LIMIT 300'
    );
    $submissionStmt->execute($submissionParams);

    ok([
        'contest' => [
            'id' => (int)$contest['id'],
            'title' => $contest['title'],
            'status' => $contest['status'],
            'org_status' => $contest['org_status'] ?? null,
            'status_label' => $statusLabel,
            'start_time' => $contest['start_time'],
            'end_time' => $contest['end_time'],
            'analytics_available' => true,
        ],
        'overview' => [
            'total_participants' => (int)($participantOverview['total_participants'] ?? 0),
            'total_submissions' => (int)($overview['total_submissions'] ?? 0),
            'accepted_submissions' => (int)($overview['accepted_submissions'] ?? 0),
            'wrong_submissions' => (int)($overview['wrong_submissions'] ?? 0),
            'acceptance_rate' => (int)($overview['total_submissions'] ?? 0) > 0
                ? round(((int)($overview['accepted_submissions'] ?? 0) / (int)$overview['total_submissions']) * 100, 1)
                : 0,
        ],
        'charts' => [
            'submission_trend' => $trend,
            'verdict_distribution' => $verdictDistribution,
            'difficulty_success' => $difficultySuccess,
            'participation_distribution' => $participationDistribution,
        ],
        'participants' => [
            'top_performers' => $topPerformers,
            'most_active' => $participationDistribution,
            'first_ac' => $firstAc,
            'highest_wrong' => $highestWrong,
        ],
        'insights' => [
            'hardest_problem' => $hardest,
            'most_failed_problem' => $mostFailed,
            'peak_submission_time' => $peak,
            'weak_topic_area' => $weakTags ? ['tag' => array_key_first($weakTags), 'wrong_submissions' => reset($weakTags)] : null,
        ],
        'submissions' => $submissionStmt->fetchAll(),
        'problems' => $problems,
    ]);
}

if ($mode === 'submissions') {
    $params = [];
    $where = submissionFilterWhere($pdo, $orgId, $_GET, $params);
    $stmt = $pdo->prepare(
        'SELECT s.id, s.user_id, s.contest_id, s.problem_id, s.language, s.status, s.score,
                s.runtime_ms, s.hints_used, s.submitted_at,
                u.username, u.email, p.title AS problem_title, c.title AS contest_title
         FROM submissions s
         JOIN users u ON u.id = s.user_id AND COALESCE(u.is_deleted, 0) = 0
         JOIN problems p ON p.id = s.problem_id
         JOIN contests c ON c.id = s.contest_id AND c.org_id = s.org_id
         WHERE ' . implode(' AND ', $where) . '
         ORDER BY s.submitted_at DESC
         LIMIT 300'
    );
    $stmt->execute($params);
    ok(['submissions' => $stmt->fetchAll()]);
}

err('Unknown analytics mode', 400);
