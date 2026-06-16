<?php
// Large CodeArena test dataset seeder.
// Creates/updates 200 users and 10 organizations with contests, members, problems, submissions, analytics data.
require_once __DIR__ . '/config/db.php';

date_default_timezone_set('Asia/Dhaka');
const LARGE_SEED_NOW = '2026-06-16 09:00:00';
const LARGE_SEED_PASSWORD = 'CodeArena@2026';

function lseedOne(PDO $pdo, string $sql, array $params = [], $default = null) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $value = $stmt->fetchColumn();
    return $value === false ? $default : $value;
}

function lseedRows(PDO $pdo, string $sql, array $params = []): array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll() ?: [];
}

function lseedSlug(string $text): string {
    return strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $text), '-')) ?: 'item';
}

function lseedTime(string $modifier): string {
    return date('Y-m-d H:i:s', strtotime($modifier, strtotime(LARGE_SEED_NOW)));
}

function lseedUser(PDO $pdo, int $i, string $role, ?int $orgId = null): int {
    $first = ['Ari', 'Nora', 'Maya', 'Rafi', 'Leah', 'Omar', 'Priya', 'Jonas', 'Elena', 'Sara', 'Tanvir', 'Mei', 'Carlos', 'Fatima', 'Daniel', 'Ibrahim', 'Ayesha', 'Marcus', 'Nadia', 'Kenji'];
    $last = ['Rahman', 'Chen', 'Torres', 'Kim', 'Hasan', 'Saha', 'Meyer', 'Noor', 'Lee', 'Novak', 'Rivera', 'Lin', 'Karim', 'Martin', 'Petrova', 'Ahmed', 'Islam', 'Costa', 'Nasser', 'Roy'];
    $countries = ['Bangladesh', 'India', 'United States', 'Canada', 'Germany', 'Japan', 'South Korea', 'Egypt', 'Mexico', 'Poland'];
    $universities = ['Northbridge University', 'Dhaka Tech', 'Metro State', 'QuantumWorks Academy', 'Global Coding School', 'IIT Kharagpur', 'Waterloo', 'MIT', 'SNU', 'TU Munich'];
    $name = $first[$i % count($first)] . ' ' . $last[($i * 3) % count($last)];
    $username = 'loadtest_user_' . str_pad((string)$i, 3, '0', STR_PAD_LEFT);
    $email = $username . '@seed.codearena.test';
    $skill = 1000 + (($i * 37) % 980);
    $contest = 980 + (($i * 43) % 1040);
    $mode = $i % 4 === 0 ? 'learning' : 'hardcore';
    $created = lseedTime('-' . (420 - $i) . ' days');
    $password = password_hash(LARGE_SEED_PASSWORD, PASSWORD_DEFAULT);
    $existing = lseedOne($pdo, 'SELECT id FROM users WHERE email = ? LIMIT 1', [$email]);

    if ($existing) {
        $stmt = $pdo->prepare(
            'UPDATE users
             SET name = ?, username = ?, password = ?, role = ?, country = ?, university = ?, org_id = ?, profile_completed = 1,
                 skill_rating = ?, skill_mode = ?, contest_rating = ?, hardcore_rating = ?, learning_rating = ?,
                 is_blocked = 0, is_deleted = 0
             WHERE id = ?'
        );
        $stmt->execute([
            $name, $username, $password, $role, $countries[$i % count($countries)], $universities[$i % count($universities)], $orgId,
            $skill, $mode, $contest, $skill, max(950, $skill - 80), (int)$existing,
        ]);
        return (int)$existing;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO users
            (name, username, email, password, role, country, university, org_id, profile_completed,
             skill_rating, skill_mode, contest_rating, hardcore_rating, learning_rating, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $name, $username, $email, $password, $role, $countries[$i % count($countries)], $universities[$i % count($universities)], $orgId,
        $skill, $mode, $contest, $skill, max(950, $skill - 80), $created,
    ]);
    return (int)$pdo->lastInsertId();
}

