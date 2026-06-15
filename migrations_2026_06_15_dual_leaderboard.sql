-- Code Arena dual leaderboard migration
-- Additive and backward compatible.

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS skill_rating INT NULL,
  ADD COLUMN IF NOT EXISTS skill_mode ENUM('hardcore','learning') DEFAULT 'hardcore',
  ADD COLUMN IF NOT EXISTS contest_rating INT NULL;

UPDATE users
SET skill_rating = COALESCE(hardcore_rating, skill_rating, 1200)
WHERE skill_rating IS NULL;

UPDATE users
SET contest_rating = COALESCE(contest_rating, 1200)
WHERE contest_rating IS NULL;

ALTER TABLE users
  MODIFY COLUMN skill_rating INT DEFAULT 1200,
  MODIFY COLUMN contest_rating INT DEFAULT 1200;

CREATE TABLE IF NOT EXISTS user_practice_stats (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  total_solved INT NOT NULL DEFAULT 0,
  accuracy FLOAT NOT NULL DEFAULT 0,
  streak_days INT NOT NULL DEFAULT 0,
  rating INT NOT NULL DEFAULT 1200,
  last_active_date DATE NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_practice_stats_user (user_id),
  CONSTRAINT fk_user_practice_stats_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS practice_submissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  problem_id INT NOT NULL,
  status ENUM('AC','WA','TLE','RE') NOT NULL,
  language VARCHAR(50) NOT NULL,
  submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_practice_submissions_user (user_id),
  KEY idx_practice_submissions_problem (problem_id),
  KEY idx_practice_submissions_user_problem (user_id, problem_id),
  CONSTRAINT fk_practice_submissions_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_practice_submissions_problem
    FOREIGN KEY (problem_id) REFERENCES problems(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS contest_leaderboard (
  id INT AUTO_INCREMENT PRIMARY KEY,
  contest_id INT NOT NULL,
  user_id INT NOT NULL,
  `rank` INT NOT NULL,
  score INT NOT NULL DEFAULT 0,
  penalty INT NOT NULL DEFAULT 0,
  solved_count INT NOT NULL DEFAULT 0,
  rating_change INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_contest_leaderboard_user (contest_id, user_id),
  KEY idx_contest_leaderboard_contest (contest_id),
  KEY idx_contest_leaderboard_user (user_id),
  CONSTRAINT fk_contest_leaderboard_contest
    FOREIGN KEY (contest_id) REFERENCES contests(id) ON DELETE CASCADE,
  CONSTRAINT fk_contest_leaderboard_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS user_rating_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  contest_id INT NOT NULL,
  old_rating INT NOT NULL,
  new_rating INT NOT NULL,
  rating_change INT NOT NULL DEFAULT 0,
  `rank` INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_rating_history_contest (user_id, contest_id),
  KEY idx_user_rating_history_user (user_id),
  KEY idx_user_rating_history_contest (contest_id),
  CONSTRAINT fk_user_rating_history_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_user_rating_history_contest
    FOREIGN KEY (contest_id) REFERENCES contests(id) ON DELETE CASCADE
);

INSERT INTO practice_submissions (user_id, problem_id, status, language, submitted_at)
SELECT s.user_id,
       s.problem_id,
       CASE
         WHEN s.status = 'Accepted' THEN 'AC'
         WHEN s.status = 'Time Limit Exceeded' THEN 'TLE'
         WHEN s.status IN ('Runtime Error', 'Compilation Error') THEN 'RE'
         ELSE 'WA'
       END AS status,
       s.language,
       s.submitted_at
FROM submissions s
LEFT JOIN practice_submissions ps
  ON ps.user_id = s.user_id
 AND ps.problem_id = s.problem_id
 AND ps.submitted_at = s.submitted_at
WHERE s.contest_id IS NULL
  AND s.is_practice = 0
  AND ps.id IS NULL;

INSERT INTO user_practice_stats
  (user_id, total_solved, accuracy, streak_days, rating, last_active_date)
SELECT u.id,
       COALESCE(p.total_solved, 0),
       COALESCE(p.accuracy, 0),
       CASE WHEN p.last_active_date IS NULL THEN 0 ELSE 1 END,
       1200 + (COALESCE(p.total_solved, 0) * 10) + FLOOR(COALESCE(p.accuracy, 0) / 5),
       p.last_active_date
FROM users u
LEFT JOIN (
  SELECT user_id,
         COUNT(DISTINCT CASE WHEN status = 'AC' THEN problem_id END) AS total_solved,
         ROUND((SUM(status = 'AC') / GREATEST(COUNT(*), 1)) * 100, 2) AS accuracy,
         MAX(DATE(submitted_at)) AS last_active_date
  FROM practice_submissions
  GROUP BY user_id
) p ON p.user_id = u.id
WHERE u.role <> 'admin'
ON DUPLICATE KEY UPDATE
  total_solved = VALUES(total_solved),
  accuracy = VALUES(accuracy),
  rating = GREATEST(user_practice_stats.rating, VALUES(rating)),
  last_active_date = VALUES(last_active_date);
