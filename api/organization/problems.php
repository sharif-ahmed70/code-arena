<?php
// ============================================================
//  Organization Problem Bank API
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/organization.php';
require_once '../../includes/response.php';

$org = requireOrganizationApi($pdo);
$orgId = (int)$org['id'];
$method = $_SERVER['REQUEST_METHOD'];

function orgProblemDifficulty(string $difficulty): string {
    $value = strtolower(trim($difficulty));
    return match ($value) {
        'medium' => 'Medium',
        'hard' => 'Hard',
        default => 'Easy',
    };
}

function orgProblemSlug(string $title): string {
    return strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $title), '-')) ?: 'problem';
}

function orgProblemExamples(array $body): string {
    $sampleInput = (string)($body['sample_input'] ?? '');
    $sampleOutput = (string)($body['sample_output'] ?? '');
    if ($sampleInput === '' && $sampleOutput === '') return '';
    return json_encode([[
        'input' => $sampleInput,
        'output' => $sampleOutput,
    ]], JSON_UNESCAPED_UNICODE);
}

function orgProblemPayload(array $body): array {
    $title = cleanString($body['title'] ?? '', 200);
    $description = cleanString($body['description'] ?? '', 20000);
    if (!$title || !$description) err('title and description are required');

    $testCases = trim((string)($body['test_cases'] ?? '[]'));
    $decoded = json_decode($testCases, true);
    if ($testCases !== '' && !is_array($decoded)) err('test_cases must be valid JSON');

    return [
        'title' => $title,
        'description' => $description,
        'difficulty' => orgProblemDifficulty((string)($body['difficulty'] ?? 'Easy')),
        'tags' => cleanString($body['tags'] ?? '', 255),
        'input_format' => cleanString($body['input_format'] ?? '', 5000),
        'output_format' => cleanString($body['output_format'] ?? '', 5000),
        'constraints' => cleanString($body['constraints'] ?? '', 5000),
        'sample_input' => (string)($body['sample_input'] ?? ''),
        'sample_output' => (string)($body['sample_output'] ?? ''),
        'test_cases' => $testCases ?: '[]',
        'hint_tier1' => cleanString($body['hint_tier1'] ?? '', 5000),
        'hint_tier2' => cleanString($body['hint_tier2'] ?? '', 5000),
        'hint_tier3' => cleanString($body['hint_tier3'] ?? '', 5000),
        'time_limit_ms' => max(500, min(10000, (int)($body['time_limit_ms'] ?? 2000))),
        'examples' => orgProblemExamples($body),
    ];
}

function requireOwnedOrgProblem(PDO $pdo, int $orgId, int $problemId): array {
    $stmt = $pdo->prepare('SELECT * FROM org_problems WHERE id = ? AND org_id = ? AND is_deleted = 0');
    $stmt->execute([$problemId, $orgId]);
    $problem = $stmt->fetch();
    if (!$problem) err('Problem not found for this organization', 404);
    return $problem;
}

function createPlatformProblem(PDO $pdo, int $orgId, int $orgProblemId, array $payload): int {
    $slug = 'org-' . $orgId . '-' . $orgProblemId . '-' . orgProblemSlug($payload['title']);
    $stmt = $pdo->prepare(
        'INSERT INTO problems
            (title, slug, difficulty, description, examples, constraints, test_cases,
             time_limit_ms, is_public, created_by, tags, hint_tier1, hint_tier2, hint_tier3)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $payload['title'],
        $slug,
        $payload['difficulty'],
        $payload['description'],
        $payload['examples'],
        $payload['constraints'],
        $payload['test_cases'],
        $payload['time_limit_ms'],
        currentUserId(),
        $payload['tags'],
        $payload['hint_tier1'],
        $payload['hint_tier2'],
        $payload['hint_tier3'],
    ]);
    $platformId = (int)$pdo->lastInsertId();
    $pdo->prepare('UPDATE org_problems SET platform_problem_id = ?, slug = ? WHERE id = ? AND org_id = ?')
        ->execute([$platformId, $slug, $orgProblemId, $orgId]);
    return $platformId;
}

function syncPlatformProblem(PDO $pdo, array $orgProblem, array $payload): void {
    if (empty($orgProblem['platform_problem_id'])) return;
    $stmt = $pdo->prepare(
        'UPDATE problems
         SET title = ?, difficulty = ?, description = ?, examples = ?, constraints = ?,
             test_cases = ?, time_limit_ms = ?, tags = ?, hint_tier1 = ?, hint_tier2 = ?, hint_tier3 = ?
         WHERE id = ?'
    );
    $stmt->execute([
        $payload['title'],
        $payload['difficulty'],
        $payload['description'],
        $payload['examples'],
        $payload['constraints'],
        $payload['test_cases'],
        $payload['time_limit_ms'],
        $payload['tags'],
        $payload['hint_tier1'],
        $payload['hint_tier2'],
        $payload['hint_tier3'],
        (int)$orgProblem['platform_problem_id'],
    ]);
}