function lseedOrg(PDO $pdo, int $i, int $ownerId): int {
    $names = [
        'Atlas University Programming Club',
        'Nexus Algorithms Society',
        'BluePeak Engineering Academy',
        'Orbit Competitive Coding Lab',
        'Vertex Developer Community',
        'Summit Tech Interview Hub',
        'Nova Campus Code League',
        'Pioneer Data Structures Guild',
        'Catalyst Software Challenge Team',
        'Riverside Programming Network',
    ];
    $types = ['university', 'community', 'company', 'university', 'community', 'company', 'university', 'community', 'company', 'community'];
    $name = $names[$i - 1];
    $existing = lseedOne($pdo, 'SELECT id FROM organizations WHERE name = ? LIMIT 1', [$name]);
    $description = $name . ' hosts recurring contests, structured practice sessions, curated problem banks, and performance reviews for members.';
    if ($existing) {
        $stmt = $pdo->prepare('UPDATE organizations SET type = ?, description = ?, owner_id = ?, status = "active", is_deleted = 0 WHERE id = ?');
        $stmt->execute([$types[$i - 1], $description, $ownerId, (int)$existing]);
        return (int)$existing;
    }
    $stmt = $pdo->prepare('INSERT INTO organizations (name, type, description, logo, owner_id, created_at, status, is_deleted) VALUES (?, ?, ?, "", ?, ?, "active", 0)');
    $stmt->execute([$name, $types[$i - 1], $description, $ownerId, lseedTime('-' . (360 - $i * 18) . ' days')]);
    return (int)$pdo->lastInsertId();
}

function lseedMember(PDO $pdo, int $orgId, int $userId, string $role, string $joinedAt): void {
    $existing = lseedOne($pdo, 'SELECT id FROM organization_members WHERE org_id = ? AND user_id = ? LIMIT 1', [$orgId, $userId]);
    if ($existing) {
        $stmt = $pdo->prepare('UPDATE organization_members SET role = ?, joined_at = ? WHERE id = ?');
        $stmt->execute([$role, $joinedAt, (int)$existing]);
        return;
    }
    $stmt = $pdo->prepare('INSERT INTO organization_members (user_id, org_id, role, created_at, joined_at) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $orgId, $role, $joinedAt, $joinedAt]);
}

function lseedOrgProblem(PDO $pdo, int $orgId, int $ownerId, array $platform, int $i): int {
    $topicNames = ['Routing', 'Scheduler', 'Leaderboard', 'Cache', 'Signals', 'Matching', 'Window', 'Graph', 'Matrix', 'Ranking', 'Queue', 'Budget'];
    $title = $topicNames[$i % count($topicNames)] . ' Challenge ' . str_pad((string)$i, 2, '0', STR_PAD_LEFT);
    $slug = 'large-' . $orgId . '-' . lseedSlug($title);
    $existing = lseedOne($pdo, 'SELECT id FROM org_problems WHERE org_id = ? AND slug = ? LIMIT 1', [$orgId, $slug]);
    $difficulty = $i <= 4 ? 'Easy' : ($i <= 9 ? 'Medium' : 'Hard');
    $tests = json_encode([['input' => "5\n1 2 3 4 5", 'output' => '15'], ['input' => "3\n10 20 30", 'output' => '60']], JSON_UNESCAPED_SLASHES);
    $params = [
        (int)$platform['id'], $title,
        'A realistic organization problem inspired by ' . $platform['title'] . '. Solve efficiently and handle production-like edge cases.',
        $difficulty, $platform['tags'] ?: 'implementation,arrays',
        'First line contains n, followed by n values.', 'Print the computed answer.',
        '1 <= n <= 200000. Values fit in signed 64-bit integer.', "5\n1 2 3 4 5", '15', $tests,
        'Identify the core invariant first.', 'Use prefix sums, sorting, or graph traversal as appropriate.', 'Check boundary and duplicate cases.',
        $difficulty === 'Hard' ? 3000 : 2000, $ownerId,
    ];
    if ($existing) {
        $stmt = $pdo->prepare(
            'UPDATE org_problems SET platform_problem_id=?, title=?, description=?, difficulty=?, tags=?, input_format=?, output_format=?,
             constraints=?, sample_input=?, sample_output=?, test_cases=?, hint_tier1=?, hint_tier2=?, hint_tier3=?, time_limit_ms=?, created_by=?, is_deleted=0
             WHERE id=?'
        );
        $stmt->execute([...$params, (int)$existing]);
        return (int)$existing;
    }
    $stmt = $pdo->prepare(
        'INSERT INTO org_problems
            (org_id, platform_problem_id, title, slug, description, difficulty, tags, input_format, output_format, constraints,
             sample_input, sample_output, test_cases, hint_tier1, hint_tier2, hint_tier3, time_limit_ms, created_by, created_at, is_deleted)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)'
    );
    $stmt->execute([$orgId, ...array_slice($params, 0, 1), $title, $slug, ...array_slice($params, 2), lseedTime('-' . (140 - $i) . ' days')]);
    return (int)$pdo->lastInsertId();
}

