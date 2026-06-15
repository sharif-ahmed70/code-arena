<?php
// ============================================================
//  Organization Participant Management API
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/organization.php';
require_once '../../includes/response.php';

$org = requireOrganizationApi($pdo);
$orgId = (int)$org['id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $contestId = (int)($_GET['contest_id'] ?? 0);
    $status = cleanString($_GET['status'] ?? '', 20);
    $where = ['cp.org_id = ?'];
    $params = [$orgId];

    if ($contestId) {
        requireOwnedContest($pdo, $orgId, $contestId);
        $where[] = 'cp.contest_id = ?';
        $params[] = $contestId;
    }
    if ($status) {
        if (!in_array($status, ['registered', 'approved', 'rejected', 'removed', 'banned'], true)) err('Invalid status');
        $where[] = 'cp.status = ?';
        $params[] = $status;
    }

    $stmt = $pdo->prepare(
        'SELECT cp.id, cp.contest_id, cp.user_id, cp.status, cp.score, cp.penalty_minutes, cp.registered_at,
                u.username, u.email, u.name,
                c.title AS contest_title,
                COUNT(s.id) AS submission_count,
                SUM(s.status = "Accepted") AS accepted_count
         FROM contest_participants cp
         JOIN users u ON u.id = cp.user_id AND COALESCE(u.is_deleted, 0) = 0
         JOIN contests c ON c.id = cp.contest_id AND c.org_id = cp.org_id
         LEFT JOIN submissions s ON s.contest_id = cp.contest_id AND s.user_id = cp.user_id AND s.org_id = cp.org_id
         WHERE ' . implode(' AND ', $where) . '
         GROUP BY cp.id
         ORDER BY cp.registered_at DESC'
    );
    $stmt->execute($params);
    ok(['participants' => $stmt->fetchAll()]);
}

if ($method === 'PUT') {
    $body = jsonBody();
    $participantId = (int)($body['participant_id'] ?? 0);
    $action = cleanString($body['action'] ?? '', 20);
    $statusMap = [
        'approve' => 'approved',
        'reject' => 'rejected',
        'remove' => 'removed',
        'ban' => 'banned',
        'restore' => 'registered',
    ];
    if (!$participantId || !isset($statusMap[$action])) err('participant_id and valid action are required');

    $stmt = $pdo->prepare(
        'SELECT cp.id
         FROM contest_participants cp
         JOIN contests c ON c.id = cp.contest_id AND c.org_id = ?
         WHERE cp.id = ? AND cp.org_id = ?'
    );
    $stmt->execute([$orgId, $participantId, $orgId]);
    if (!$stmt->fetch()) err('Participant not found for this organization', 404);

    $pdo->prepare('UPDATE contest_participants SET status = ? WHERE id = ? AND org_id = ?')
        ->execute([$statusMap[$action], $participantId, $orgId]);
    ok(null, 'Participant updated');
}

err('Method not allowed', 405);
