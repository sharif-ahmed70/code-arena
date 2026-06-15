<?php
// ============================================================
//  CODE ARENA - Unified Contest Feed
//  Single source of truth for user-facing contest slider/list/calendar.
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/response.php';
require_once '../../includes/contest.php';

methodCheck('GET');

function caTableExists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}

function caColumnExists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function caIndexExists(PDO $pdo, string $table, string $index): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
    );
    $stmt->execute([$table, $index]);
    return (int)$stmt->fetchColumn() > 0;
}

function caEnsureIndex(PDO $pdo, string $table, string $index, string $columns): void {
    if (!caIndexExists($pdo, $table, $index)) {
        $pdo->exec("ALTER TABLE `$table` ADD INDEX `$index` ($columns)");
    }
}

function ensureContestFeedSchema(PDO $pdo): void {
    if (!caTableExists($pdo, 'contests')) {
        $pdo->exec(
            "CREATE TABLE contests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                org_id INT NULL,
                title VARCHAR(200) NOT NULL,
                slug VARCHAR(200) NOT NULL UNIQUE,
                description TEXT NULL,
                start_time DATETIME NOT NULL,
                end_time DATETIME NOT NULL,
                created_by INT NOT NULL,
                is_rated TINYINT(1) NOT NULL DEFAULT 1,
                status ENUM('upcoming','active','ended') NOT NULL DEFAULT 'upcoming',
                org_status ENUM('draft','scheduled','live','ended','archived') NOT NULL DEFAULT 'scheduled',
                is_published TINYINT(1) NOT NULL DEFAULT 0,
                visibility ENUM('public','org') NOT NULL DEFAULT 'public',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_contests_status (status),
                KEY idx_contests_org_id (org_id),
                KEY idx_contests_feed (is_published, visibility, org_status, status)
            )"
        );
    }

    $contestAdds = [
        'org_id' => 'ADD COLUMN org_id INT NULL AFTER id',
        'is_published' => 'ADD COLUMN is_published TINYINT(1) NOT NULL DEFAULT 0',
        'visibility' => "ADD COLUMN visibility ENUM('public','org') NOT NULL DEFAULT 'public'",
        'org_status' => "ADD COLUMN org_status ENUM('draft','scheduled','live','ended','archived') NOT NULL DEFAULT 'scheduled'",
    ];
    foreach ($contestAdds as $column => $ddl) {
        if (!caColumnExists($pdo, 'contests', $column)) {
            $pdo->exec("ALTER TABLE contests $ddl");
        }
    }
    caEnsureIndex($pdo, 'contests', 'idx_contests_org_id', 'org_id');
    caEnsureIndex($pdo, 'contests', 'idx_contests_feed', 'is_published, visibility, org_status, status');

    if (!caTableExists($pdo, 'contest_problems')) {
        $pdo->exec(
            "CREATE TABLE contest_problems (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contest_id INT NOT NULL,
                problem_id INT NULL,
                org_problem_id INT NULL,
                points INT NOT NULL DEFAULT 100,
                order_index INT NOT NULL DEFAULT 0,
                KEY idx_contest_problems_contest (contest_id),
                KEY idx_contest_problems_problem (problem_id),
                KEY idx_contest_problems_org_problem (org_problem_id)
            )"
        );
    } else {
        if (!caColumnExists($pdo, 'contest_problems', 'problem_id')) {
            $pdo->exec('ALTER TABLE contest_problems ADD COLUMN problem_id INT NULL AFTER contest_id');
        }
        if (!caColumnExists($pdo, 'contest_problems', 'org_problem_id')) {
            $pdo->exec('ALTER TABLE contest_problems ADD COLUMN org_problem_id INT NULL AFTER problem_id');
        }
        if (!caColumnExists($pdo, 'contest_problems', 'order_index')) {
            $pdo->exec('ALTER TABLE contest_problems ADD COLUMN order_index INT NOT NULL DEFAULT 0');
        }
        caEnsureIndex($pdo, 'contest_problems', 'idx_contest_problems_contest', 'contest_id');
    }

    if (!caTableExists($pdo, 'contest_participants')) {
        $pdo->exec(
            "CREATE TABLE contest_participants (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contest_id INT NOT NULL,
                org_id INT NULL,
                user_id INT NOT NULL,
                status ENUM('registered','approved','rejected','removed','banned') NOT NULL DEFAULT 'registered',
                score INT NOT NULL DEFAULT 0,
                penalty_minutes INT NOT NULL DEFAULT 0,
                registered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_contest_participant (contest_id, user_id),
                KEY idx_contest_participants_contest (contest_id),
                KEY idx_contest_participants_user (user_id),
                KEY idx_contest_participants_status (contest_id, status)
            )"
        );
    } else {
        if (!caColumnExists($pdo, 'contest_participants', 'org_id')) {
            $pdo->exec('ALTER TABLE contest_participants ADD COLUMN org_id INT NULL AFTER contest_id');
        }
        if (!caColumnExists($pdo, 'contest_participants', 'status')) {
            $pdo->exec("ALTER TABLE contest_participants ADD COLUMN status ENUM('registered','approved','rejected','removed','banned') NOT NULL DEFAULT 'registered'");
        }
        if (!caColumnExists($pdo, 'contest_participants', 'registered_at')) {
            $pdo->exec('ALTER TABLE contest_participants ADD COLUMN registered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
        }
        caEnsureIndex($pdo, 'contest_participants', 'idx_contest_participants_contest', 'contest_id');
        caEnsureIndex($pdo, 'contest_participants', 'idx_contest_participants_user', 'user_id');
    }
}

