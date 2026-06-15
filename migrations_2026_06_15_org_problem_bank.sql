-- Code Arena organization problem bank migration
-- Additive and backward compatible with the existing global problems solver.

CREATE TABLE IF NOT EXISTS org_problems (
  id INT AUTO_INCREMENT PRIMARY KEY,
  org_id INT NOT NULL,
  platform_problem_id INT NULL,
  title VARCHAR(200) NOT NULL,
  slug VARCHAR(220) NOT NULL,
  description TEXT NOT NULL,
  difficulty ENUM('Easy','Medium','Hard') NOT NULL DEFAULT 'Easy',
  tags VARCHAR(255) NULL,
  input_format TEXT NULL,
  output_format TEXT NULL,
  constraints TEXT NULL,
  sample_input TEXT NULL,
  sample_output TEXT NULL,
  test_cases LONGTEXT NULL,
  hint_tier1 TEXT NULL,
  hint_tier2 TEXT NULL,
  hint_tier3 TEXT NULL,
  time_limit_ms INT NOT NULL DEFAULT 2000,
  created_by INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  is_deleted TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_org_problems_slug (org_id, slug),
  KEY idx_org_problems_org (org_id),
  KEY idx_org_problems_platform (platform_problem_id),
  KEY idx_org_problems_difficulty (org_id, difficulty),
  KEY idx_org_problems_deleted (org_id, is_deleted)
);

ALTER TABLE contests
  ADD COLUMN IF NOT EXISTS visibility ENUM('public','org') NOT NULL DEFAULT 'public' AFTER is_published,
  ADD INDEX IF NOT EXISTS idx_contests_visibility (visibility),
  ADD INDEX IF NOT EXISTS idx_contests_publish_visibility (is_published, visibility);

ALTER TABLE contest_problems
  ADD COLUMN IF NOT EXISTS org_problem_id INT NULL AFTER problem_id,
  ADD INDEX IF NOT EXISTS idx_contest_problems_org_problem (org_problem_id);
