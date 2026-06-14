<?php
// ============================================================
//  CODE ARENA - Admin Problem Management API
//  GET    /api/admin/problems.php[?id=]
//  POST   /api/admin/problems.php
//  PUT    /api/admin/problems.php
//  DELETE /api/admin/problems.php
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/adminAuthMiddleware.php';
require_once '../../includes/adminActivityLogger.php';

requireAdminApi();

$method = $_SERVER['REQUEST_METHOD'];

function adminSlugify(string $title): string {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
    return preg_replace('/-+/', '-', $slug);
}

function adminUniqueSlug(PDO $pdo, string $base, ?int $excludeId = null): string {
    $slug = $base ?: 'problem';
    for ($i = 0; $i < 100; $i++) {
        $candidate = $i === 0 ? $slug : "$slug-$i";
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM problems WHERE slug = ? AND id != ? AND COALESCE(is_deleted, 0) = 0');
        $stmt->execute([$candidate, $excludeId ?? 0]);
        if ((int)$stmt->fetchColumn() === 0) return $candidate;
    }
    return $slug . '-' . time();
}

function adminParseTestCases(mixed $raw): string {
    $cases = is_string($raw) ? json_decode($raw, true) : $raw;
    if (!is_array($cases)) err('test_cases must be an array');
    if (count($cases) === 0) err('At least one test case is required');
    if (count($cases) > 50) err('Maximum 50 test cases allowed');

    $clean = [];
    foreach ($cases as $case) {
        if (!is_array($case)) err('Invalid test case format');
        $input = (string)($case['input'] ?? '');
        $output = (string)($case['expected_output'] ?? $case['output'] ?? '');
        if (strlen($input) > 8192 || strlen($output) > 8192) err('Test case input/output too large');
        $clean[] = ['input' => $input, 'expected_output' => $output];
    }
    return json_encode($clean, JSON_UNESCAPED_SLASHES);
}

function adminProblemPayload(): array {
    $b = jsonBody();
    $title = cleanString($b['title'] ?? '', 200);
    $difficulty = cleanString($b['difficulty'] ?? '', 20);
    $description = cleanString($b['description'] ?? '', 50000);

    if (!$title || !$description || !in_array($difficulty, ['Easy', 'Medium', 'Hard'], true)) {
        err('title, description and valid difficulty are required');
    }

    $roadmapDay = !empty($b['roadmap_day']) ? (int)$b['roadmap_day'] : null;
    if ($roadmapDay !== null && ($roadmapDay < 1 || $roadmapDay > 365)) err('Invalid roadmap day');

    return [
        'title' => $title,
        'difficulty' => $difficulty,
        'description' => $description,
        'examples' => cleanString($b['examples'] ?? '', 20000),
        'constraints' => cleanString($b['constraints'] ?? '', 10000),
        'test_cases' => adminParseTestCases($b['test_cases'] ?? []),
        'tags' => cleanString($b['tags'] ?? '', 255),
        'roadmap_day' => $roadmapDay,
        'hint_tier1' => cleanString($b['hint_tier1'] ?? '', 5000) ?: null,
        'hint_tier2' => cleanString($b['hint_tier2'] ?? '', 5000) ?: null,
        'hint_tier3' => cleanString($b['hint_tier3'] ?? '', 5000) ?: null,
        'time_limit_ms' => max(500, min(10000, (int)($b['time_limit_ms'] ?? 2000))),
        'memory_limit_mb' => max(64, min(1024, (int)($b['memory_limit_mb'] ?? 256))),
        'is_public' => isset($b['is_public']) ? (int)(bool)$b['is_public'] : 1,
    ];
}

if ($method === 'GET' && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare(
        'SELECT id, title, slug, difficulty, description, examples, constraints,
                test_cases, tags, roadmap_day, hint_tier1, hint_tier2, hint_tier3,
                time_limit_ms, memory_limit_mb, is_public, created_at
         FROM problems WHERE id = ? AND COALESCE(is_deleted, 0) = 0'
    );
    $stmt->execute([$id]);
    $problem = $stmt->fetch();
    if (!$problem) err('Problem not found', 404);
    ok($problem);
}

