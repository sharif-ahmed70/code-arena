<?php
// ============================================================
//  Organization Contest Management API
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/organization.php';
require_once '../../includes/response.php';

$org = requireOrganizationApi($pdo);
$orgId = (int)$org['id'];
$method = $_SERVER['REQUEST_METHOD'];

function orgContestPayload(array $body): array {
    $title = cleanString($body['title'] ?? '', 200);
    $description = cleanString($body['description'] ?? '', 5000);
    $start = normalizeContestDateTime((string)($body['start_time'] ?? ''));
    $end = normalizeContestDateTime((string)($body['end_time'] ?? ''));
    $orgStatus = cleanString($body['org_status'] ?? 'scheduled', 20);
    $visibility = cleanString($body['visibility'] ?? 'public', 20);

    if (!$title || !$start || !$end) err('title, start_time and end_time are required');
    if (strtotime($start) >= strtotime($end)) err('Contest end time must be after start time');
    if (!in_array($orgStatus, ['draft', 'scheduled', 'live', 'ended', 'archived'], true)) err('Invalid contest lifecycle status');
    if (!in_array($visibility, ['public', 'org'], true)) err('Invalid contest visibility');

    return [$title, $description, $start, $end, $orgStatus, $visibility];
}

function orgContestSlug(PDO $pdo, string $title): string {
    $base = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $title), '-')) ?: 'contest';
    for ($i = 0; $i < 100; $i++) {
        $slug = $i === 0 ? $base . '-' . time() : $base . '-' . time() . '-' . $i;
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM contests WHERE slug = ?');
        $stmt->execute([$slug]);
        if ((int)$stmt->fetchColumn() === 0) return $slug;
    }
    return $base . '-' . time() . '-' . random_int(1000, 9999);
}

function syncContestProblems(PDO $pdo, int $orgId, int $contestId, array $orgProblemIds, array $platformProblemIds = []): void {
    $pdo->prepare('DELETE FROM contest_problems WHERE contest_id = ?')->execute([$contestId]);
    $check = $pdo->prepare(
        'SELECT id, platform_problem_id
         FROM org_problems
         WHERE id = ? AND org_id = ? AND is_deleted = 0 AND platform_problem_id IS NOT NULL'
    );
    $insert = $pdo->prepare(
        'INSERT INTO contest_problems (contest_id, problem_id, org_problem_id, points, order_index)
         VALUES (?, ?, ?, ?, ?)'
    );
    $orderIndex = 1;
    foreach (array_values(array_unique(array_map('intval', $orgProblemIds))) as $orgProblemId) {
        if ($orgProblemId <= 0) continue;
        $check->execute([$orgProblemId, $orgId]);
        $problem = $check->fetch();
        if ($problem) {
            $insert->execute([$contestId, (int)$problem['platform_problem_id'], $orgProblemId, 100, $orderIndex++]);
        }
    }

    if (!$platformProblemIds) return;
    $platformCheck = $pdo->prepare(
        'SELECT id
         FROM problems
         WHERE id = ? AND is_public = 1 AND COALESCE(is_deleted, 0) = 0'
    );
    foreach (array_values(array_unique(array_map('intval', $platformProblemIds))) as $problemId) {
        if ($problemId <= 0) continue;
        $platformCheck->execute([$problemId]);
        if ($platformCheck->fetch()) {
            $insert->execute([$contestId, $problemId, null, 100, $orderIndex++]);
        }
    }
}

