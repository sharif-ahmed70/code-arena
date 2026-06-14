<?php
// GET  /api/discuss/posts.php          → paginated feed
// POST /api/discuss/posts.php          → create post
require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';

$uid = currentUserId();

// ── GET: feed ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $category = $_GET['category'] ?? '';
    $problemId = (int)($_GET['problem_id'] ?? 0);
    $search   = trim($_GET['search'] ?? '');
    $sort     = $_GET['sort'] ?? 'newest';
    $page     = max(1, (int) ($_GET['page'] ?? 1));
    $perPage  = 20;
    $offset   = ($page - 1) * $perPage;

    $where  = [];
    $params = [];

    $validCats = ['General','Career','Contest','Feedback','Interview'];
    if (in_array($category, $validCats)) {
        $where[]  = 'p.category = ?';
        $params[] = $category;
    }
    if ($problemId) {
        $where[] = 'p.problem_id = ?';
        $params[] = $problemId;
    }
    if ($search) {
        $where[]  = '(p.title LIKE ? OR p.tags LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $sortSQL = match($sort) {
        'votes'   => 'p.upvotes DESC, p.created_at DESC',
        'views'   => 'p.views DESC, p.created_at DESC',
        default   => 'p.created_at DESC',
    };

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM discuss_posts p $whereSQL");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    // Pinned always first, then sort
    $listStmt = $pdo->prepare(
        "SELECT p.id, p.title, p.category, p.tags, p.is_pinned, p.is_team_post,
                p.views, p.upvotes, p.downvotes, p.comment_count, p.created_at,
                u.username AS author,
                pr.id AS problem_id, pr.title AS problem_title, pr.slug AS problem_slug
         FROM discuss_posts p
         JOIN users u ON u.id = p.user_id
         LEFT JOIN problems pr ON pr.id = p.problem_id
         $whereSQL
         ORDER BY p.is_pinned DESC, $sortSQL
         LIMIT $perPage OFFSET $offset"
    );
    $listStmt->execute($params);
    $posts = $listStmt->fetchAll();

    // Attach user's vote
    if ($uid && $posts) {
        $ids = array_column($posts, 'id');
        $in  = implode(',', array_fill(0, count($ids), '?'));
        $voteStmt = $pdo->prepare(
            "SELECT target_id, vote FROM discuss_votes
             WHERE user_id = ? AND target_type = 'post' AND target_id IN ($in)"
        );
        $voteStmt->execute(array_merge([$uid], $ids));
        $voteMap = [];
        foreach ($voteStmt->fetchAll() as $v) $voteMap[$v['target_id']] = (int) $v['vote'];
        foreach ($posts as &$p) $p['user_vote'] = $voteMap[$p['id']] ?? 0;
        unset($p);
    } else {
        foreach ($posts as &$p) $p['user_vote'] = 0;
        unset($p);
    }

    ok([
        'posts'    => $posts,
        'total'    => $total,
        'page'     => $page,
        'per_page' => $perPage,
        'pages'    => (int) ceil($total / $perPage),
    ]);
}

// ── POST: create ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$uid) err('Login required', 401);

    $body = jsonBody();
    $title    = trim($body['title']   ?? '');
    $content  = trim($body['content'] ?? '');
    $category = $body['category']     ?? 'General';
    $tags     = trim($body['tags']    ?? '');
    $isTeam   = (bool) ($body['is_team_post'] ?? false);
    $problemId = (int)($body['problem_id'] ?? 0);

    if (!$title)   err('Title is required');
    if (!$content) err('Content is required');

    $validCats = ['General','Career','Contest','Feedback','Interview'];
    if (!in_array($category, $validCats)) $category = 'General';

    if ($problemId) {
        $problemStmt = $pdo->prepare('SELECT id, tags FROM problems WHERE id = ? AND is_public = 1');
        $problemStmt->execute([$problemId]);
        $problem = $problemStmt->fetch();
        if (!$problem) err('Linked problem not found', 404);
    }

    // Clean tags: lowercase, strip extra spaces
    $tagsClean = '';
    if ($tags) {
        $tagArr    = array_filter(array_map('trim', explode(',', $tags)));
        $tagArr    = array_slice($tagArr, 0, 10);
        $tagsClean = implode(',', $tagArr);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO discuss_posts (user_id, problem_id, title, content, category, tags, is_team_post)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$uid, $problemId ?: null, $title, $content, $category, $tagsClean ?: null, $isTeam ? 1 : 0]);
    $postId = (int) $pdo->lastInsertId();

    created(['id' => $postId], 'Post created');
}

err('Method not allowed', 405);
