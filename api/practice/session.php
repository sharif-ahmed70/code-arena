<?php
// ============================================================
//  CODE ARENA — Practice Session
//  GET  ?contest_id=X  → fetch existing session (or 404)
//  POST { contest_id } → start session (idempotent)
// ============================================================
require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/response.php';

requireLogin();
$uid = currentUserId();

// ── helpers ──────────────────────────────────────────────────
function buildSessionRow(array $row): array {
    $elapsed   = time() - strtotime($row['started_at']);
    $remaining = max(0, (int)$row['duration_seconds'] - $elapsed);
    return [
        'id'               => (int)$row['id'],
        'contest_id'       => (int)$row['contest_id'],
        'started_at'       => $row['started_at'],
        'duration_seconds' => (int)$row['duration_seconds'],
        'seconds_remaining'=> $remaining,
        'expired'          => $remaining === 0,
    ];
}

function loadContest(PDO $pdo, int $cid): array {
    $s = $pdo->prepare('SELECT id, status, start_time, end_time FROM contests WHERE id = ?');
    $s->execute([$cid]);
    $c = $s->fetch();
    if (!$c)                      err('Contest not found', 404);
    if ($c['status'] !== 'ended') err('Practice mode is only available for ended contests', 403);
    return $c;
}

// ── GET ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $cid = (int)($_GET['contest_id'] ?? 0);
    if (!$cid) err('contest_id required');

    $s = $pdo->prepare(
        'SELECT * FROM practice_sessions WHERE user_id = ? AND contest_id = ?'
    );
    $s->execute([$uid, $cid]);
    $row = $s->fetch();
    if (!$row) err('No practice session found', 404);
    ok(buildSessionRow($row));
}

// ── POST ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = jsonBody();
    $cid  = (int)($body['contest_id'] ?? 0);
    if (!$cid) err('contest_id required');

    $contest = loadContest($pdo, $cid);

    // Calculate original duration in seconds
    $start   = new DateTime($contest['start_time']);
    $end     = new DateTime($contest['end_time']);
    $durSecs = max(60, (int)$end->getTimestamp() - (int)$start->getTimestamp());

    // Idempotent: return existing session if already started
    $check = $pdo->prepare(
        'SELECT * FROM practice_sessions WHERE user_id = ? AND contest_id = ?'
    );
    $check->execute([$uid, $cid]);
    $existing = $check->fetch();
    if ($existing) {
        ok(buildSessionRow($existing));
    }

    // Create new session
    $ins = $pdo->prepare(
        'INSERT INTO practice_sessions (user_id, contest_id, duration_seconds)
         VALUES (?, ?, ?)'
    );
    $ins->execute([$uid, $cid, $durSecs]);

    $fetch = $pdo->prepare('SELECT * FROM practice_sessions WHERE id = ?');
    $fetch->execute([$pdo->lastInsertId()]);
    ok(buildSessionRow($fetch->fetch()));
}

err('Method not allowed', 405);
