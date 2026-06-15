<?php
// ============================================================
//  CODE ARENA - Admin Contest Management API
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/adminAuthMiddleware.php';
require_once '../../includes/adminActivityLogger.php';
require_once '../../includes/contest.php';

requireAdminApi();
syncContestStatuses($pdo);

$method = $_SERVER['REQUEST_METHOD'];

function adminContestTableExists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}

if ($method === 'GET') {
    $status = cleanString($_GET['status'] ?? '', 20);
    $search = cleanString($_GET['search'] ?? '', 100);
    $where = [];
    $params = [];
    if ($status) {
        if (!in_array($status, ['upcoming', 'active', 'ended'], true)) err('Invalid status');
        $where[] = 'c.status = ?';
        $params[] = $status;
    }
    if ($search) {
        $where[] = '(c.title LIKE ? OR u.username LIKE ? OR u.email LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $pdo->prepare(
        "SELECT c.id, c.title, c.slug, c.status, c.start_time, c.end_time, c.is_rated, c.created_at,
                u.username AS creator_username, u.email AS creator_email,
                COUNT(DISTINCT cp.user_id) AS participants,
                COUNT(DISTINCT s.id) AS submissions
         FROM contests c
         JOIN users u ON u.id = c.created_by
         LEFT JOIN contest_participants cp ON cp.contest_id = c.id
         LEFT JOIN submissions s ON s.contest_id = c.id AND s.is_practice = 0
         $whereSql
         GROUP BY c.id
         ORDER BY c.start_time DESC
         LIMIT 200"
    );
    $stmt->execute($params);
    ok(['contests' => $stmt->fetchAll()]);
}

if ($method === 'PUT') {
    $body = jsonBody();
    $id = (int)($body['id'] ?? 0);
    $action = cleanString($body['action'] ?? '', 30);
    if (!$id || !$action) err('id and action are required');

    $stmt = $pdo->prepare('SELECT id, title, status FROM contests WHERE id = ?');
    $stmt->execute([$id]);
    $contest = $stmt->fetch();
    if (!$contest) err('Contest not found', 404);

    if ($action === 'end') {
        $pdo->prepare('UPDATE contests SET status = "ended", end_time = LEAST(end_time, NOW()) WHERE id = ?')->execute([$id]);
        logAdminAction(currentUserId(), 'END_CONTEST', 'contest', $id, 'Ended contest ' . $contest['title']);
        ok(null, 'Contest ended');
    }
    if ($action === 'mark_upcoming') {
        if ($contest['status'] === 'active') err('Cannot move a live contest back to upcoming', 403);
        $pdo->prepare('UPDATE contests SET status = "upcoming" WHERE id = ?')->execute([$id]);
        logAdminAction(currentUserId(), 'MARK_CONTEST_UPCOMING', 'contest', $id, 'Marked contest upcoming');
        ok(null, 'Contest marked upcoming');
    }

    err('Unknown action');
}

if ($method === 'DELETE') {
    $body = jsonBody();
    $id = (int)($body['id'] ?? $body['contest_id'] ?? 0);
    if (!$id) err('contest id required');

    $stmt = $pdo->prepare('SELECT id, title, status FROM contests WHERE id = ?');
    $stmt->execute([$id]);
    $contest = $stmt->fetch();
    if (!$contest) err('Contest not found', 404);
    if ($contest['status'] === 'active') err('Cannot delete a live contest', 403);

    $pdo->beginTransaction();
    try {
        foreach (['contest_problems', 'contest_participants', 'contest_leaderboard', 'user_rating_history'] as $table) {
            if (adminContestTableExists($pdo, $table)) {
                $pdo->prepare("DELETE FROM `$table` WHERE contest_id = ?")->execute([$id]);
            }
        }
        if (adminContestTableExists($pdo, 'submissions')) {
            $pdo->prepare('UPDATE submissions SET contest_id = NULL WHERE contest_id = ?')->execute([$id]);
        }
        $pdo->prepare('DELETE FROM contests WHERE id = ?')->execute([$id]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('admin contest delete failed: ' . $e->getMessage());
        err('Contest deletion failed', 500);
    }
    logAdminAction(currentUserId(), 'DELETE_CONTEST', 'contest', $id, 'Deleted contest ' . $contest['title']);
    ok(null, 'Contest deleted');
}

err('Method not allowed', 405);
