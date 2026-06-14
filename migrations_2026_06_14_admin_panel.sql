ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `is_blocked` TINYINT(1) NOT NULL DEFAULT 0 AFTER `role`,
  ADD INDEX IF NOT EXISTS `idx_users_role_blocked` (`role`, `is_blocked`);

ALTER TABLE `submissions`
  ADD INDEX IF NOT EXISTS `idx_submissions_status_language_time` (`status`, `language`, `submitted_at`);

ALTER TABLE `problems`
  ADD INDEX IF NOT EXISTS `idx_problems_difficulty_public` (`difficulty`, `is_public`);