if ($method === 'GET') {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(100, max(10, (int)($_GET['per_page'] ?? 30)));
    $offset = ($page - 1) * $perPage;
    $search = cleanString($_GET['search'] ?? ($_GET['title'] ?? ''), 100);
    $difficulty = cleanString($_GET['difficulty'] ?? '', 20);
    $tags = cleanString($_GET['tags'] ?? '', 100);

    $where = [];
    $params = [];
    $where[] = 'COALESCE(is_deleted, 0) = 0';
    if ($search) {
        $where[] = '(title LIKE ? OR slug LIKE ? OR tags LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if (in_array($difficulty, ['Easy', 'Medium', 'Hard'], true)) {
        $where[] = 'difficulty = ?';
        $params[] = $difficulty;
    }
    if ($difficulty && !in_array($difficulty, ['Easy', 'Medium', 'Hard'], true)) {
        err('Invalid difficulty filter');
    }
    if ($tags) {
        $where[] = 'tags LIKE ?';
        $params[] = "%$tags%";
    }
    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $count = $pdo->prepare("SELECT COUNT(*) FROM problems $whereSQL");
    $count->execute($params);
    $total = (int)$count->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT id, title, slug, difficulty, tags, roadmap_day, total_submissions,
                total_accepted, is_public, created_at
         FROM problems
         $whereSQL
         ORDER BY id DESC
         LIMIT $perPage OFFSET $offset"
    );
    $stmt->execute($params);

    ok([
        'problems' => $stmt->fetchAll(),
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'pages' => (int)ceil($total / $perPage),
    ]);
}

if ($method === 'POST') {
    $p = adminProblemPayload();
    $slug = adminUniqueSlug($pdo, adminSlugify($p['title']));
    $stmt = $pdo->prepare(
        'INSERT INTO problems
            (title, slug, difficulty, description, examples, constraints, test_cases,
             tags, roadmap_day, hint_tier1, hint_tier2, hint_tier3,
             time_limit_ms, memory_limit_mb, is_public, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $p['title'], $slug, $p['difficulty'], $p['description'], $p['examples'],
        $p['constraints'], $p['test_cases'], $p['tags'], $p['roadmap_day'],
        $p['hint_tier1'], $p['hint_tier2'], $p['hint_tier3'], $p['time_limit_ms'],
        $p['memory_limit_mb'], $p['is_public'], currentUserId(),
    ]);
    $id = (int)$pdo->lastInsertId();
    logAdminAction(currentUserId(), 'ADD_PROBLEM', 'problem', $id, 'Created problem: ' . $p['title']);
    appLog($pdo, 'admin_problem_created', ['problem_id' => $id]);
    created(['id' => $id, 'slug' => $slug], 'Problem created');
}

if ($method === 'PUT') {
    $b = jsonBody();
    $id = (int)($b['id'] ?? 0);
    if (!$id) err('id required');
    $p = adminProblemPayload();
    $slug = adminUniqueSlug($pdo, adminSlugify($p['title']), $id);

    $stmt = $pdo->prepare(
        'UPDATE problems
         SET title = ?, slug = ?, difficulty = ?, description = ?, examples = ?,
             constraints = ?, test_cases = ?, tags = ?, roadmap_day = ?,
             hint_tier1 = ?, hint_tier2 = ?, hint_tier3 = ?, time_limit_ms = ?,
             memory_limit_mb = ?, is_public = ?
         WHERE id = ?'
    );
    $stmt->execute([
        $p['title'], $slug, $p['difficulty'], $p['description'], $p['examples'],
        $p['constraints'], $p['test_cases'], $p['tags'], $p['roadmap_day'],
        $p['hint_tier1'], $p['hint_tier2'], $p['hint_tier3'], $p['time_limit_ms'],
        $p['memory_limit_mb'], $p['is_public'], $id,
    ]);
    logAdminAction(currentUserId(), 'UPDATE_PROBLEM', 'problem', $id, 'Updated problem: ' . $p['title']);
    appLog($pdo, 'admin_problem_updated', ['problem_id' => $id]);
    ok(['id' => $id, 'slug' => $slug], 'Problem updated');
}

if ($method === 'DELETE') {
    $body = jsonBody();
    $id = (int)($body['id'] ?? 0);
    if (!$id) err('id required');
    $titleStmt = $pdo->prepare('SELECT title FROM problems WHERE id = ? AND COALESCE(is_deleted, 0) = 0');
    $titleStmt->execute([$id]);
    $title = $titleStmt->fetchColumn();
    if (!$title) err('Problem not found', 404);
    $stmt = $pdo->prepare('UPDATE problems SET is_deleted = 1, is_public = 0 WHERE id = ?');
    $stmt->execute([$id]);
    logAdminAction(currentUserId(), 'SOFT_DELETE_PROBLEM', 'problem', $id, 'Soft deleted problem: ' . $title);
    appLog($pdo, 'admin_problem_deleted', ['problem_id' => $id]);
    ok(null, 'Problem deleted');
}

err('Method not allowed', 405);
