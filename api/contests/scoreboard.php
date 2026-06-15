<?php
// ============================================================
//  CODE ARENA - Contest Scoreboard
//  GET /api/contests/scoreboard.php?contest_id=X
//
//  Scoring:
//    - First Accepted per problem earns that problem's points.
//    - Penalty = minutes from contest start to AC + 20 min per failed try
//      before AC on that problem.
//    - Practice submissions are ignored.
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';
require_once '../../includes/contest.php';
require_once '../../includes/leaderboard.php';

methodCheck('GET');
syncContestStatuses($pdo);

$contestId = (int) ($_GET['contest_id'] ?? 0);
if (!$contestId) err('contest_id required');

$contestStmt = $pdo->prepare(
    'SELECT id, title, status, start_time, end_time
     FROM contests WHERE id = ?'
);
$contestStmt->execute([$contestId]);
$contest = $contestStmt->fetch();
if (!$contest) err('Contest not found', 404);

$problemStmt = $pdo->prepare(
    'SELECT cp.problem_id, cp.points, cp.order_index, p.title, p.slug
     FROM contest_problems cp
     JOIN problems p ON p.id = cp.problem_id
     WHERE cp.contest_id = ?
     ORDER BY cp.order_index, cp.id'
);
$problemStmt->execute([$contestId]);
$problems = $problemStmt->fetchAll();

$participantsStmt = $pdo->prepare(
    "SELECT cp.user_id, u.username
     FROM contest_participants cp
     JOIN users u ON u.id = cp.user_id
     WHERE cp.contest_id = ? AND u.role <> 'admin'
       AND COALESCE(cp.status, 'registered') NOT IN ('removed', 'banned', 'rejected')
     ORDER BY cp.registered_at ASC"
);
$participantsStmt->execute([$contestId]);
$participants = $participantsStmt->fetchAll();

$subsStmt = $pdo->prepare(
    'SELECT user_id, problem_id, status, submitted_at
     FROM submissions
     WHERE contest_id = ? AND is_practice = 0
     ORDER BY submitted_at ASC, id ASC'
);
$subsStmt->execute([$contestId]);
$submissions = $subsStmt->fetchAll();

$problemMap = [];
foreach ($problems as $idx => $problem) {
    $problemMap[(int) $problem['problem_id']] = [
        'index' => $idx,
        'points' => (int) $problem['points'],
    ];
}

$rows = [];
foreach ($participants as $participant) {
    $uid = (int) $participant['user_id'];
    $rows[$uid] = [
        'user_id' => $uid,
        'username' => $participant['username'],
        'score' => 0,
        'penalty_minutes' => 0,
        'solved_count' => 0,
        'problems' => array_map(fn() => [
            'status' => 'none',
            'attempts' => 0,
            'failed_attempts' => 0,
            'solved_at_minutes' => null,
            'points' => 0,
        ], $problems),
    ];
}

$startTs = strtotime($contest['start_time']);

foreach ($submissions as $submission) {
    $uid = (int) $submission['user_id'];
    $pid = (int) $submission['problem_id'];
    if (!isset($rows[$uid]) || !isset($problemMap[$pid])) continue;

    $idx = $problemMap[$pid]['index'];
    $cell =& $rows[$uid]['problems'][$idx];
    if ($cell['status'] === 'accepted') {
        unset($cell);
        continue;
    }

    $cell['attempts']++;
    if ($submission['status'] === 'Accepted') {
        $solvedAt = max(0, (int) floor((strtotime($submission['submitted_at']) - $startTs) / 60));
        $points = $problemMap[$pid]['points'];
        $cell['status'] = 'accepted';
        $cell['solved_at_minutes'] = $solvedAt;
        $cell['points'] = $points;

        $rows[$uid]['score'] += $points;
        $rows[$uid]['solved_count']++;
        $rows[$uid]['penalty_minutes'] += $solvedAt + ($cell['failed_attempts'] * 20);
    } else {
        $cell['status'] = 'attempted';
        $cell['failed_attempts']++;
    }
    unset($cell);
}

$ranked = array_values($rows);
usort($ranked, function ($a, $b) {
    return [$b['score'], $a['penalty_minutes'], $b['solved_count'], $a['username']]
        <=> [$a['score'], $b['penalty_minutes'], $a['solved_count'], $b['username']];
});

$rank = 1;
foreach ($ranked as &$row) {
    $row['rank'] = $rank++;
}
unset($row);

finalizeContestLeaderboard($pdo, $contest, $ranked);

ok([
    'contest' => [
        'id' => (int) $contest['id'],
        'title' => $contest['title'],
        'status' => $contest['status'],
        'start_time' => $contest['start_time'],
        'end_time' => $contest['end_time'],
    ],
    'problems' => array_map(fn($problem) => [
        'problem_id' => (int) $problem['problem_id'],
        'title' => $problem['title'],
        'slug' => $problem['slug'],
        'points' => (int) $problem['points'],
        'order_index' => (int) $problem['order_index'],
    ], $problems),
    'rows' => $ranked,
]);