if ($method === 'GET') {
    $contestId = (int)($_GET['contest_id'] ?? 0);
    if ($contestId) {
        $contest = requireOwnedContest($pdo, $orgId, $contestId);
        $pStmt = $pdo->prepare(
            'SELECT cp.id AS contest_problem_id, cp.problem_id, cp.org_problem_id, cp.order_index, cp.points,
                    CASE WHEN cp.org_problem_id IS NULL THEN "platform" ELSE "org" END AS source,
                    COALESCE(op.title, p.title) AS title,
                    COALESCE(op.slug, p.slug) AS slug,
                    COALESCE(op.difficulty, p.difficulty) AS difficulty
             FROM contest_problems cp
             JOIN problems p ON p.id = cp.problem_id
             LEFT JOIN org_problems op ON op.id = cp.org_problem_id AND op.org_id = ?
             WHERE cp.contest_id = ?
             ORDER BY cp.order_index, cp.id'
        );
        $pStmt->execute([$orgId, $contestId]);
        ok(['contest' => $contest, 'problems' => $pStmt->fetchAll()]);
    }

    $stmt = $pdo->prepare(
        'SELECT c.id, c.title, c.slug, c.description, c.start_time, c.end_time, c.status, c.org_status,
                c.is_published, c.visibility, c.created_at,
                COUNT(DISTINCT cp.user_id) AS participant_count,
                COUNT(DISTINCT s.id) AS submission_count
         FROM contests c
         LEFT JOIN contest_participants cp ON cp.contest_id = c.id AND cp.org_id = c.org_id
         LEFT JOIN submissions s ON s.contest_id = c.id AND s.org_id = c.org_id
         WHERE c.org_id = ?
         GROUP BY c.id
         ORDER BY c.start_time DESC'
    );
    $stmt->execute([$orgId]);
    ok(['contests' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $body = jsonBody();
    [$title, $description, $start, $end, $orgStatus, $visibility] = orgContestPayload($body);
    $publicStatus = orgPublicStatusFromLifecycle($orgStatus, $start, $end);
    $isPublished = isset($body['is_published']) ? (int)(bool)$body['is_published'] : ($orgStatus === 'draft' ? 0 : 1);
    if ($orgStatus === 'draft') $isPublished = 0;
    $slug = orgContestSlug($pdo, $title);
    $orgProblemIds = is_array($body['org_problem_ids'] ?? null)
        ? $body['org_problem_ids']
        : (is_array($body['problem_ids'] ?? null) ? $body['problem_ids'] : []);
    $platformProblemIds = is_array($body['platform_problem_ids'] ?? null) ? $body['platform_problem_ids'] : [];
    $selectedProblemCount = count(array_filter(array_map('intval', $orgProblemIds))) + count(array_filter(array_map('intval', $platformProblemIds)));
    if ($isPublished && $orgStatus !== 'draft' && $selectedProblemCount === 0) {
        err('Select at least one problem before publishing a contest');
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO contests
                (org_id, title, slug, description, start_time, end_time, created_by, is_rated, status, org_status, is_published, visibility)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$orgId, $title, $slug, $description, $start, $end, currentUserId(), 1, $publicStatus, $orgStatus, $isPublished, $visibility]);
        $contestId = (int)$pdo->lastInsertId();
        syncContestProblems($pdo, $orgId, $contestId, $orgProblemIds, $platformProblemIds);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('org contest create failed: ' . $e->getMessage());
        err('Contest could not be created', 500);
    }
    created(['id' => $contestId, 'slug' => $slug], 'Contest created');
}

if ($method === 'PUT') {
    $body = jsonBody();
    $contestId = (int)($body['contest_id'] ?? 0);
    if (!$contestId) err('contest_id required');
    $contest = requireOwnedContest($pdo, $orgId, $contestId);
    [$title, $description, $start, $end, $orgStatus, $visibility] = orgContestPayload($body);
    $publicStatus = orgPublicStatusFromLifecycle($orgStatus, $start, $end);
    $isPublished = isset($body['is_published']) ? (int)(bool)$body['is_published'] : (int)$contest['is_published'];
    if ($orgStatus === 'draft') $isPublished = 0;
    $orgProblemIds = is_array($body['org_problem_ids'] ?? null)
        ? $body['org_problem_ids']
        : (is_array($body['problem_ids'] ?? null) ? $body['problem_ids'] : null);
    $platformProblemIds = is_array($body['platform_problem_ids'] ?? null) ? $body['platform_problem_ids'] : null;
    if ($isPublished && $orgStatus !== 'draft' && (is_array($orgProblemIds) || is_array($platformProblemIds))) {
        $selectedProblemCount = (is_array($orgProblemIds) ? count(array_filter(array_map('intval', $orgProblemIds))) : 0)
            + (is_array($platformProblemIds) ? count(array_filter(array_map('intval', $platformProblemIds))) : 0);
        if ($selectedProblemCount === 0) err('Select at least one problem before publishing a contest');
    }
    if ($orgProblemIds === null && $platformProblemIds !== null) {
        $orgProblemIds = [];
    }
    if ($platformProblemIds === null && $orgProblemIds !== null) {
        $platformProblemIds = [];
    }
    if ($isPublished && $orgStatus !== 'draft' && is_array($orgProblemIds) && is_array($platformProblemIds) && count(array_filter(array_map('intval', $orgProblemIds))) + count(array_filter(array_map('intval', $platformProblemIds))) === 0) {
        err('Select at least one problem before publishing a contest');
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'UPDATE contests
             SET title = ?, description = ?, start_time = ?, end_time = ?, status = ?, org_status = ?, is_published = ?, visibility = ?
             WHERE id = ? AND org_id = ?'
        );
        $stmt->execute([$title, $description, $start, $end, $publicStatus, $orgStatus, $isPublished, $visibility, $contestId, $orgId]);
        if ($orgProblemIds !== null) syncContestProblems($pdo, $orgId, $contestId, $orgProblemIds, $platformProblemIds ?? []);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('org contest update failed: ' . $e->getMessage());
        err('Contest could not be updated', 500);
    }
    ok(null, 'Contest updated');
}

if ($method === 'DELETE') {
    $body = jsonBody();
    $contestId = (int)($body['contest_id'] ?? 0);
    if (!$contestId) err('contest_id required');
    $contest = requireOwnedContest($pdo, $orgId, $contestId);
    if (($contest['org_status'] ?? '') !== 'draft') err('Only draft contests can be deleted');
    $pdo->prepare('DELETE FROM contest_problems WHERE contest_id = ?')->execute([$contestId]);
    $pdo->prepare('DELETE FROM contests WHERE id = ? AND org_id = ?')->execute([$contestId, $orgId]);
    ok(null, 'Draft contest deleted');
}

err('Method not allowed', 405);
