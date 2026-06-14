<?php
// POST /api/discuss/comment.php  → add comment
// DELETE /api/discuss/comment.php?id=X → delete (admin/own)
require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';

$uid = currentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$uid) err('Login required', 401);

    $body     = jsonBody();
    $postId   = (int) ($body['post_id']   ?? 0);
    $content  = trim($body['content']     ?? '');
    $parentId = ($body['parent_id'] ?? null) ? (int) $body['parent_id'] : null;

    if (!$postId)   err('post_id required');
    if (!$content)  err('Content required');

    // Verify post exists
    $chk = $pdo->prepare('SELECT id FROM discuss_posts WHERE id=?');
    $chk->execute([$postId]);
    if (!$chk->fetch()) err('Post not found', 404);

    $stmt = $pdo->prepare(
        'INSERT INTO discuss_comments (post_id, user_id, parent_id, content) VALUES (?,?,?,?)'
    );
    $stmt->execute([$postId, $uid, $parentId, $content]);
    $commentId = (int) $pdo->lastInsertId();

    // Keep comment_count in sync
    $pdo->prepare('UPDATE discuss_posts SET comment_count = comment_count + 1 WHERE id=?')
        ->execute([$postId]);

    // Fetch full comment for return
    $get = $pdo->prepare(
        'SELECT c.id, c.parent_id, c.content, c.upvotes, c.downvotes, c.created_at,
                u.username AS author, u.id AS author_id
         FROM discuss_comments c JOIN users u ON u.id=c.user_id WHERE c.id=?'
    );
    $get->execute([$commentId]);
    $comment = $get->fetch();
    $comment['user_vote'] = 0;

    created($comment, 'Comment added');
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if (!$uid) err('Login required', 401);
    $cid = (int) ($_GET['id'] ?? 0);
    if (!$cid) err('Comment ID required');

    $row = $pdo->prepare('SELECT user_id, post_id FROM discuss_comments WHERE id=?');
    $row->execute([$cid]);
    $c = $row->fetch();
    if (!$c) err('Comment not found', 404);
    if ($c['user_id'] !== $uid && !isAdmin()) err('Forbidden', 403);

    $pdo->prepare('DELETE FROM discuss_comments WHERE id=?')->execute([$cid]);
    $pdo->prepare('UPDATE discuss_posts SET comment_count = GREATEST(comment_count-1,0) WHERE id=?')
        ->execute([$c['post_id']]);

    ok(null, 'Comment deleted');
}

err('Method not allowed', 405);
