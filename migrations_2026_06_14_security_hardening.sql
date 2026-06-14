CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `rate_key` CHAR(64) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_rate_limits_key_time` (`rate_key`, `created_at`),
  INDEX `idx_rate_limits_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event` VARCHAR(80) NOT NULL,
  `user_id` INT NULL,
  `ip_address` VARCHAR(45) NULL,
  `context` JSON NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_audit_event_time` (`event`, `created_at`),
  INDEX `idx_audit_user_time` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `submissions`
  ADD INDEX IF NOT EXISTS `idx_submissions_user_status_problem` (`user_id`, `status`, `problem_id`),
  ADD INDEX IF NOT EXISTS `idx_submissions_problem_status` (`problem_id`, `status`),
  ADD INDEX IF NOT EXISTS `idx_submissions_contest_user_problem` (`contest_id`, `user_id`, `problem_id`),
  ADD INDEX IF NOT EXISTS `idx_submissions_submitted_at` (`submitted_at`);

ALTER TABLE `problems`
  ADD INDEX IF NOT EXISTS `idx_problems_public_difficulty` (`is_public`, `difficulty`),
  ADD INDEX IF NOT EXISTS `idx_problems_roadmap_public` (`roadmap_day`, `is_public`);

ALTER TABLE `users`
  ADD INDEX IF NOT EXISTS `idx_users_role_rating` (`role`, `hardcore_rating`, `learning_rating`);
