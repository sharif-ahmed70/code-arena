USE `code_arena`;

SET FOREIGN_KEY_CHECKS = 0;

SET @demo_title = 'Demo Weekly Contest - Live Scoreboard';
SET @demo_slug = 'demo-weekly-contest-live-scoreboard';

DELETE s FROM submissions s
JOIN contests c ON c.id = s.contest_id
WHERE c.slug = @demo_slug;

DELETE cp FROM contest_participants cp
JOIN contests c ON c.id = cp.contest_id
WHERE c.slug = @demo_slug;

DELETE cprob FROM contest_problems cprob
JOIN contests c ON c.id = cprob.contest_id
WHERE c.slug = @demo_slug;

DELETE FROM contests WHERE slug = @demo_slug;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO contests
    (title, slug, description, start_time, end_time, created_by, is_rated, status)
VALUES
    (
        @demo_title,
        @demo_slug,
        'Seeded demo contest for testing live scoreboard, penalties, problem points, and participant ranking.',
        '2026-06-10 20:00:00',
        '2026-06-10 22:00:00',
        1,
        1,
        'active'
    );

SET @contest_id = LAST_INSERT_ID();

INSERT INTO contest_problems (contest_id, problem_id, points, order_index) VALUES
(@contest_id, 1, 100, 1),
(@contest_id, 2, 100, 2),
(@contest_id, 3, 150, 3),
(@contest_id, 4, 200, 4),
(@contest_id, 5, 250, 5);

INSERT INTO contest_participants (contest_id, user_id, registered_at) VALUES
(@contest_id, 2, '2026-06-10 19:40:00'),
(@contest_id, 3, '2026-06-10 19:42:00'),
(@contest_id, 4, '2026-06-10 19:43:00'),
(@contest_id, 5, '2026-06-10 19:45:00');

INSERT INTO submissions
    (user_id, problem_id, code, language, status, hints_used, contest_id, runtime_ms, test_results, is_practice, submitted_at)
VALUES
-- testuser: solves P1, P2, P3; one failed attempt before P3
(2, 1, '// demo accepted', 'javascript', 'Accepted', 0, @contest_id, 55, '[]', 0, '2026-06-10 20:08:00'),
(2, 2, '// demo accepted', 'python', 'Accepted', 0, @contest_id, 70, '[]', 0, '2026-06-10 20:18:00'),
(2, 3, '// demo wrong', 'cpp', 'Wrong Answer', 0, @contest_id, 80, '[]', 0, '2026-06-10 20:30:00'),
(2, 3, '// demo accepted', 'cpp', 'Accepted', 0, @contest_id, 88, '[]', 0, '2026-06-10 20:41:00'),

-- shafin: solves P1, P2, P4 with two failed attempts before P4
(3, 1, '# demo accepted', 'python', 'Accepted', 0, @contest_id, 42, '[]', 0, '2026-06-10 20:05:00'),
(3, 2, '# demo accepted', 'python', 'Accepted', 0, @contest_id, 66, '[]', 0, '2026-06-10 20:16:00'),
(3, 4, '# demo runtime', 'python', 'Runtime Error', 0, @contest_id, 110, '[]', 0, '2026-06-10 20:28:00'),
(3, 4, '# demo wrong', 'python', 'Wrong Answer', 0, @contest_id, 115, '[]', 0, '2026-06-10 20:38:00'),
(3, 4, '# demo accepted', 'python', 'Accepted', 0, @contest_id, 120, '[]', 0, '2026-06-10 20:55:00'),

-- tawsif: fewer points but low penalty
(4, 1, '// demo accepted', 'javascript', 'Accepted', 0, @contest_id, 50, '[]', 0, '2026-06-10 20:04:00'),
(4, 2, '// demo accepted', 'javascript', 'Accepted', 0, @contest_id, 60, '[]', 0, '2026-06-10 20:12:00'),
(4, 3, '// demo accepted', 'javascript', 'Accepted', 0, @contest_id, 76, '[]', 0, '2026-06-10 20:24:00'),

-- user 5: attempts many, solves P1 and P5
(5, 1, '// demo wrong', 'java', 'Wrong Answer', 0, @contest_id, 90, '[]', 0, '2026-06-10 20:07:00'),
(5, 1, '// demo accepted', 'java', 'Accepted', 0, @contest_id, 95, '[]', 0, '2026-06-10 20:22:00'),
(5, 5, '// demo wrong', 'java', 'Wrong Answer', 0, @contest_id, 130, '[]', 0, '2026-06-10 20:40:00'),
(5, 5, '// demo wrong', 'java', 'Wrong Answer', 0, @contest_id, 132, '[]', 0, '2026-06-10 21:05:00'),
(5, 5, '// demo accepted', 'java', 'Accepted', 0, @contest_id, 140, '[]', 0, '2026-06-10 21:18:00');
