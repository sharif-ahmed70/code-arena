-- Code Arena - Admin Activity Logs

CREATE TABLE IF NOT EXISTS `admin_activity_logs` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `admin_id` INT NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `target_type` VARCHAR(50) NOT NULL,
  `target_id` INT NULL,
  `description` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_admin_activity_admin_created` (`admin_id`, `created_at`),
  KEY `idx_admin_activity_target` (`target_type`, `target_id`),
  KEY `idx_admin_activity_created` (`created_at`),
  CONSTRAINT `fk_admin_activity_logs_admin`
    FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