function lseedContest(PDO $pdo, int $orgId, int $ownerId, int $i, int $j): int {
    $titles = ['Weekly Sprint', 'Monthly Challenge', 'Interview Drill', 'Graph Invitational', 'Freshers Cup', 'Reliability Round'];
    $title = 'Large Seed Org ' . str_pad((string)$i, 2, '0', STR_PAD_LEFT) . ' ' . $titles[$j - 1];
    $slug = 'large-' . $orgId . '-' . lseedSlug($title);
    $offsets = [-80, -45, -12, 1, 14, 32];
    $start = lseedTime(($offsets[$j - 1] >= 0 ? '+' : '') . $offsets[$j - 1] . ' days');
    $end = date('Y-m-d H:i:s', strtotime($start . ' +' . (2 + ($j % 3)) . ' hours'));
    $status = $offsets[$j - 1] < 0 ? 'ended' : 'upcoming';
    $orgStatus = $offsets[$j - 1] < 0 ? 'ended' : 'scheduled';
    if ($j === 4) {
        $start = lseedTime('-30 minutes');
        $end = lseedTime('+3 hours');
        $status = 'active';
        $orgStatus = 'live';
    }
    $existing = lseedOne($pdo, 'SELECT id FROM contests WHERE slug = ? LIMIT 1', [$slug]);
    $params = [$orgId, $title, 'Full dataset contest with realistic participation, submissions, verdict mix, and analytics values.', $start, $end, $ownerId, 1, $status, $orgStatus, 1, $j % 3 === 0 ? 'org' : 'public'];
    if ($existing) {
        $stmt = $pdo->prepare('UPDATE contests SET org_id=?, title=?, description=?, start_time=?, end_time=?, created_by=?, is_rated=?, status=?, org_status=?, is_published=?, visibility=? WHERE id=?');
        $stmt->execute([...$params, (int)$existing]);
        return (int)$existing;
    }
    $stmt = $pdo->prepare('INSERT INTO contests (org_id,title,slug,description,start_time,end_time,created_by,is_rated,status,org_status,is_published,visibility,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$orgId, $title, $slug, $params[2], $start, $end, $ownerId, 1, $status, $orgStatus, 1, $params[10], lseedTime('-' . (100 - $j * 8) . ' days')]);
    return (int)$pdo->lastInsertId();
}

function lseedContestProblem(PDO $pdo, int $contestId, int $problemId, int $orgProblemId, int $order): void {
    $existing = lseedOne($pdo, 'SELECT id FROM contest_problems WHERE contest_id=? AND org_problem_id=? LIMIT 1', [$contestId, $orgProblemId]);
    if ($existing) {
        $stmt = $pdo->prepare('UPDATE contest_problems SET problem_id=?, points=?, order_index=? WHERE id=?');
        $stmt->execute([$problemId, 100 * $order, $order, (int)$existing]);
        return;
    }
    $stmt = $pdo->prepare('INSERT INTO contest_problems (contest_id, problem_id, org_problem_id, points, order_index) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$contestId, $problemId, $orgProblemId, 100 * $order, $order]);
}

function lseedParticipant(PDO $pdo, int $contestId, int $orgId, int $userId, int $score, int $penalty, string $registered): void {
    $existing = lseedOne($pdo, 'SELECT id FROM contest_participants WHERE contest_id=? AND user_id=? LIMIT 1', [$contestId, $userId]);
    if ($existing) {
        $stmt = $pdo->prepare('UPDATE contest_participants SET org_id=?, score=?, penalty_minutes=?, status="approved", registered_at=? WHERE id=?');
        $stmt->execute([$orgId, $score, $penalty, $registered, (int)$existing]);
        return;
    }
    $stmt = $pdo->prepare('INSERT INTO contest_participants (contest_id, org_id, user_id, score, penalty_minutes, registered_at, status) VALUES (?, ?, ?, ?, ?, ?, "approved")');
    $stmt->execute([$contestId, $orgId, $userId, $score, $penalty, $registered]);
}

function lseedSubmission(PDO $pdo, array $s): void {
    $existing = lseedOne($pdo, 'SELECT id FROM submissions WHERE user_id=? AND problem_id=? AND contest_id=? AND submitted_at=? LIMIT 1', [$s['user_id'], $s['problem_id'], $s['contest_id'], $s['submitted_at']]);
    if ($existing) return;
    $stmt = $pdo->prepare(
        'INSERT INTO submissions (user_id,problem_id,code,language,status,score,hints_used,contest_id,org_id,runtime_ms,memory_kb,test_results,submitted_at,is_practice)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,0)'
    );
    $stmt->execute([$s['user_id'], $s['problem_id'], '// Large dataset submission', $s['language'], $s['status'], $s['score'], $s['hints'], $s['contest_id'], $s['org_id'], $s['runtime'], $s['memory'], json_encode(['passed' => $s['status'] === 'Accepted' ? 12 : 6, 'total' => 12, 'seed' => 'large']), $s['submitted_at']]);
}

$pdo->beginTransaction();
try {
    $platformProblems = lseedRows($pdo, 'SELECT id,title,difficulty,tags FROM problems WHERE is_public=1 AND COALESCE(is_deleted,0)=0 ORDER BY id ASC LIMIT 100');
    if (count($platformProblems) < 30) throw new RuntimeException('Need at least 30 public problems to seed large dataset.');

    $userIds = [];
    for ($i = 1; $i <= 200; $i++) {
        $role = $i <= 10 ? 'org_admin' : 'user';
        $userIds[$i] = lseedUser($pdo, $i, $role);
    }

    $orgIds = [];
    for ($i = 1; $i <= 10; $i++) {
        $ownerId = $userIds[$i];
        $orgId = lseedOrg($pdo, $i, $ownerId);
        $orgIds[$i] = $orgId;
        $pdo->prepare('UPDATE users SET org_id=? WHERE id=?')->execute([$orgId, $ownerId]);
        lseedMember($pdo, $orgId, $ownerId, 'org_owner', lseedTime('-' . (330 - $i * 10) . ' days'));

        $startUser = 11 + (($i - 1) * 19);
        for ($m = 0; $m < 22; $m++) {
            $userIndex = (($startUser + $m - 1) % 190) + 11;
            $role = $m < 2 ? 'org_admin' : 'org_member';
            lseedMember($pdo, $orgId, $userIds[$userIndex], $role, lseedTime('-' . (260 - $m * 3) . ' days'));
            if ($m < 14) $pdo->prepare('UPDATE users SET org_id=? WHERE id=?')->execute([$orgId, $userIds[$userIndex]]);
        }
    }

    $orgProblemMap = [];
    foreach ($orgIds as $i => $orgId) {
        $orgProblemMap[$i] = [];
        for ($p = 1; $p <= 15; $p++) {
            $platform = $platformProblems[(($i - 1) * 9 + $p) % count($platformProblems)];
            $orgProblemId = lseedOrgProblem($pdo, $orgId, $userIds[$i], $platform, $p);
            $orgProblemMap[$i][] = ['org_problem_id' => $orgProblemId, 'problem_id' => (int)$platform['id']];
        }
    }

    $languages = ['cpp', 'python', 'javascript', 'java'];
    $verdicts = ['Accepted', 'Accepted', 'Accepted', 'Wrong Answer', 'Time Limit Exceeded', 'Runtime Error', 'Compilation Error'];
    $contestCount = 0;
    foreach ($orgIds as $i => $orgId) {
        for ($j = 1; $j <= 6; $j++) {
            $contestId = lseedContest($pdo, $orgId, $userIds[$i], $i, $j);
            $contestCount++;
            $problemSlice = array_slice($orgProblemMap[$i], (($j - 1) * 2) % 10, 5);
            foreach ($problemSlice as $order => $problem) {
                lseedContestProblem($pdo, $contestId, $problem['problem_id'], $problem['org_problem_id'], $order + 1);
            }

            $contest = lseedRows($pdo, 'SELECT start_time,status FROM contests WHERE id=?', [$contestId])[0];
            $participants = lseedRows($pdo, 'SELECT user_id FROM organization_members WHERE org_id=? ORDER BY id ASC LIMIT 23', [$orgId]);
            foreach ($participants as $idx => $participant) {
                if (($idx + $j) % 7 === 0) continue;
                $userId = (int)$participant['user_id'];
                $totalScore = 0;
                $penalty = 20 + (($idx * 17 + $j * 11) % 210);
                foreach ($problemSlice as $pi => $problem) {
                    $attempts = 1 + (($idx + $pi + $j) % 3);
                    for ($a = 0; $a < $attempts; $a++) {
                        $status = $a === $attempts - 1 && (($idx + $pi + $j) % 5 !== 0) ? 'Accepted' : $verdicts[($idx + $pi + $a + $j) % count($verdicts)];
                        $score = $status === 'Accepted' ? (($pi + 1) * 100) : 0;
                        if ($status === 'Accepted') $totalScore += $score;
                        lseedSubmission($pdo, [
                            'user_id' => $userId,
                            'problem_id' => $problem['problem_id'],
                            'contest_id' => $contestId,
                            'org_id' => $orgId,
                            'language' => $languages[($idx + $pi + $a) % count($languages)],
                            'status' => $status,
                            'score' => $score,
                            'hints' => ($idx + $pi) % 3,
                            'runtime' => 28 + (($idx * 31 + $pi * 43 + $a * 17) % 1200),
                            'memory' => 12000 + (($idx * 997 + $pi * 701) % 98000),
                            'submitted_at' => date('Y-m-d H:i:s', strtotime($contest['start_time'] . ' +' . (8 + $idx * 6 + $pi * 12 + $a * 7) . ' minutes')),
                        ]);
                    }
                }
                lseedParticipant($pdo, $contestId, $orgId, $userId, min($totalScore, 1500), $penalty, date('Y-m-d H:i:s', strtotime($contest['start_time'] . ' -' . (1 + ($idx % 5)) . ' days')));
            }

            $leaders = lseedRows($pdo, 'SELECT user_id,score,penalty_minutes FROM contest_participants WHERE contest_id=? ORDER BY score DESC, penalty_minutes ASC LIMIT 50', [$contestId]);
            $rank = 1;
            foreach ($leaders as $row) {
                $existing = lseedOne($pdo, 'SELECT id FROM contest_leaderboard WHERE contest_id=? AND user_id=? LIMIT 1', [$contestId, (int)$row['user_id']]);
                if (!$existing) {
                    $stmt = $pdo->prepare('INSERT INTO contest_leaderboard (contest_id,user_id,rank,score,penalty,solved_count,rating_change,created_at) VALUES (?,?,?,?,?,?,?,?)');
                    $stmt->execute([$contestId, (int)$row['user_id'], $rank, (int)$row['score'], (int)$row['penalty_minutes'], (int)floor((int)$row['score'] / 100), max(-60, 75 - $rank * 6), LARGE_SEED_NOW]);
                }
                $rank++;
            }
        }
    }

    foreach ($orgIds as $i => $orgId) {
        $ann = [
            ['Contest schedule published', 'The next round schedule is live. Please review contest rules and allowed languages.'],
            ['Problem bank updated', 'New practice problems were added for graph, DP, and implementation training.'],
            ['Performance review available', 'Analytics now includes verdict trends, peak hours, and top performer data.'],
        ];
        foreach ($ann as $a => $row) {
            $title = 'Large Seed Org ' . str_pad((string)$i, 2, '0', STR_PAD_LEFT) . ': ' . $row[0];
            if (!lseedOne($pdo, 'SELECT id FROM announcements WHERE title=? LIMIT 1', [$title])) {
                $stmt = $pdo->prepare('INSERT INTO announcements (org_id,contest_id,title,message,target_type,type,is_published,created_by,created_at) VALUES (?,NULL,?,?, "org", "announcement", 1, ?, ?)');
                $stmt->execute([$orgId, $title, $row[1], $userIds[$i], lseedTime('-' . (12 - $a) . ' days')]);
            }
        }
    }

    for ($i = 11; $i <= 200; $i++) {
        $solved = 20 + (($i * 7) % 90);
        $accuracy = 48 + (($i * 5) % 47);
        $existing = lseedOne($pdo, 'SELECT id FROM user_practice_stats WHERE user_id=? LIMIT 1', [$userIds[$i]]);
        if ($existing) {
            $stmt = $pdo->prepare('UPDATE user_practice_stats SET total_solved=?, accuracy=?, streak_days=?, rating=?, last_active_date=?, updated_at=? WHERE id=?');
            $stmt->execute([$solved, $accuracy, $i % 21, 1050 + (($i * 11) % 900), date('Y-m-d', strtotime(LARGE_SEED_NOW . ' -' . ($i % 8) . ' days')), LARGE_SEED_NOW, (int)$existing]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO user_practice_stats (user_id,total_solved,accuracy,streak_days,rating,last_active_date,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?)');
            $stmt->execute([$userIds[$i], $solved, $accuracy, $i % 21, 1050 + (($i * 11) % 900), date('Y-m-d', strtotime(LARGE_SEED_NOW . ' -' . ($i % 8) . ' days')), LARGE_SEED_NOW, LARGE_SEED_NOW]);
        }
    }

    $pdo->exec('UPDATE problems p SET total_submissions = (SELECT COUNT(*) FROM submissions s WHERE s.problem_id = p.id), total_accepted = (SELECT COUNT(*) FROM submissions s WHERE s.problem_id = p.id AND s.status = "Accepted")');
    $pdo->commit();

    echo "Large dataset seeded successfully.\n";
    echo "Seed users: " . lseedOne($pdo, "SELECT COUNT(*) FROM users WHERE email LIKE 'loadtest_user_%@seed.codearena.test'") . "\n";
    echo "Seed organizations: " . lseedOne($pdo, "SELECT COUNT(*) FROM organizations WHERE name IN ('Atlas University Programming Club','Nexus Algorithms Society','BluePeak Engineering Academy','Orbit Competitive Coding Lab','Vertex Developer Community','Summit Tech Interview Hub','Nova Campus Code League','Pioneer Data Structures Guild','Catalyst Software Challenge Team','Riverside Programming Network')") . "\n";
    echo "Seed contests created/updated: {$contestCount}\n";
    echo "Total submissions now: " . lseedOne($pdo, 'SELECT COUNT(*) FROM submissions') . "\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'Large seed failed: ' . $e->getMessage() . "\n");
    exit(1);
}
