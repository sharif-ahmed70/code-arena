-- Code Arena auth restructure migration
-- Additive and backward compatible with existing student/instructor/admin data.

ALTER TABLE users
  MODIFY COLUMN role ENUM('student','instructor','admin','user','org_admin') DEFAULT 'user',
  ADD COLUMN IF NOT EXISTS name VARCHAR(120) NULL AFTER id,
  ADD COLUMN IF NOT EXISTS country VARCHAR(80) NULL AFTER role,
  ADD COLUMN IF NOT EXISTS university VARCHAR(160) NULL AFTER country,
  ADD COLUMN IF NOT EXISTS org_id INT NULL AFTER university,
  ADD COLUMN IF NOT EXISTS profile_completed TINYINT(1) NOT NULL DEFAULT 0 AFTER org_id;

UPDATE users
SET name = COALESCE(name, username),
    profile_completed = CASE
      WHEN role IN ('student','instructor','admin') THEN 1
      ELSE profile_completed
    END
WHERE name IS NULL OR profile_completed IS NULL;

CREATE TABLE IF NOT EXISTS organizations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  type ENUM('university','company','community') NOT NULL,
  description TEXT NULL,
  logo VARCHAR(255) NULL,
  owner_id INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_organizations_owner (owner_id),
  KEY idx_organizations_type (type)
);

CREATE TABLE IF NOT EXISTS organization_members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  org_id INT NOT NULL,
  role ENUM('org_owner','org_member') NOT NULL DEFAULT 'org_member',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_org_member_user_org (user_id, org_id),
  KEY idx_org_members_user (user_id),
  KEY idx_org_members_org (org_id),
  CONSTRAINT fk_org_members_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_org_members_org
    FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE
);
