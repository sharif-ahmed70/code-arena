-- Code Arena organization SaaS module migration
-- Additive, multi-tenant safe, and backward compatible with existing contests/submissions.

ALTER TABLE organizations
  ADD COLUMN IF NOT EXISTS status ENUM('active','suspended') NOT NULL DEFAULT 'active',
  ADD INDEX IF NOT EXISTS idx_organizations_status (status);

ALTER TABLE organization_members
  MODIFY COLUMN role ENUM('org_owner','org_admin','org_member','admin','member') NOT NULL DEFAULT 'org_member',
  ADD COLUMN IF NOT EXISTS joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ADD INDEX IF NOT EXISTS idx_org_members_org_role (org_id, role);

ALTER TABLE contests
  ADD COLUMN IF NOT EXISTS org_id INT NULL AFTER id,
  ADD COLUMN IF NOT EXISTS org_status ENUM('draft','scheduled','live','ended','archived') NOT NULL DEFAULT 'scheduled' AFTER status,
  ADD COLUMN IF NOT EXISTS is_published TINYINT(1) NOT NULL DEFAULT 1 AFTER org_status,
  ADD INDEX IF NOT EXISTS idx_contests_org_id (org_id),
  ADD INDEX IF NOT EXISTS idx_contests_org_status (org_id, org_status),
  ADD INDEX IF NOT EXISTS idx_contests_created_by_org (created_by, org_id);

UPDATE contests c
JOIN users u ON u.id = c.created_by
SET c.org_id = u.org_id
WHERE c.org_id IS NULL AND u.org_id IS NOT NULL;

UPDATE contests
SET org_status = CASE
  WHEN status = 'upcoming' THEN 'scheduled'
  WHEN status = 'active' THEN 'live'
  WHEN status = 'ended' THEN 'ended'
  ELSE org_status
END;

ALTER TABLE contest_participants
  ADD COLUMN IF NOT EXISTS status ENUM('registered','approved','rejected','removed','banned') NOT NULL DEFAULT 'registered',
  ADD COLUMN IF NOT EXISTS org_id INT NULL AFTER contest_id,
  ADD INDEX IF NOT EXISTS idx_contest_participants_org (org_id),
  ADD INDEX IF NOT EXISTS idx_contest_participants_status (contest_id, status),
  ADD INDEX IF NOT EXISTS idx_contest_participants_user_status (user_id, status);

UPDATE contest_participants cp
JOIN contests c ON c.id = cp.contest_id
SET cp.org_id = c.org_id
WHERE cp.org_id IS NULL AND c.org_id IS NOT NULL;

ALTER TABLE submissions
  ADD COLUMN IF NOT EXISTS org_id INT NULL AFTER contest_id,
  ADD COLUMN IF NOT EXISTS score INT NOT NULL DEFAULT 0 AFTER status,
  ADD INDEX IF NOT EXISTS idx_submissions_org (org_id),
  ADD INDEX IF NOT EXISTS idx_submissions_org_contest (org_id, contest_id),
  ADD INDEX IF NOT EXISTS idx_submissions_contest_user (contest_id, user_id);

UPDATE submissions s
JOIN contests c ON c.id = s.contest_id
SET s.org_id = c.org_id
WHERE s.org_id IS NULL AND c.org_id IS NOT NULL;

CREATE TABLE IF NOT EXISTS announcements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  org_id INT NOT NULL,
  contest_id INT NULL,
  title VARCHAR(180) NOT NULL,
  message TEXT NOT NULL,
  type ENUM('announcement','clarification','instruction') NOT NULL DEFAULT 'announcement',
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  created_by INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_announcements_org (org_id),
  KEY idx_announcements_contest (contest_id),
  KEY idx_announcements_created_by (created_by)
);

CREATE TABLE IF NOT EXISTS analytics_cache (
  id INT AUTO_INCREMENT PRIMARY KEY,
  org_id INT NOT NULL,
  metric_type VARCHAR(80) NOT NULL,
  value JSON NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_analytics_cache_org_metric (org_id, metric_type),
  KEY idx_analytics_cache_org (org_id)
);
