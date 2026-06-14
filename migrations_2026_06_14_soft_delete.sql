-- Code Arena - Soft Delete Support

ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `is_deleted` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_blocked`,
  ADD INDEX IF NOT EXISTS `idx_users_deleted_blocked` (`is_deleted`, `is_blocked`);

ALTER TABLE `problems`
  ADD COLUMN IF NOT EXISTS `is_deleted` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_public`,
  ADD INDEX IF NOT EXISTS `idx_problems_deleted_public` (`is_deleted`, `is_public`);
