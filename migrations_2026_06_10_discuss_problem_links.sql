ALTER TABLE `discuss_posts`
  ADD COLUMN IF NOT EXISTS `problem_id` INT NULL AFTER `user_id`,
  ADD INDEX IF NOT EXISTS `idx_discuss_posts_problem` (`problem_id`);

ALTER TABLE `discuss_posts`
  ADD CONSTRAINT `discuss_posts_problem_fk`
  FOREIGN KEY (`problem_id`) REFERENCES `problems` (`id`) ON DELETE SET NULL;
