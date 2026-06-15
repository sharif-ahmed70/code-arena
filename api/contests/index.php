<?php
// ============================================================
//  CODE ARENA — Contests API
//  GET  /api/contests/index.php        → list contests
//  GET  /api/contests/index.php?slug=x → single contest
//  POST → create (instructor+)
//  POST with ?action=join → register for contest
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';
require_once '../../includes/contest.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ── Sync contest statuses based on current time ──────────────
syncContestStatuses($pdo);

// ── List / single ─────────────────────────────────────────────
if ($method === 'GET') {
    if (!empty($_GET['slug'])) {
        $stmt = $pdo->prepare(
            'SELECT c.*, u.username AS author
             FROM contests c JOIN users u ON u.id = c.created_by AND COALESCE(u.is_deleted, 0) = 0
             WHERE c.slug = ?
               AND COALESCE(c.is_published, 1) = 1
               AND COALESCE(c.visibility, "public") IN ("public", "org")
               AND COALESCE(c.org_status, "scheduled") NOT IN ("draft", "archived")'
        );
        $stmt->execute([trim($_GET['slug'])]);
        $contest = $stmt->fetch();
        if (!$contest) err('Contest not found', 404);

        // Problems
        $pStmt = $pdo->prepare(
            'SELECT p.id, p.title, p.slug, p.difficulty, cp.points, cp.order_index
             FROM contest_problems cp
             JOIN problems p ON p.id = cp.problem_id AND COALESCE(p.is_deleted, 0) = 0
             WHERE cp.contest_id = ?
             ORDER BY cp.order_index'
        );
        $pStmt->execute([$contest['id']]);
        $contest['problems'] = $pStmt->fetchAll();

        // Participant count
        $pcSt = $pdo->prepare('SELECT COUNT(*) FROM contest_participants WHERE contest_id = ?');
        $pcSt->execute([$contest['id']]);
        $contest['participant_count'] = (int) $pcSt->fetchColumn();

        // Is current user registered?
        $contest['registered'] = false;
        if (isLoggedIn()) {
            $regSt = $pdo->prepare(
                'SELECT COUNT(*) FROM contest_participants WHERE contest_id = ? AND user_id = ?'
            );
            $regSt->execute([$contest['id'], currentUserId()]);
            $contest['registered'] = (bool) $regSt->fetchColumn();
        }

        ok($contest);
    }

    $filter = $_GET['status'] ?? '';
    if ($filter === 'live') $filter = 'active';
    $where = [
        'COALESCE(c.is_published, 1) = 1',
        'COALESCE(c.visibility, "public") IN ("public", "org")',
        'COALESCE(c.org_status, "scheduled") NOT IN ("draft", "archived")',
    ];
    $params = [];
    if ($filter && in_array($filter, ['upcoming','active','ended'], true)) {
        $where[] = 'c.status = ?';
        $params[] = $filter;
    }
    $whereSql = 'WHERE ' . implode(' AND ', $where);

    $stmt = $pdo->prepare(
        "SELECT c.id, c.title, c.slug, c.status, c.start_time, c.end_time,
                c.is_rated, u.username AS author,
                (SELECT COUNT(*) FROM contest_participants cp WHERE cp.contest_id = c.id) AS participant_count
         FROM contests c JOIN users u ON u.id = c.created_by AND COALESCE(u.is_deleted, 0) = 0
         $whereSql ORDER BY c.start_time DESC LIMIT 50"
    );
    $stmt->execute($params);
    ok($stmt->fetchAll());
}

// ── Join contest ──────────────────────────────────────────────
if ($method === 'POST' && $action === 'join') {
    requireLogin();
    syncContestStatuses($pdo);
    $body      = jsonBody();
    $contestId = (int) ($body['contest_id'] ?? 0);
    if (!$contestId) err('contest_id required');

    $cSt = $pdo->prepare('SELECT id, status, org_id, org_status, is_published, visibility FROM contests WHERE id = ?');
    $cSt->execute([$contestId]);
    $contest = $cSt->fetch();
    if (!$contest)                      err('Contest not found', 404);
    if (
        !(int)($contest['is_published'] ?? 1)
        || !in_array($contest['visibility'] ?? 'public', ['public', 'org'], true)
        || in_array($contest['org_status'] ?? 'scheduled', ['draft', 'archived'], true)
    ) {
        err('Contest is not open for registration', 403);
    }
    if ($contest['status'] === 'ended') err('Contest has ended');

    try {
        $pdo->prepare(
            'INSERT INTO contest_participants (contest_id, org_id, user_id) VALUES (?, ?, ?)'
        )->execute([$contestId, $contest['org_id'] ?? null, currentUserId()]);
        ok(null, 'Registered for contest');
    } catch (PDOException $e) {
        err('Already registered');
    }
}

// ── Create (instructor+) ──────────────────────────────────────
if ($method === 'POST' && !$action) {
    requireInstructor();
    $b = jsonBody();

    $title = trim($b['title'] ?? '');
    $start = trim($b['start_time'] ?? '');
    $end   = trim($b['end_time']   ?? '');
    if (!$title || !$start || !$end) err('title, start_time and end_time are required');
    [$start, $end, $status] = validateContestWindow($start, $end);

    $base   = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', trim($title, '-')));
    $slug   = $base . '-' . time();
    $creatorOrgId = null;
    if (currentRole() === 'org_admin') {
        $orgStmt = $pdo->prepare('SELECT org_id FROM users WHERE id = ? AND COALESCE(is_deleted, 0) = 0');
        $orgStmt->execute([currentUserId()]);
        $creatorOrgId = $orgStmt->fetchColumn() ?: null;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO contests (org_id, title, slug, description, start_time, end_time, created_by, is_rated, status)
         VALUES (?,?,?,?,?,?,?,?,?)'
    );
    $stmt->execute([
        $creatorOrgId,
        $title,
        $slug,
        $b['description'] ?? '',
        $start,
        $end,
        currentUserId(),
        isset($b['is_rated']) ? (int)(bool)$b['is_rated'] : 1,
        $status,
    ]);
    $contestId = (int)$pdo->lastInsertId();

    $problemIds = [];
    if (!empty($b['problem_ids'])) {
        if (is_array($b['problem_ids'])) {
            $problemIds = array_map('intval', $b['problem_ids']);
        } else {
            $problemIds = array_map('intval', preg_split('/\s*,\s*/', (string)$b['problem_ids'], -1, PREG_SPLIT_NO_EMPTY));
        }
    }
    $problemIds = array_values(array_unique(array_filter($problemIds)));
    if ($problemIds) {
        $check = $pdo->prepare('SELECT id FROM problems WHERE id = ? AND is_public = 1 AND COALESCE(is_deleted, 0) = 0');
        $insProblem = $pdo->prepare(
            'INSERT INTO contest_problems (contest_id, problem_id, points, order_index)
             VALUES (?, ?, ?, ?)'
        );
        foreach ($problemIds as $idx => $problemId) {
            $check->execute([$problemId]);
            if ($check->fetchColumn()) {
                $insProblem->execute([$contestId, $problemId, 100, $idx + 1]);
            }
        }
    }

    created(['id' => $contestId, 'slug' => $slug], 'Contest created');
}

err('Method not allowed', 405);
