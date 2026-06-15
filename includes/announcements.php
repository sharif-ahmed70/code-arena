<?php
// ============================================================
//  Code Arena - Announcement schema helpers
// ============================================================

function announcementColumnExists(PDO $pdo, string $column): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?
           AND COLUMN_NAME = ?'
    );
    $stmt->execute(['announcements', $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function announcementTableExists(PDO $pdo): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?'
    );
    $stmt->execute(['announcements']);
    return (int)$stmt->fetchColumn() > 0;
}

function ensureAnnouncementsSchema(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $done = true;

    if (!announcementTableExists($pdo)) {
        $pdo->exec(
            "CREATE TABLE announcements (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(180) NOT NULL,
                message TEXT NOT NULL,
                target_type VARCHAR(20) NOT NULL DEFAULT 'global',
                org_id INT NULL,
                contest_id INT NULL,
                type VARCHAR(30) NOT NULL DEFAULT 'announcement',
                is_published TINYINT(1) NOT NULL DEFAULT 1,
                created_by INT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_announcements_target (target_type, org_id, contest_id),
                KEY idx_announcements_org (org_id),
                KEY idx_announcements_contest (contest_id),
                KEY idx_announcements_created_by (created_by),
                KEY idx_announcements_created_at (created_at)
            )"
        );
        return;
    }

    $columns = [
        'target_type' => "ALTER TABLE announcements ADD COLUMN target_type VARCHAR(20) NOT NULL DEFAULT 'org' AFTER message",
        'org_id' => "ALTER TABLE announcements ADD COLUMN org_id INT NULL AFTER target_type",
        'contest_id' => "ALTER TABLE announcements ADD COLUMN contest_id INT NULL AFTER org_id",
        'type' => "ALTER TABLE announcements ADD COLUMN type VARCHAR(30) NOT NULL DEFAULT 'announcement' AFTER message",
        'is_published' => "ALTER TABLE announcements ADD COLUMN is_published TINYINT(1) NOT NULL DEFAULT 1",
        'created_by' => "ALTER TABLE announcements ADD COLUMN created_by INT NULL",
        'created_at' => "ALTER TABLE announcements ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
        'updated_at' => "ALTER TABLE announcements ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP",
    ];

    foreach ($columns as $column => $sql) {
        if (!announcementColumnExists($pdo, $column)) {
            $pdo->exec($sql);
        }
    }

    $nullableStmt = $pdo->prepare(
        'SELECT IS_NULLABLE
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?
           AND COLUMN_NAME = ?'
    );
    $nullableStmt->execute(['announcements', 'org_id']);
    if (strtoupper((string)$nullableStmt->fetchColumn()) === 'NO') {
        $pdo->exec('ALTER TABLE announcements MODIFY COLUMN org_id INT NULL');
    }

    $pdo->exec("UPDATE announcements SET target_type = 'org' WHERE target_type IS NULL OR target_type = ''");

    $indexes = [
        'idx_announcements_target' => 'CREATE INDEX idx_announcements_target ON announcements (target_type, org_id, contest_id)',
        'idx_announcements_created_at' => 'CREATE INDEX idx_announcements_created_at ON announcements (created_at)',
    ];
    foreach ($indexes as $name => $sql) {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND INDEX_NAME = ?'
        );
        $stmt->execute(['announcements', $name]);
        if ((int)$stmt->fetchColumn() === 0) {
            $pdo->exec($sql);
        }
    }
}

function ensureAnnouncementReadsSchema(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $done = true;

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS announcement_reads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            announcement_id INT NOT NULL,
            user_id INT NOT NULL,
            read_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_announcement_reads_user_announcement (user_id, announcement_id),
            KEY idx_announcement_reads_user (user_id),
            KEY idx_announcement_reads_announcement (announcement_id)
        )'
    );
}

function announcementUserOrgIds(PDO $pdo, int $userId): array {
    $ids = [];

    $stmt = $pdo->prepare('SELECT org_id FROM users WHERE id = ? AND org_id IS NOT NULL AND COALESCE(is_deleted, 0) = 0');
    $stmt->execute([$userId]);
    $directOrgId = $stmt->fetchColumn();
    if ($directOrgId) $ids[] = (int)$directOrgId;

    $stmt = $pdo->prepare('SELECT org_id FROM organization_members WHERE user_id = ?');
    $stmt->execute([$userId]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $orgId) {
        if ($orgId) $ids[] = (int)$orgId;
    }

    $stmt = $pdo->prepare(
        'SELECT DISTINCT COALESCE(cp.org_id, c.org_id) AS org_id
         FROM contest_participants cp
         JOIN contests c ON c.id = cp.contest_id
         WHERE cp.user_id = ?
           AND COALESCE(cp.status, "registered") NOT IN ("removed", "banned", "rejected")
           AND COALESCE(cp.org_id, c.org_id) IS NOT NULL'
    );
    $stmt->execute([$userId]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $orgId) {
        if ($orgId) $ids[] = (int)$orgId;
    }

    return array_values(array_unique($ids));
}
