<?php
// ============================================================
//  CODE ARENA - Dual leaderboard helpers
// ============================================================

function ensurePracticeStats(PDO $pdo, int $userId): array {
    $pdo->prepare(
        'INSERT IGNORE INTO user_practice_stats (user_id) VALUES (?)'
    )->execute([$userId]);

    $stmt = $pdo->prepare('SELECT * FROM user_practice_stats WHERE user_id = ?');
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: [];
}

function practiceStatusCode(string $verdict): string {
    return match ($verdict) {
        'Accepted' => 'AC',
        'Time Limit Exceeded' => 'TLE',
        'Runtime Error', 'Compilation Error' => 'RE',
        default => 'WA',
    };
}

function recordPracticeLeaderboardSubmission(PDO $pdo, int $userId, int $problemId, string $verdict, string $language): void {
    ensurePracticeStats($pdo, $userId);

    $pdo->prepare(
        'INSERT INTO practice_submissions (user_id, problem_id, status, language)
         VALUES (?, ?, ?, ?)'
    )->execute([$userId, $problemId, practiceStatusCode($verdict), $language]);

    $statsStmt = $pdo->prepare(
        "SELECT
            COUNT(*) AS total_submissions,
            SUM(status = 'AC') AS accepted_submissions,
            COUNT(DISTINCT CASE WHEN status = 'AC' THEN problem_id END) AS solved_count
         FROM practice_submissions
         WHERE user_id = ?"
    );
    $statsStmt->execute([$userId]);
    $stats = $statsStmt->fetch();

    $total = max(1, (int) $stats['total_submissions']);
    $accepted = (int) $stats['accepted_submissions'];
    $solved = (int) $stats['solved_count'];
    $accuracy = round(($accepted / $total) * 100, 2);

    $current = ensurePracticeStats($pdo, $userId);
    $today = date('Y-m-d');
    $lastActive = $current['last_active_date'] ?? null;
    $streak = (int) ($current['streak_days'] ?? 0);
    if ($lastActive !== $today) {
        $streak = ($lastActive === date('Y-m-d', strtotime('-1 day'))) ? $streak + 1 : 1;
    }

    $rating = 1200 + ($solved * 10) + (int) floor($accuracy / 5);
    $pdo->prepare(
        'UPDATE user_practice_stats
         SET total_solved = ?, accuracy = ?, streak_days = ?, rating = GREATEST(rating, ?), last_active_date = ?
         WHERE user_id = ?'
    )->execute([$solved, $accuracy, $streak, $rating, $today, $userId]);
}

function finalizeContestLeaderboard(PDO $pdo, array $contest, array $rankedRows): void {
    if (($contest['status'] ?? '') !== 'ended') return;

    $contestId = (int) $contest['id'];
    $historyStmt = $pdo->prepare('SELECT rating_change FROM user_rating_history WHERE contest_id = ? AND user_id = ?');
    $firstFinalizeStmt = $pdo->prepare('SELECT COUNT(*) FROM user_rating_history WHERE contest_id = ?');
    $firstFinalizeStmt->execute([$contestId]);
    $shouldRate = ((int) $firstFinalizeStmt->fetchColumn()) === 0;

    foreach ($rankedRows as $row) {
        $userId = (int) $row['user_id'];
        $rank = (int) $row['rank'];
        $score = (int) $row['score'];
        $penalty = (int) $row['penalty_minutes'];
        $solved = (int) $row['solved_count'];

        $historyStmt->execute([$contestId, $userId]);
        $existingChange = $historyStmt->fetchColumn();
        $ratingChange = $existingChange !== false ? (int) $existingChange : 0;

        if ($shouldRate) {
            $ratingStmt = $pdo->prepare('SELECT contest_rating FROM users WHERE id = ? AND COALESCE(is_deleted, 0) = 0');
            $ratingStmt->execute([$userId]);
            $oldRating = (int) ($ratingStmt->fetchColumn() ?: 1200);
            $ratingChange = $score > 0 ? max(5, 50 - ($rank * 3)) : -5;
            $newRating = max(800, $oldRating + $ratingChange);

            $pdo->prepare(
                'INSERT IGNORE INTO user_rating_history
                    (user_id, contest_id, old_rating, new_rating, rating_change, `rank`)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$userId, $contestId, $oldRating, $newRating, $ratingChange, $rank]);

            $pdo->prepare('UPDATE users SET contest_rating = ? WHERE id = ? AND COALESCE(is_deleted, 0) = 0')
                ->execute([$newRating, $userId]);
        }

        $pdo->prepare(
            'INSERT INTO contest_leaderboard
                (contest_id, user_id, `rank`, score, penalty, solved_count, rating_change)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                `rank` = VALUES(`rank`),
                score = VALUES(score),
                penalty = VALUES(penalty),
                solved_count = VALUES(solved_count),
                rating_change = VALUES(rating_change)'
        )->execute([$contestId, $userId, $rank, $score, $penalty, $solved, $ratingChange]);
    }
}
