<?php
// ============================================================
//  CODE ARENA — Instructor: Problem CRUD
//  GET    → list own problems (admin sees all)
//  POST   → create problem
//  PUT    → update problem   { id, ...fields }
//  DELETE → delete problem   { id }
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';

requireInstructor();

$userId  = currentUserId();
$method  = $_SERVER['REQUEST_METHOD'];

// ── Single problem by id (for edit pre-fill) ─────────────────
if ($method === 'GET' && !empty($_GET['id'])) {
    $id = (int) $_GET['id'];

    // Ownership check for non-admins
    if (!isAdmin()) {
        $ownSt = $pdo->prepare('SELECT created_by FROM problems WHERE id = ?');
        $ownSt->execute([$id]);
        if ($ownSt->fetchColumn() != $userId) err('Forbidden', 403);
    }

    $stmt = $pdo->prepare(
        'SELECT id, title, slug, difficulty, description, examples, constraints,
                test_cases, tags, roadmap_day, hint_tier1, hint_tier2, hint_tier3,
                time_limit_ms, memory_limit_mb, is_public, created_at
         FROM problems WHERE id = ?'
    );
    $stmt->execute([$id]);
    $problem = $stmt->fetch();
    if (!$problem) err('Problem not found', 404);
    ok($problem);
}

// ── List ─────────────────────────────────────────────────────
if ($method === 'GET') {
    $where  = isAdmin() ? '1' : 'created_by = ?';
    $params = isAdmin() ? []  : [$userId];

    $stmt = $pdo->prepare(
        "SELECT id, title, slug, difficulty, tags, roadmap_day,
                total_submissions, total_accepted, is_public, created_at
         FROM problems WHERE $where ORDER BY id DESC"
    );
    $stmt->execute($params);
    ok($stmt->fetchAll());
}

// ── Helpers ───────────────────────────────────────────────────
function slugify(string $title): string {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
    return preg_replace('/-+/', '-', $slug);
}

function uniqueSlug(PDO $pdo, string $base, ?int $excludeId = null): string {
    $slug  = $base;
    $i     = 0;
    do {
        $candidate = $i === 0 ? $slug : "$slug-$i";
        $st = $pdo->prepare(
            'SELECT COUNT(*) FROM problems WHERE slug = ? AND id != ?'
        );
        $st->execute([$candidate, $excludeId ?? 0]);
        if ($st->fetchColumn() == 0) return $candidate;
        $i++;
    } while ($i < 100);
    return $base . '-' . time();
}

function parseTestCases(mixed $raw): string {
    if (is_string($raw)) {
        $arr = json_decode($raw, true);
        return json_encode(is_array($arr) ? $arr : []);
    }
    return json_encode(is_array($raw) ? $raw : []);
}

// ── Create ────────────────────────────────────────────────────
if ($method === 'POST') {
    $b = jsonBody();
    $title = trim($b['title'] ?? '');
    $diff  = $b['difficulty'] ?? '';
    $desc  = trim($b['description'] ?? '');

    if (!$title || !$desc || !in_array($diff, ['Easy','Medium','Hard'])) {
        err('title, description and difficulty are required');
    }

    $slug  = uniqueSlug($pdo, slugify($title));
    $stmt  = $pdo->prepare(
        'INSERT INTO problems
            (title, slug, difficulty, description, examples, constraints, test_cases,
             tags, roadmap_day, hint_tier1, hint_tier2, hint_tier3,
             time_limit_ms, memory_limit_mb, is_public, created_by)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
    );
    $stmt->execute([
        $title, $slug, $diff, $desc,
        $b['examples']    ?? '',
        $b['constraints'] ?? '',
        parseTestCases($b['test_cases'] ?? []),
        $b['tags']        ?? '',
        !empty($b['roadmap_day']) ? (int)$b['roadmap_day'] : null,
        $b['hint_tier1']  ?? null,
        $b['hint_tier2']  ?? null,
        $b['hint_tier3']  ?? null,
        (int) ($b['time_limit_ms']   ?? 2000),
        (int) ($b['memory_limit_mb'] ?? 256),
        isset($b['is_public']) ? (int)(bool)$b['is_public'] : 1,
        $userId,
    ]);
    created(['id' => (int)$pdo->lastInsertId(), 'slug' => $slug], 'Problem created');
}

// ── Update ────────────────────────────────────────────────────
if ($method === 'PUT') {
    $b  = jsonBody();
    $id = (int) ($b['id'] ?? 0);
    if (!$id) err('id required');

    // Ownership check
    if (!isAdmin()) {
        $ownSt = $pdo->prepare('SELECT created_by FROM problems WHERE id = ?');
        $ownSt->execute([$id]);
        $own = $ownSt->fetchColumn();
        if ($own != $userId) err('Forbidden', 403);
    }

    $fields = [];
    $params = [];
    $allowed = ['title','difficulty','description','examples','constraints',
                'tags','roadmap_day','hint_tier1','hint_tier2','hint_tier3',
                'time_limit_ms','memory_limit_mb','is_public'];
    foreach ($allowed as $f) {
        if (array_key_exists($f, $b)) {
            $fields[] = "$f = ?";
            $params[] = $b[$f] === '' ? null : $b[$f];
        }
    }
    if (isset($b['test_cases'])) {
        $fields[] = 'test_cases = ?';
        $params[] = parseTestCases($b['test_cases']);
    }
    if (isset($b['title'])) {
        $newSlug  = uniqueSlug($pdo, slugify($b['title']), $id);
        $fields[] = 'slug = ?';
        $params[] = $newSlug;
    }
    if (!$fields) err('Nothing to update');

    $params[] = $id;
    $pdo->prepare('UPDATE problems SET ' . implode(', ', $fields) . ' WHERE id = ?')
        ->execute($params);
    ok(null, 'Problem updated');
}

// ── Delete ────────────────────────────────────────────────────
if ($method === 'DELETE') {
    $b  = jsonBody();
    $id = (int) ($b['id'] ?? 0);
    if (!$id) err('id required');

    if (!isAdmin()) {
        $ownSt = $pdo->prepare('SELECT created_by FROM problems WHERE id = ?');
        $ownSt->execute([$id]);
        if ($ownSt->fetchColumn() != $userId) err('Forbidden', 403);
    }

    $pdo->prepare('DELETE FROM problems WHERE id = ?')->execute([$id]);
    ok(null, 'Problem deleted');
}

err('Method not allowed', 405);
