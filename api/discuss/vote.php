<?php
// POST /api/discuss/vote.php
// Body: { target_type: 'post'|'comment', target_id: int, vote: 1|-1 }
// Toggles: voting same direction removes vote; opposite direction changes it.
require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';

methodCheck('POST');
$uid = currentUserId();
if (!$uid) err('Login required', 401);

$body       = jsonBody();
$targetType = $body['target_type'] ?? '';
$targetId   = (int) ($body['target_id'] ?? 0);
$vote       = (int) ($body['vote'] ?? 0);

if (!in_array($targetType, ['post', 'comment'])) err('Invalid target_type');
if (!$targetId)                                   err('target_id required');
if (!in_array($vote, [1, -1]))                    err('vote must be 1 or -1');

// Look up existing vote
$existing = $pdo->prepare(
    'SELECT vote FROM discuss_votes WHERE user_id=? AND target_type=? AND target_id=?'
);
$existing->execute([$uid, $targetType, $targetId]);
$current = $existing->fetchColumn();

$table  = $targetType === 'post' ? 'discuss_posts' : 'discuss_comments';
$newVote = 0; // net new vote stored

if ($current === false) {
    // No prior vote — insert
    $pdo->prepare(
        'INSERT INTO discuss_votes (user_id, target_type, target_id, vote) VALUES (?,?,?,?)'
    )->execute([$uid, $targetType, $targetId, $vote]);
    $col = $vote === 1 ? 'upvotes' : 'downvotes';
    $pdo->prepare("UPDATE $table SET $col = $col + 1 WHERE id=?")->execute([$targetId]);
    $newVote = $vote;
} elseif ((int) $current === $vote) {
    // Same direction — toggle off
    $pdo->prepare(
        'DELETE FROM discuss_votes WHERE user_id=? AND target_type=? AND target_id=?'
    )->execute([$uid, $targetType, $targetId]);
    $col = $vote === 1 ? 'upvotes' : 'downvotes';
    $pdo->prepare("UPDATE $table SET $col = GREATEST($col - 1, 0) WHERE id=?")->execute([$targetId]);
    $newVote = 0;
} else {
    // Opposite direction — change vote
    $pdo->prepare(
        'UPDATE discuss_votes SET vote=? WHERE user_id=? AND target_type=? AND target_id=?'
    )->execute([$vote, $uid, $targetType, $targetId]);
    // Remove old, add new
    $oldCol = $vote === 1 ? 'downvotes' : 'upvotes';
    $newCol = $vote === 1 ? 'upvotes'   : 'downvotes';
    $pdo->prepare("UPDATE $table SET $oldCol = GREATEST($oldCol - 1, 0), $newCol = $newCol + 1 WHERE id=?")
        ->execute([$targetId]);
    $newVote = $vote;
}

// Return fresh counts
$counts = $pdo->prepare("SELECT upvotes, downvotes FROM $table WHERE id=?");
$counts->execute([$targetId]);
$row = $counts->fetch();

ok(['user_vote' => $newVote, 'upvotes' => (int)$row['upvotes'], 'downvotes' => (int)$row['downvotes']]);