if ($method === 'GET') {
    $problemId = (int)($_GET['id'] ?? 0);
    if ($problemId) {
        ok(['problem' => requireOwnedOrgProblem($pdo, $orgId, $problemId)]);
    }

    $difficulty = cleanString($_GET['difficulty'] ?? '', 20);
    $tag = cleanString($_GET['tag'] ?? '', 100);
    $where = ['op.org_id = ?', 'op.is_deleted = 0'];
    $params = [$orgId];
    if ($difficulty) {
        $where[] = 'op.difficulty = ?';
        $params[] = orgProblemDifficulty($difficulty);
    }
    if ($tag) {
        $where[] = 'op.tags LIKE ?';
        $params[] = "%$tag%";
    }

    $stmt = $pdo->prepare(
        'SELECT op.*, COUNT(cp.id) AS contest_count
         FROM org_problems op
         LEFT JOIN contest_problems cp ON cp.org_problem_id = op.id
         WHERE ' . implode(' AND ', $where) . '
         GROUP BY op.id
         ORDER BY op.created_at DESC'
    );
    $stmt->execute($params);
    ok(['problems' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $body = jsonBody();
    $payload = orgProblemPayload($body);
    $baseSlug = orgProblemSlug($payload['title']) . '-' . time();

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO org_problems
                (org_id, title, slug, description, difficulty, tags, input_format, output_format,
                 constraints, sample_input, sample_output, test_cases, hint_tier1, hint_tier2,
                 hint_tier3, time_limit_ms, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $orgId,
            $payload['title'],
            $baseSlug,
            $payload['description'],
            $payload['difficulty'],
            $payload['tags'],
            $payload['input_format'],
            $payload['output_format'],
            $payload['constraints'],
            $payload['sample_input'],
            $payload['sample_output'],
            $payload['test_cases'],
            $payload['hint_tier1'],
            $payload['hint_tier2'],
            $payload['hint_tier3'],
            $payload['time_limit_ms'],
            currentUserId(),
        ]);
        $orgProblemId = (int)$pdo->lastInsertId();
        $platformId = createPlatformProblem($pdo, $orgId, $orgProblemId, $payload);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('org problem create failed: ' . $e->getMessage());
        err('Problem could not be created', 500);
    }

    created(['id' => $orgProblemId, 'platform_problem_id' => $platformId], 'Problem created');
}

if ($method === 'PUT') {
    $body = jsonBody();
    $problemId = (int)($body['id'] ?? 0);
    if (!$problemId) err('id required');
    $problem = requireOwnedOrgProblem($pdo, $orgId, $problemId);
    $payload = orgProblemPayload($body);

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'UPDATE org_problems
             SET title = ?, description = ?, difficulty = ?, tags = ?, input_format = ?, output_format = ?,
                 constraints = ?, sample_input = ?, sample_output = ?, test_cases = ?, hint_tier1 = ?,
                 hint_tier2 = ?, hint_tier3 = ?, time_limit_ms = ?
             WHERE id = ? AND org_id = ?'
        );
        $stmt->execute([
            $payload['title'],
            $payload['description'],
            $payload['difficulty'],
            $payload['tags'],
            $payload['input_format'],
            $payload['output_format'],
            $payload['constraints'],
            $payload['sample_input'],
            $payload['sample_output'],
            $payload['test_cases'],
            $payload['hint_tier1'],
            $payload['hint_tier2'],
            $payload['hint_tier3'],
            $payload['time_limit_ms'],
            $problemId,
            $orgId,
        ]);
        syncPlatformProblem($pdo, $problem, $payload);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('org problem update failed: ' . $e->getMessage());
        err('Problem could not be updated', 500);
    }

    ok(null, 'Problem updated');
}

if ($method === 'DELETE') {
    $body = jsonBody();
    $problemId = (int)($body['id'] ?? 0);
    if (!$problemId) err('id required');
    $problem = requireOwnedOrgProblem($pdo, $orgId, $problemId);

    $inUse = $pdo->prepare(
        'SELECT COUNT(*)
         FROM contest_problems cp
         JOIN contests c ON c.id = cp.contest_id AND c.org_id = ?
         WHERE cp.org_problem_id = ? AND c.org_status <> "draft"'
    );
    $inUse->execute([$orgId, $problemId]);
    if ((int)$inUse->fetchColumn() > 0) err('Published or scheduled contests still use this problem');

    $pdo->prepare('UPDATE org_problems SET is_deleted = 1 WHERE id = ? AND org_id = ?')->execute([$problemId, $orgId]);
    if (!empty($problem['platform_problem_id'])) {
        $pdo->prepare('UPDATE problems SET is_deleted = 1 WHERE id = ?')->execute([(int)$problem['platform_problem_id']]);
    }
    ok(null, 'Problem deleted');
}

err('Method not allowed', 405);
