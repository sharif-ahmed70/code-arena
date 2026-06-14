<?php
// seed_roadmap.php — Run once: php seed_roadmap.php
// Assigns 3 problems per day across 30 days.
require_once __DIR__ . '/config/db.php';

// ── 30-day curriculum ────────────────────────────────────────
// Format: day => [problem_id, problem_id, problem_id]
//
// Day  1-3  : Arrays Basics
// Day  4-6  : Strings
// Day  7-9  : Hashing / Hash Maps
// Day 10-12 : Two Pointers & Sliding Window
// Day 13-15 : Stacks / Queues / Heap
// Day 16-19 : Trees & Backtracking
// Day 20-22 : Graphs
// Day 23-25 : Dynamic Programming
// Day 26-28 : Binary Search & Sorting
// Day 29-30 : Mixed Hard

$roadmap = [
     1 => [1,  6,  9],   // Hello World, Sum of Array, Find Maximum
     2 => [16, 23, 24],  // Contains Duplicate, Move Zeroes, Plus One
     3 => [20, 36, 39],  // Maximum Subarray, Merge Sorted Array, Squares of Sorted Array

     4 => [7,  29, 41],  // Reverse a String, Length of Last Word, Valid Palindrome
     5 => [17, 33, 34],  // Valid Anagram, Ransom Note, First Unique Character
     6 => [35, 40, 43],  // Longest Common Prefix, Add Binary, Count and Say

     7 => [11, 25, 30],  // Two Sum, Happy Number, Majority Element
     8 => [18, 19, 28],  // Missing Number, Single Number, Roman to Integer
     9 => [32, 49, 70],  // Intersection of Two Arrays, Group Anagrams, Top K Frequent

    10 => [10, 12, 38],  // Palindrome Check, Remove Duplicates, Remove Element
    11 => [46, 47, 50],  // Container With Most Water, 3Sum, Merge Intervals
    12 => [21, 45, 51],  // Best Time to Buy/Sell, Longest Substr No Repeat, Product Except Self

    13 => [14, 44, 90],  // Valid Parentheses, Min Stack, Longest Valid Parentheses
    14 => [82, 85, 86],  // Trapping Rain Water, Sliding Window Max, Largest Rectangle
    15 => [71, 77, 81],  // Kth Largest, K Closest Points, Task Scheduler

    16 => [78, 79, 80],  // Max Depth Binary Tree, Validate BST, Level Order Traversal
    17 => [62, 63, 64],  // Combination Sum, Permutations, Subsets
    18 => [61, 72, 83],  // Word Break, Letter Combinations, N-Queens
    19 => [65, 66, 67],  // Spiral Matrix, Set Matrix Zeroes, Rotate Image

    20 => [68, 69, 88],  // Number of Islands, Course Schedule, Word Ladder
    21 => [58, 60, 95],  // Jump Game, Decode Ways, Longest Increasing Path in Matrix
    22 => [84, 87, 94],  // Minimum Window Substring, Regex Matching, Wildcard Matching

    23 => [13, 22, 57],  // Fibonacci, Climbing Stairs, House Robber
    24 => [55, 56, 59],  // Coin Change, LIS, Unique Paths
    25 => [73, 74, 76],  // LCS, Edit Distance, Partition Equal Subset Sum

    26 => [15, 52, 53],  // Binary Search, Find Min Rotated, Search Rotated
    27 => [37, 48, 54],  // Search Insert Position, Longest Palindromic Substr, Max Product Subarray
    28 => [75, 89, 98],  // Minimum Path Sum, Median of Two Sorted Arrays, Russian Doll Envelopes

    29 => [91, 92, 93],  // Jump Game II, Gas Station, Candy
    30 => [27, 96, 97],  // Power of Two, Burst Balloons, Min Refueling Stops
];

// Wipe all existing assignments
$pdo->exec('UPDATE problems SET roadmap_day = NULL');
echo "Cleared existing roadmap_day assignments.\n";

// Apply new assignments
$stmt = $pdo->prepare('UPDATE problems SET roadmap_day = ? WHERE id = ?');
$assigned = 0;
$missing  = [];

foreach ($roadmap as $day => $ids) {
    foreach ($ids as $pid) {
        $stmt->execute([$day, $pid]);
        if ($stmt->rowCount() > 0) {
            $assigned++;
        } else {
            $missing[] = "Day $day → problem id=$pid not found";
        }
    }
}

echo "Assigned $assigned problems across 30 days.\n";
if ($missing) {
    echo "Warnings:\n" . implode("\n", $missing) . "\n";
}

// Verify
$check = $pdo->query('SELECT roadmap_day, COUNT(*) as cnt FROM problems WHERE roadmap_day IS NOT NULL GROUP BY roadmap_day ORDER BY roadmap_day');
echo "\nDay → problems count:\n";
foreach ($check as $r) {
    echo "  Day {$r['roadmap_day']}: {$r['cnt']} problem(s)\n";
}
