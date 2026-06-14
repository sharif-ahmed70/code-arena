<?php
// ============================================================
//  CODE ARENA - Problem Bookmarks
//  GET  ?problem_id=X
//  POST { problem_id } toggles bookmark
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';

requireLogin();

$userId = currentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $problemId = (int) ($_GET['problem_id'] ?? 0);
    if (!$problemId) err('problem_id required');

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM problem_bookmarks WHERE user_id = ? AND problem_id = ?');
    $stmt->execute([$userId, $problemId]);
    ok(['bookmarked' => (bool) $stmt->fetchColumn()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = jsonBody();
    $problemId = (int) ($body['problem_id'] ?? 0);
    if (!$problemId) err('problem_id required');

    $exists = $pdo->prepare('SELECT id FROM problem_bookmarks WHERE user_id = ? AND problem_id = ?');
    $exists->execute([$userId, $problemId]);
    $bookmarkId = $exists->fetchColumn();

    if ($bookmarkId) {
        $pdo->prepare('DELETE FROM problem_bookmarks WHERE id = ?')->execute([$bookmarkId]);
        ok(['bookmarked' => false], 'Bookmark removed');
    }

    $pdo->prepare('INSERT INTO problem_bookmarks (user_id, problem_id) VALUES (?, ?)')
        ->execute([$userId, $problemId]);
    ok(['bookmarked' => true], 'Problem bookmarked');
}

err('Method not allowed', 405);
