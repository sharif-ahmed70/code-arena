-- Code Arena contest system hardening migration
-- Safe additions only: no data deletion, no table rewrites.

SET @sql = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE contests ADD INDEX idx_contests_status_start_end (status, start_time, end_time)',
    'SELECT 1'
  )
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'contests'
    AND index_name = 'idx_contests_status_start_end'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE contest_participants ADD INDEX idx_contest_participants_contest_registered (contest_id, registered_at)',
    'SELECT 1'
  )
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'contest_participants'
    AND index_name = 'idx_contest_participants_contest_registered'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE contest_problems ADD INDEX idx_contest_problems_contest_order (contest_id, order_index)',
    'SELECT 1'
  )
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'contest_problems'
    AND index_name = 'idx_contest_problems_contest_order'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE submissions ADD CONSTRAINT fk_submissions_contest FOREIGN KEY (contest_id) REFERENCES contests(id) ON DELETE SET NULL',
    'SELECT 1'
  )
  FROM information_schema.table_constraints
  WHERE table_schema = DATABASE()
    AND table_name = 'submissions'
    AND constraint_name = 'fk_submissions_contest'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
