<?php
// ============================================================
//  CODE ARENA — Roadmap Progress
//  GET /api/roadmap/progress.php   → all 30 days with status
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';

methodCheck('GET');
requireLogin();
if (isAdmin()) err('Admins do not have roadmap access', 403);

$userId = currentUserId();

// User's current unlocked day
$userStmt = $pdo->prepare('SELECT roadmap_day FROM users WHERE id = ?');
$userStmt->execute([$userId]);
$unlockedDay = (int) ($userStmt->fetchColumn() ?: 1);

// Completed days
$doneStmt = $pdo->prepare('SELECT day FROM roadmap_progress WHERE user_id = ?');
$doneStmt->execute([$userId]);
$completedDays = array_column($doneStmt->fetchAll(), 'day');

// All roadmap problems grouped by day
$probStmt = $pdo->prepare(
    'SELECT id, title, slug, difficulty, roadmap_day,
            (SELECT COUNT(*) FROM submissions s
             WHERE s.user_id = ? AND s.problem_id = p.id AND s.status = "Accepted") AS solved
     FROM problems p
     WHERE roadmap_day IS NOT NULL AND is_public = 1
     ORDER BY roadmap_day, id'
);
$probStmt->execute([$userId]);
$allProblems = $probStmt->fetchAll();

// Day topic titles & descriptions
$dayMeta = [
     1 => ['topic' => 'Arrays Basics',          'desc' => 'Iteration, max/min, basic traversal'],
     2 => ['topic' => 'Array Manipulation',      'desc' => 'In-place edits, shifting, counters'],
     3 => ['topic' => 'Array Classics',          'desc' => "Kadane's, merging, transformations"],
     4 => ['topic' => 'String Basics',           'desc' => 'Reversal, palindromes, word parsing'],
     5 => ['topic' => 'String Matching',         'desc' => 'Anagram detection, character maps'],
     6 => ['topic' => 'String Operations',       'desc' => 'Prefix search, binary strings, encoding'],
     7 => ['topic' => 'Hash Map Intro',          'desc' => 'Two Sum pattern, frequency counting'],
     8 => ['topic' => 'Hash Map Patterns',       'desc' => 'XOR tricks, set membership, symbol maps'],
     9 => ['topic' => 'Hash Map Medium',         'desc' => 'Grouping, top-K, set intersection'],
    10 => ['topic' => 'Two Pointers Intro',      'desc' => 'Opposite ends, deduplication, insertion'],
    11 => ['topic' => 'Two Pointers Classic',    'desc' => 'k-sum, interval merging, water trap'],
    12 => ['topic' => 'Sliding Window',          'desc' => 'Variable-width windows, prefix products'],
    13 => ['topic' => 'Stack Fundamentals',      'desc' => 'Bracket matching, monotone stacks'],
    14 => ['topic' => 'Stack Hard',              'desc' => 'Histogram, rain water, sliding max'],
    15 => ['topic' => 'Heap / Priority Queue',   'desc' => 'Top-K elements, scheduling, clusters'],
    16 => ['topic' => 'Binary Trees',            'desc' => 'BFS, DFS, BST validation'],
    17 => ['topic' => 'Backtracking Intro',      'desc' => 'Combinations, permutations, subsets'],
    18 => ['topic' => 'Backtracking Advanced',   'desc' => 'Word break, phone digits, N-Queens'],
    19 => ['topic' => 'Matrix Traversal',        'desc' => 'Spiral, in-place zeroing, rotation'],
    20 => ['topic' => 'Graph BFS / DFS',         'desc' => 'Islands, topology sort, word ladder'],
    21 => ['topic' => 'Graph + Greedy',          'desc' => 'Jump game, path decoding, matrix paths'],
    22 => ['topic' => 'Pattern Matching',        'desc' => 'Sliding window hard, wildcards, regex'],
    23 => ['topic' => 'DP Intro',                'desc' => 'Memoization, 1-D DP, house robber'],
    24 => ['topic' => 'Classic DP',              'desc' => 'Coin change, LIS, grid paths'],
    25 => ['topic' => '2-D Dynamic Programming', 'desc' => 'LCS, edit distance, knapsack'],
    26 => ['topic' => 'Binary Search',           'desc' => 'Classic search, rotated arrays'],
    27 => ['topic' => 'Search + Sort Mixed',     'desc' => 'Insertion point, palindromes, max product'],
    28 => ['topic' => 'Hard Search',             'desc' => 'Median of arrays, DP + binary search'],
    29 => ['topic' => 'Hard Greedy',             'desc' => 'Jump games, gas station, candy'],
    30 => ['topic' => 'Final Challenge',         'desc' => 'Bit tricks, interval DP, hard DP + heap'],
];

// Build 30-day structure
$days = [];
for ($d = 1; $d <= 30; $d++) {
    $dayProblems = array_values(array_filter($allProblems, fn($p) => (int)$p['roadmap_day'] === $d));
    $totalInDay  = count($dayProblems);
    $solvedInDay = array_sum(array_column($dayProblems, 'solved'));

    $status = 'locked';
    if ($d < $unlockedDay)                            $status = 'completed';
    elseif ($d === $unlockedDay)                      $status = 'active';
    elseif (in_array($d, $completedDays))             $status = 'completed';

    $days[] = [
        'day'      => $d,
        'topic'    => $dayMeta[$d]['topic'] ?? "Day $d",
        'desc'     => $dayMeta[$d]['desc']  ?? '',
        'status'   => $status,
        'problems' => $dayProblems,
        'total'    => $totalInDay,
        'solved'   => (int) $solvedInDay,
    ];
}

ok([
    'unlocked_day'    => $unlockedDay,
    'completed_days'  => $completedDays,
    'days'            => $days,
]);
