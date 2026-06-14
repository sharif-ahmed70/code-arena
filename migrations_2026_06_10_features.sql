USE `code_arena`;

CREATE TABLE IF NOT EXISTS `problem_bookmarks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `problem_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_problem_bookmark` (`user_id`, `problem_id`),
  KEY `idx_bookmark_problem` (`problem_id`),
  CONSTRAINT `problem_bookmarks_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `problem_bookmarks_problem_fk` FOREIGN KEY (`problem_id`) REFERENCES `problems` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `problem_editorials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `problem_id` int(11) NOT NULL,
  `approach` text NOT NULL,
  `complexity` varchar(255) DEFAULT NULL,
  `reference_solution` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_problem_editorial` (`problem_id`),
  CONSTRAINT `problem_editorials_problem_fk` FOREIGN KEY (`problem_id`) REFERENCES `problems` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `problem_editorials` (`problem_id`, `approach`, `complexity`, `reference_solution`)
SELECT
  p.id,
  CONCAT(
    'Read the input carefully, identify the required transformation, and solve it with the simplest data structure that fits the constraints. ',
    'For this problem, start from brute force reasoning, then remove repeated work using counting, sorting, hashing, or two pointers depending on the tags.'
  ),
  'Time complexity depends on the chosen approach; most starter solutions should target O(n) or O(n log n). Space complexity is usually O(1) to O(n).',
  NULL
FROM `problems` p
WHERE NOT EXISTS (
  SELECT 1 FROM `problem_editorials` e WHERE e.problem_id = p.id
);
