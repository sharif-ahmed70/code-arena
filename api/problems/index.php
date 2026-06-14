<?php
// ============================================================
//  CODE ARENA — Problems API
//  GET  /api/problems/index.php            → paginated list
//  GET  /api/problems/index.php?slug=x     → single problem
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') err('Method not allowed', 405);

$userId = currentUserId();

// ── Single problem by slug ───────────────────────────────────
if (!empty($_GET['slug'])) {
    $slug = trim($_GET['slug']);
    $stmt = $pdo->prepare('SELECT id, title, slug, difficulty, description, examples, constraints,
                                  tags, roadmap_day, hint_tier1, hint_tier2, hint_tier3,
                                  total_submissions, total_accepted, time_limit_ms, memory_limit_mb
                           FROM problems WHERE slug = ? AND is_public = 1 AND COALESCE(is_deleted, 0) = 0');
    $stmt->execute([$slug]);
    $problem = $stmt->fetch();
    if (!$problem) err('Problem not found', 404);

    // User-specific status
    $problem['user_status'] = 'unsolved';
    if ($userId) {
        $st = $pdo->prepare('SELECT status FROM submissions
                             WHERE user_id = ? AND problem_id = ?
                             ORDER BY submitted_at DESC LIMIT 1');
        $st->execute([$userId, $problem['id']]);
        $last = $st->fetchColumn();
        if ($last === 'Accepted')      $problem['user_status'] = 'solved';
        elseif ($last)                 $problem['user_status'] = 'attempted';
    }

    // Hints used in session for this problem (affects rating preview)
    $problem['hints_used'] = $_SESSION['hints_' . $problem['id']] ?? 0;

    // Strip hint content — only reveal what the user has unlocked via hint API
    $unlocked = $problem['hints_used'];
    if ($unlocked < 1) { $problem['hint_tier1'] = null; }
    if ($unlocked < 2) { $problem['hint_tier2'] = null; }
    if ($unlocked < 3) { $problem['hint_tier3'] = null; }

    ok($problem);
}

// ── List problems ────────────────────────────────────────────
$difficulty = $_GET['difficulty'] ?? '';
$tag        = $_GET['tag']        ?? '';
$company    = $_GET['company']    ?? '';
$search     = trim($_GET['search'] ?? '');
$savedOnly  = !empty($_GET['saved']);
$sort       = $_GET['sort']       ?? 'id';
$order      = strtoupper($_GET['order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
$page       = max(1, (int) ($_GET['page'] ?? 1));
$perPage    = 20;
$offset     = ($page - 1) * $perPage;

$where  = ['p.is_public = 1', 'COALESCE(p.is_deleted, 0) = 0'];
$params = [];

if (in_array($difficulty, ['Easy', 'Medium', 'Hard'])) {
    $where[]  = 'p.difficulty = ?';
    $params[] = $difficulty;
}
if ($tag) {
    $where[]  = 'FIND_IN_SET(?, p.tags)';
    $params[] = $tag;
}
if ($company) {
    $where[]  = 'FIND_IN_SET(?, p.companies)';
    $params[] = strtolower($company);
}
if ($search) {
    $where[]  = '(p.title LIKE ? OR p.tags LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($_GET['roadmap'] ?? false) {
    $where[]  = 'p.roadmap_day IS NOT NULL';
}
if ($savedOnly) {
    if (!$userId) err('Login required', 401);
    $where[] = 'EXISTS (SELECT 1 FROM problem_bookmarks pb WHERE pb.problem_id = p.id AND pb.user_id = ?)';
    $params[] = $userId;
}

$allowedSort = ['id', 'difficulty', 'title', 'total_accepted', 'total_submissions'];
if (!in_array($sort, $allowedSort)) $sort = 'id';

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM problems p $whereSQL");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

// Difficulty ordering helper
$diffOrder = "CASE p.difficulty WHEN 'Easy' THEN 1 WHEN 'Medium' THEN 2 WHEN 'Hard' THEN 3 ELSE 4 END";
$sortSQL   = $sort === 'difficulty' ? $diffOrder : "p.$sort";

$listStmt = $pdo->prepare(
    "SELECT p.id, p.title, p.slug, p.difficulty, p.tags,
            p.total_submissions, p.total_accepted, p.roadmap_day
     FROM problems p $whereSQL
     ORDER BY $sortSQL $order
     LIMIT $perPage OFFSET $offset"
);
$listStmt->execute($params);
$problems = $listStmt->fetchAll();

// Add user status for each problem
if ($userId && $problems) {
    $ids      = array_column($problems, 'id');
    $in       = implode(',', array_fill(0, count($ids), '?'));
    $subStmt  = $pdo->prepare(
        "SELECT problem_id, MAX(status = 'Accepted') AS accepted,
                COUNT(*) AS attempts
         FROM submissions WHERE user_id = ? AND problem_id IN ($in)
         GROUP BY problem_id"
    );
    $subStmt->execute(array_merge([$userId], $ids));
    $statusMap = [];
    foreach ($subStmt->fetchAll() as $row) {
        $statusMap[$row['problem_id']] = $row['accepted'] ? 'solved' : 'attempted';
    }
    foreach ($problems as &$p) {
        $p['user_status'] = $statusMap[$p['id']] ?? 'unsolved';
    }
    unset($p);

    $bmStmt = $pdo->prepare(
        "SELECT problem_id FROM problem_bookmarks WHERE user_id = ? AND problem_id IN ($in)"
    );
    $bmStmt->execute(array_merge([$userId], $ids));
    $bookmarkMap = array_fill_keys($bmStmt->fetchAll(PDO::FETCH_COLUMN), true);
    foreach ($problems as &$p) {
        $p['bookmarked'] = !empty($bookmarkMap[$p['id']]);
    }
    unset($p);
} else {
    foreach ($problems as &$p) {
        $p['user_status'] = 'unsolved';
        $p['bookmarked'] = false;
    }
    unset($p);
}

ok([
    'problems' => $problems,
    'total'    => $total,
    'page'     => $page,
    'per_page' => $perPage,
    'pages'    => (int) ceil($total / $perPage),
]);
