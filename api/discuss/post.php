<?php
// GET    /api/discuss/post.php?id=X[&sort=best|newest]  → post + comments
// DELETE /api/discuss/post.php?id=X                     → delete (admin/own)
require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';

$uid = currentUserId();
$id  = (int) ($_GET['id'] ?? 0);
if (!$id) err('Post ID required');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Increment view count
    $pdo->prepare('UPDATE discuss_posts SET views = views + 1 WHERE id = ?')->execute([$id]);

    $stmt = $pdo->prepare(
        'SELECT p.id, p.title, p.content, p.category, p.tags, p.is_pinned, p.is_team_post,
                p.views, p.upvotes, p.downvotes, p.comment_count, p.created_at, p.updated_at,
                u.username AS author, u.id AS author_id,
                pr.id AS problem_id, pr.title AS problem_title, pr.slug AS problem_slug,
                pr.difficulty AS problem_difficulty
         FROM discuss_posts p
         JOIN users u ON u.id = p.user_id
         LEFT JOIN problems pr ON pr.id = p.problem_id
         WHERE p.id = ?'
    );
    $stmt->execute([$id]);
    $post = $stmt->fetch();
    if (!$post) err('Post not found', 404);

    // User's vote on post
    $post['user_vote'] = 0;
    if ($uid) {
        $v = $pdo->prepare("SELECT vote FROM discuss_votes WHERE user_id=? AND target_type='post' AND target_id=?");
        $v->execute([$uid, $id]);
        $post['user_vote'] = (int) ($v->fetchColumn() ?: 0);
    }

    // Comments
    $sort    = $_GET['sort'] ?? 'best';
    $sortSQL = $sort === 'newest' ? 'c.created_at DESC' : '(c.upvotes - c.downvotes) DESC, c.created_at ASC';

    $cStmt = $pdo->prepare(
        "SELECT c.id, c.parent_id, c.content, c.upvotes, c.downvotes, c.created_at,
                u.username AS author, u.id AS author_id
         FROM discuss_comments c
         JOIN users u ON u.id = c.user_id
         WHERE c.post_id = ?
         ORDER BY $sortSQL"
    );
    $cStmt->execute([$id]);
    $comments = $cStmt->fetchAll();

    // User's votes on comments
    if ($uid && $comments) {
        $cids = array_column($comments, 'id');
        $in   = implode(',', array_fill(0, count($cids), '?'));
        $cvStmt = $pdo->prepare(
            "SELECT target_id, vote FROM discuss_votes
             WHERE user_id=? AND target_type='comment' AND target_id IN ($in)"
        );
        $cvStmt->execute(array_merge([$uid], $cids));
        $cvMap = [];
        foreach ($cvStmt->fetchAll() as $v) $cvMap[$v['target_id']] = (int) $v['vote'];
        foreach ($comments as &$c) $c['user_vote'] = $cvMap[$c['id']] ?? 0;
        unset($c);
    } else {
        foreach ($comments as &$c) $c['user_vote'] = 0;
        unset($c);
    }

    ok(['post' => $post, 'comments' => $comments]);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if (!$uid) err('Login required', 401);
    $post = $pdo->prepare('SELECT user_id FROM discuss_posts WHERE id=?');
    $post->execute([$id]);
    $row = $post->fetch();
    if (!$row) err('Post not found', 404);
    if ($row['user_id'] !== $uid && !isAdmin()) err('Forbidden', 403);
    $pdo->prepare('DELETE FROM discuss_posts WHERE id=?')->execute([$id]);
    ok(null, 'Post deleted');
}

err('Method not allowed', 405);