try {
    ensureContestFeedSchema($pdo);
    $pdo->exec(
        "UPDATE contests
         SET org_status = CASE status
             WHEN 'active' THEN 'live'
             WHEN 'ended' THEN 'ended'
             ELSE 'scheduled'
         END
         WHERE is_published = 1
           AND org_status = 'draft'"
    );
    syncContestStatuses($pdo);
} catch (Throwable $e) {
    error_log('contest feed schema check failed: ' . $e->getMessage());
    err('Contest feed is temporarily unavailable', 500);
}

$filter = cleanString($_GET['status'] ?? '', 20);
$statusMap = [
    'live' => 'active',
    'active' => 'active',
    'scheduled' => 'upcoming',
    'upcoming' => 'upcoming',
    'ended' => 'ended',
];
$feedStatus = $statusMap[$filter] ?? '';

$where = [
    'c.is_published = 1',
    'c.visibility IN ("public", "org")',
    'c.org_status IN ("scheduled", "live", "ended")',
    'c.status IN ("upcoming", "active", "ended")',
];
$params = [];
if ($feedStatus) {
    $where[] = 'c.status = ?';
    $params[] = $feedStatus;
}

$stmt = $pdo->prepare(
    'SELECT c.id, c.org_id, c.title, c.slug, c.description, c.start_time, c.end_time,
            c.status, c.org_status, c.is_published, c.visibility, c.is_rated, c.created_at,
            COALESCE(o.name, "Code Arena") AS organization_name,
            COALESCE(u.username, u.name, "Code Arena") AS author,
            COUNT(DISTINCT cp.user_id) AS participant_count,
            COUNT(DISTINCT cprob.id) AS problem_count
     FROM contests c
     LEFT JOIN users u ON u.id = c.created_by AND COALESCE(u.is_deleted, 0) = 0
     LEFT JOIN organizations o ON o.id = c.org_id
     LEFT JOIN contest_participants cp
        ON cp.contest_id = c.id
       AND COALESCE(cp.status, "registered") NOT IN ("removed", "banned", "rejected")
     LEFT JOIN contest_problems cprob ON cprob.contest_id = c.id
     WHERE ' . implode(' AND ', $where) . '
     GROUP BY c.id
     ORDER BY
        CASE c.status WHEN "active" THEN 0 WHEN "upcoming" THEN 1 ELSE 2 END,
        c.start_time ASC
     LIMIT 300'
);
$stmt->execute($params);

$rows = array_map(function (array $row): array {
    $statusLabel = match ($row['status']) {
        'active' => 'live',
        'ended' => 'ended',
        default => 'scheduled',
    };
    return [
        'id' => (int)$row['id'],
        'org_id' => $row['org_id'] !== null ? (int)$row['org_id'] : null,
        'title' => $row['title'],
        'slug' => $row['slug'],
        'description' => $row['description'],
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time'],
        'status' => $row['status'],
        'feed_status' => $statusLabel,
        'org_status' => $row['org_status'],
        'is_published' => (int)$row['is_published'],
        'visibility' => $row['visibility'],
        'is_rated' => (int)$row['is_rated'],
        'created_at' => $row['created_at'],
        'author' => $row['author'],
        'organization_name' => $row['organization_name'],
        'platform' => 'Code Arena',
        'participant_count' => (int)$row['participant_count'],
        'problem_count' => (int)$row['problem_count'],
    ];
}, $stmt->fetchAll());

ok($rows);
