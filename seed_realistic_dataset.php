<?php
// Realistic CodeArena dataset seeder.
// Safe to rerun: uses deterministic emails/slugs and checks before inserting duplicate activity rows.
require_once __DIR__ . '/config/db.php';

date_default_timezone_set('Asia/Dhaka');
const CODEARENA_SEED_NOW = '2026-06-16 06:43:35';

function seedSlug(string $text): string {
    return strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $text), '-')) ?: 'item';
}

function seedTime(string $modifier): string {
    return date('Y-m-d H:i:s', strtotime($modifier, strtotime(CODEARENA_SEED_NOW)));
}

function seedOne(PDO $pdo, string $sql, array $params = [], $default = null) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $value = $stmt->fetchColumn();
    return $value === false ? $default : $value;
}

function seedRows(PDO $pdo, string $sql, array $params = []): array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll() ?: [];
}

function seedUser(PDO $pdo, array $user): int {
    $existing = seedOne($pdo, 'SELECT id FROM users WHERE email = ? LIMIT 1', [$user['email']]);
    $password = password_hash($user['password'] ?? 'CodeArena@2026', PASSWORD_DEFAULT);
    if ($existing) {
        $stmt = $pdo->prepare(
            'UPDATE users
             SET name = ?, username = ?, role = ?, country = ?, university = ?, profile_completed = 1,
                 skill_rating = ?, skill_mode = ?, contest_rating = ?, hardcore_rating = ?, learning_rating = ?,
                 is_blocked = 0, is_deleted = 0
             WHERE id = ?'
        );
        $stmt->execute([
            $user['name'], $user['username'], $user['role'], $user['country'], $user['university'],
            $user['skill_rating'], $user['skill_mode'], $user['contest_rating'],
            $user['skill_rating'], max(1000, $user['skill_rating'] - 70), (int)$existing,
        ]);
        return (int)$existing;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO users
            (name, username, email, password, role, country, university, profile_completed, skill_rating, skill_mode, contest_rating, hardcore_rating, learning_rating, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $user['name'], $user['username'], $user['email'], $password, $user['role'], $user['country'], $user['university'],
        $user['skill_rating'], $user['skill_mode'], $user['contest_rating'], $user['skill_rating'], max(1000, $user['skill_rating'] - 70), $user['created_at'],
    ]);
    return (int)$pdo->lastInsertId();
}

function seedOrganization(PDO $pdo, array $org, int $ownerId): int {
    $existing = seedOne($pdo, 'SELECT id FROM organizations WHERE name = ? LIMIT 1', [$org['name']]);
    if ($existing) {
        $stmt = $pdo->prepare(
            'UPDATE organizations
             SET type = ?, description = ?, logo = ?, owner_id = ?, status = "active", is_deleted = 0
             WHERE id = ?'
        );
        $stmt->execute([$org['type'], $org['description'], $org['logo'], $ownerId, (int)$existing]);
        return (int)$existing;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO organizations (name, type, description, logo, owner_id, created_at, status, is_deleted)
         VALUES (?, ?, ?, ?, ?, ?, "active", 0)'
    );
    $stmt->execute([$org['name'], $org['type'], $org['description'], $org['logo'], $ownerId, $org['created_at']]);
    return (int)$pdo->lastInsertId();
}

function seedMembership(PDO $pdo, int $orgId, int $userId, string $role, string $joinedAt): void {
    $existing = seedOne($pdo, 'SELECT id FROM organization_members WHERE org_id = ? AND user_id = ? LIMIT 1', [$orgId, $userId]);
    if ($existing) {
        $stmt = $pdo->prepare('UPDATE organization_members SET role = ?, joined_at = ? WHERE id = ?');
        $stmt->execute([$role, $joinedAt, (int)$existing]);
        return;
    }
    $stmt = $pdo->prepare('INSERT INTO organization_members (org_id, user_id, role, created_at, joined_at) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$orgId, $userId, $role, $joinedAt, $joinedAt]);
}

function seedOrgProblem(PDO $pdo, int $orgId, int $creatorId, int $platformProblemId, array $problem): int {
    $slug = 'seed-' . $orgId . '-' . seedSlug($problem['title']);
    $existing = seedOne($pdo, 'SELECT id FROM org_problems WHERE org_id = ? AND slug = ? LIMIT 1', [$orgId, $slug]);
    $tests = json_encode($problem['tests'], JSON_UNESCAPED_SLASHES);
    if ($existing) {
        $stmt = $pdo->prepare(
            'UPDATE org_problems
             SET platform_problem_id = ?, title = ?, description = ?, difficulty = ?, tags = ?, input_format = ?, output_format = ?,
                 constraints = ?, sample_input = ?, sample_output = ?, test_cases = ?, hint_tier1 = ?, hint_tier2 = ?, hint_tier3 = ?,
                 time_limit_ms = ?, created_by = ?, is_deleted = 0
             WHERE id = ?'
        );
        $stmt->execute([
            $platformProblemId, $problem['title'], $problem['description'], $problem['difficulty'], $problem['tags'],
            $problem['input_format'], $problem['output_format'], $problem['constraints'], $problem['sample_input'],
            $problem['sample_output'], $tests, $problem['hint1'], $problem['hint2'], $problem['hint3'],
            $problem['time_limit_ms'], $creatorId, (int)$existing,
        ]);
        return (int)$existing;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO org_problems
            (org_id, platform_problem_id, title, slug, description, difficulty, tags, input_format, output_format, constraints,
             sample_input, sample_output, test_cases, hint_tier1, hint_tier2, hint_tier3, time_limit_ms, created_by, created_at, is_deleted)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)'
    );
    $stmt->execute([
        $orgId, $platformProblemId, $problem['title'], $slug, $problem['description'], $problem['difficulty'], $problem['tags'],
        $problem['input_format'], $problem['output_format'], $problem['constraints'], $problem['sample_input'],
        $problem['sample_output'], $tests, $problem['hint1'], $problem['hint2'], $problem['hint3'],
        $problem['time_limit_ms'], $creatorId, $problem['created_at'],
    ]);
    return (int)$pdo->lastInsertId();
}

function seedContest(PDO $pdo, int $orgId, int $creatorId, array $contest): int {
    $slug = 'seed-' . $orgId . '-' . seedSlug($contest['title']);
    $existing = seedOne($pdo, 'SELECT id FROM contests WHERE slug = ? LIMIT 1', [$slug]);
    if ($existing) {
        $stmt = $pdo->prepare(
            'UPDATE contests
             SET org_id = ?, title = ?, description = ?, start_time = ?, end_time = ?, created_by = ?, is_rated = ?,
                 status = ?, org_status = ?, is_published = ?, visibility = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $orgId, $contest['title'], $contest['description'], $contest['start_time'], $contest['end_time'], $creatorId,
            $contest['is_rated'], $contest['status'], $contest['org_status'], $contest['is_published'], $contest['visibility'],
            (int)$existing,
        ]);
        return (int)$existing;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO contests
            (org_id, title, slug, description, start_time, end_time, created_by, is_rated, status, org_status, is_published, visibility, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $orgId, $contest['title'], $slug, $contest['description'], $contest['start_time'], $contest['end_time'],
        $creatorId, $contest['is_rated'], $contest['status'], $contest['org_status'], $contest['is_published'],
        $contest['visibility'], $contest['created_at'],
    ]);
    return (int)$pdo->lastInsertId();
}

function seedContestProblem(PDO $pdo, int $contestId, int $problemId, ?int $orgProblemId, int $orderIndex, int $points): void {
    $existing = seedOne(
        $pdo,
        'SELECT id FROM contest_problems WHERE contest_id = ? AND problem_id = ? AND ((org_problem_id = ?) OR (org_problem_id IS NULL AND ? IS NULL)) LIMIT 1',
        [$contestId, $problemId, $orgProblemId, $orgProblemId]
    );
    if ($existing) {
        $stmt = $pdo->prepare('UPDATE contest_problems SET points = ?, order_index = ? WHERE id = ?');
        $stmt->execute([$points, $orderIndex, (int)$existing]);
        return;
    }
    $stmt = $pdo->prepare('INSERT INTO contest_problems (contest_id, problem_id, org_problem_id, points, order_index) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$contestId, $problemId, $orgProblemId, $points, $orderIndex]);
}

function seedParticipant(PDO $pdo, int $contestId, int $orgId, int $userId, int $score, int $penalty, string $status, string $registeredAt): void {
    $existing = seedOne($pdo, 'SELECT id FROM contest_participants WHERE contest_id = ? AND user_id = ? LIMIT 1', [$contestId, $userId]);
    if ($existing) {
        $stmt = $pdo->prepare('UPDATE contest_participants SET org_id = ?, score = ?, penalty_minutes = ?, status = ?, registered_at = ? WHERE id = ?');
        $stmt->execute([$orgId, $score, $penalty, $status, $registeredAt, (int)$existing]);
        return;
    }
    $stmt = $pdo->prepare('INSERT INTO contest_participants (contest_id, org_id, user_id, score, penalty_minutes, registered_at, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$contestId, $orgId, $userId, $score, $penalty, $registeredAt, $status]);
}

function seedSubmission(PDO $pdo, array $sub): void {
    $exists = seedOne(
        $pdo,
        'SELECT id FROM submissions WHERE user_id = ? AND problem_id = ? AND ((contest_id = ?) OR (contest_id IS NULL AND ? IS NULL)) AND submitted_at = ? LIMIT 1',
        [$sub['user_id'], $sub['problem_id'], $sub['contest_id'], $sub['contest_id'], $sub['submitted_at']]
    );
    if ($exists) return;
    $stmt = $pdo->prepare(
        'INSERT INTO submissions
            (user_id, problem_id, code, language, status, score, hints_used, contest_id, org_id, runtime_ms, memory_kb, test_results, submitted_at, is_practice)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $sub['user_id'], $sub['problem_id'], $sub['code'], $sub['language'], $sub['status'], $sub['score'], $sub['hints_used'],
        $sub['contest_id'], $sub['org_id'], $sub['runtime_ms'], $sub['memory_kb'], $sub['test_results'], $sub['submitted_at'], $sub['is_practice'],
    ]);
}

function seedSimpleUnique(PDO $pdo, string $selectSql, array $selectParams, string $insertSql, array $insertParams): void {
    if (seedOne($pdo, $selectSql, $selectParams)) return;
    $stmt = $pdo->prepare($insertSql);
    $stmt->execute($insertParams);
}

$users = [
    ['name' => 'Avery Chen', 'username' => 'avery_admin', 'email' => 'avery.admin@seed.codearena.test', 'role' => 'admin', 'country' => 'Singapore', 'university' => 'CodeArena HQ', 'skill_rating' => 1800, 'contest_rating' => 1760, 'skill_mode' => 'hardcore', 'created_at' => '2025-01-12 09:10:00'],
    ['name' => 'Maya Rahman', 'username' => 'maya_northbridge', 'email' => 'maya.rahman@seed.codearena.test', 'role' => 'org_admin', 'country' => 'Bangladesh', 'university' => 'Northbridge University', 'skill_rating' => 1660, 'contest_rating' => 1715, 'skill_mode' => 'hardcore', 'created_at' => '2025-02-02 10:20:00'],
    ['name' => 'Rafiq Karim', 'username' => 'rafiq_devclub', 'email' => 'rafiq.karim@seed.codearena.test', 'role' => 'org_admin', 'country' => 'Bangladesh', 'university' => 'Dhaka Dev Circle', 'skill_rating' => 1580, 'contest_rating' => 1638, 'skill_mode' => 'learning', 'created_at' => '2025-02-16 14:30:00'],
    ['name' => 'Nadia Torres', 'username' => 'nadia_quantum', 'email' => 'nadia.torres@seed.codearena.test', 'role' => 'org_admin', 'country' => 'United States', 'university' => 'QuantumWorks Labs', 'skill_rating' => 1720, 'contest_rating' => 1810, 'skill_mode' => 'hardcore', 'created_at' => '2025-03-03 08:30:00'],
    ['name' => 'Tanvir Hasan', 'username' => 'tanvir_dp', 'email' => 'tanvir.hasan@seed.codearena.test', 'role' => 'user', 'country' => 'Bangladesh', 'university' => 'BUET', 'skill_rating' => 1542, 'contest_rating' => 1510, 'skill_mode' => 'hardcore', 'created_at' => '2025-03-12 21:00:00'],
    ['name' => 'Sara Kim', 'username' => 'sara_graphs', 'email' => 'sara.kim@seed.codearena.test', 'role' => 'user', 'country' => 'South Korea', 'university' => 'SNU', 'skill_rating' => 1688, 'contest_rating' => 1702, 'skill_mode' => 'hardcore', 'created_at' => '2025-04-01 11:18:00'],
    ['name' => 'Omar Faruk', 'username' => 'omar_binary', 'email' => 'omar.faruk@seed.codearena.test', 'role' => 'user', 'country' => 'Bangladesh', 'university' => 'Northbridge University', 'skill_rating' => 1315, 'contest_rating' => 1270, 'skill_mode' => 'learning', 'created_at' => '2025-04-22 16:00:00'],
    ['name' => 'Leah Martin', 'username' => 'leah_strings', 'email' => 'leah.martin@seed.codearena.test', 'role' => 'user', 'country' => 'Canada', 'university' => 'Waterloo', 'skill_rating' => 1468, 'contest_rating' => 1492, 'skill_mode' => 'learning', 'created_at' => '2025-05-09 13:10:00'],
    ['name' => 'Ibrahim Nasser', 'username' => 'ibrahim_math', 'email' => 'ibrahim.nasser@seed.codearena.test', 'role' => 'user', 'country' => 'Egypt', 'university' => 'Cairo University', 'skill_rating' => 1604, 'contest_rating' => 1594, 'skill_mode' => 'hardcore', 'created_at' => '2025-05-14 09:44:00'],
    ['name' => 'Priya Saha', 'username' => 'priya_segment', 'email' => 'priya.saha@seed.codearena.test', 'role' => 'user', 'country' => 'India', 'university' => 'IIT Kharagpur', 'skill_rating' => 1778, 'contest_rating' => 1832, 'skill_mode' => 'hardcore', 'created_at' => '2025-06-01 19:21:00'],
    ['name' => 'Marcus Lee', 'username' => 'marcus_greedy', 'email' => 'marcus.lee@seed.codearena.test', 'role' => 'user', 'country' => 'United States', 'university' => 'MIT', 'skill_rating' => 1392, 'contest_rating' => 1360, 'skill_mode' => 'learning', 'created_at' => '2025-06-25 15:19:00'],
    ['name' => 'Ayesha Chowdhury', 'username' => 'ayesha_trie', 'email' => 'ayesha.chowdhury@seed.codearena.test', 'role' => 'user', 'country' => 'Bangladesh', 'university' => 'DU', 'skill_rating' => 1498, 'contest_rating' => 1440, 'skill_mode' => 'learning', 'created_at' => '2025-07-10 20:00:00'],
    ['name' => 'Daniel Novak', 'username' => 'daniel_flow', 'email' => 'daniel.novak@seed.codearena.test', 'role' => 'user', 'country' => 'Germany', 'university' => 'TU Munich', 'skill_rating' => 1864, 'contest_rating' => 1905, 'skill_mode' => 'hardcore', 'created_at' => '2025-08-07 10:10:00'],
    ['name' => 'Mei Lin', 'username' => 'mei_heap', 'email' => 'mei.lin@seed.codearena.test', 'role' => 'user', 'country' => 'China', 'university' => 'Tsinghua', 'skill_rating' => 1578, 'contest_rating' => 1615, 'skill_mode' => 'hardcore', 'created_at' => '2025-09-01 08:00:00'],
    ['name' => 'Carlos Rivera', 'username' => 'carlos_bits', 'email' => 'carlos.rivera@seed.codearena.test', 'role' => 'user', 'country' => 'Mexico', 'university' => 'UNAM', 'skill_rating' => 1245, 'contest_rating' => 1210, 'skill_mode' => 'learning', 'created_at' => '2025-10-12 18:35:00'],
    ['name' => 'Fatima Noor', 'username' => 'fatima_bfs', 'email' => 'fatima.noor@seed.codearena.test', 'role' => 'user', 'country' => 'Pakistan', 'university' => 'LUMS', 'skill_rating' => 1434, 'contest_rating' => 1461, 'skill_mode' => 'learning', 'created_at' => '2025-11-03 17:45:00'],
    ['name' => 'Jonas Meyer', 'username' => 'jonas_tree', 'email' => 'jonas.meyer@seed.codearena.test', 'role' => 'user', 'country' => 'Germany', 'university' => 'RWTH Aachen', 'skill_rating' => 1512, 'contest_rating' => 1548, 'skill_mode' => 'hardcore', 'created_at' => '2025-11-20 12:10:00'],
    ['name' => 'Elena Petrova', 'username' => 'elena_geometry', 'email' => 'elena.petrova@seed.codearena.test', 'role' => 'user', 'country' => 'Poland', 'university' => 'Warsaw University', 'skill_rating' => 1652, 'contest_rating' => 1688, 'skill_mode' => 'hardcore', 'created_at' => '2025-12-05 08:50:00'],
];

$pdo->beginTransaction();

try {
    $userIds = [];
    foreach ($users as $user) {
        $userIds[$user['username']] = seedUser($pdo, $user);
    }

    $organizations = [
        'northbridge' => [
            'name' => 'Northbridge Coding Guild',
            'type' => 'university',
            'owner' => 'maya_northbridge',
            'description' => 'University programming guild hosting weekly algorithm rounds, onboarding bootcamps, and rated team contests.',
            'logo' => '',
            'created_at' => '2025-02-05 11:00:00',
            'members' => ['maya_northbridge' => 'org_owner', 'omar_binary' => 'org_member', 'tanvir_dp' => 'org_admin', 'ayesha_trie' => 'org_member', 'fatima_bfs' => 'org_member', 'jonas_tree' => 'org_member'],
        ],
        'dhaka-dev-circle' => [
            'name' => 'Dhaka Dev Circle',
            'type' => 'community',
            'owner' => 'rafiq_devclub',
            'description' => 'Community-led practice group for interview prep, university contests, and beginner-friendly problem solving.',
            'logo' => '',
            'created_at' => '2025-03-01 12:00:00',
            'members' => ['rafiq_devclub' => 'org_owner', 'tanvir_dp' => 'org_member', 'leah_strings' => 'org_member', 'ibrahim_math' => 'org_admin', 'carlos_bits' => 'org_member', 'mei_heap' => 'org_member'],
        ],
        'quantumworks' => [
            'name' => 'QuantumWorks Engineering',
            'type' => 'company',
            'owner' => 'nadia_quantum',
            'description' => 'Engineering hiring workspace for technical assessments, internal ranking rounds, and onboarding skill checks.',
            'logo' => '',
            'created_at' => '2025-04-15 10:15:00',
            'members' => ['nadia_quantum' => 'org_owner', 'sara_graphs' => 'org_admin', 'priya_segment' => 'org_member', 'daniel_flow' => 'org_member', 'elena_geometry' => 'org_member', 'marcus_greedy' => 'org_member'],
        ],
    ];

    $orgIds = [];
    foreach ($organizations as $key => $org) {
        $orgId = seedOrganization($pdo, $org, $userIds[$org['owner']]);
        $orgIds[$key] = $orgId;
        foreach ($org['members'] as $username => $role) {
            seedMembership($pdo, $orgId, $userIds[$username], $role, date('Y-m-d H:i:s', strtotime($org['created_at'] . ' +' . (array_search($username, array_keys($org['members']), true) * 5) . ' days')));
            $pdo->prepare('UPDATE users SET org_id = ? WHERE id = ? AND role != "admin"')->execute([$orgId, $userIds[$username]]);
        }
    }

    $platformProblems = seedRows($pdo, 'SELECT id, title, difficulty, tags FROM problems WHERE is_public = 1 AND COALESCE(is_deleted, 0) = 0 ORDER BY id ASC LIMIT 36');
    if (count($platformProblems) < 12) {
        throw new RuntimeException('Need at least 12 public problems before seeding organization contests.');
    }

    $orgProblemMap = [];
    $problemCursor = 0;
    foreach ($organizations as $key => $org) {
        $orgProblemMap[$key] = [];
        for ($i = 1; $i <= 10; $i++) {
            $platform = $platformProblems[$problemCursor % count($platformProblems)];
            $problemCursor++;
            $title = match ($key) {
                'northbridge' => ['Campus Route Optimizer', 'Dormitory Queue Audit', 'Scholarship DP Planner', 'Library Trie Search', 'Exam Hall Seating', 'Cafeteria Flow Balance', 'Club Budget Knapsack', 'Research Paper Graph', 'Freshers Pairing', 'Lab Sensor Windows'][$i - 1],
                'dhaka-dev-circle' => ['Metro Line Queries', 'Startup Pitch Scheduler', 'Festival Ticket Greedy', 'River Crossing BFS', 'API Rate Window', 'Marketplace Matching', 'Cricket Score Segment', 'Courier Route Graph', 'Code Review Diff', 'Community Rank Merge'][$i - 1],
                default => ['Telemetry Spike Detector', 'Service Dependency Graph', 'Feature Flag Rollout', 'Incident Timeline Sort', 'Shard Load Balancer', 'Cache Eviction Game', 'Pipeline DAG Recovery', 'Latency Percentile Query', 'Hiring Round Matrix', 'Repository Ownership Tree'][$i - 1],
            };
            $orgProblemId = seedOrgProblem($pdo, $orgIds[$key], $userIds[$org['owner']], (int)$platform['id'], [
                'title' => $title,
                'description' => 'Solve a realistic ' . strtolower($platform['difficulty']) . ' level scenario inspired by ' . $org['name'] . '. The input is designed to test clean implementation, edge cases, and efficient reasoning.',
                'difficulty' => $i <= 3 ? 'Easy' : ($i <= 7 ? 'Medium' : 'Hard'),
                'tags' => $platform['tags'] ?: 'arrays,implementation',
                'input_format' => 'The first line contains the primary input size. The next lines contain the dataset described in the statement.',
                'output_format' => 'Print the requested answer exactly as described.',
                'constraints' => '1 <= n <= 200000. Use O(n log n) or better where possible.',
                'sample_input' => "5\n1 3 5 7 9",
                'sample_output' => '25',
                'tests' => [['input' => "5\n1 3 5 7 9", 'output' => '25'], ['input' => "3\n2 4 8", 'output' => '14']],
                'hint1' => 'Start by identifying the invariant in the sample cases.',
                'hint2' => 'Sort or precompute prefix values if repeated queries appear.',
                'hint3' => 'Watch for empty ranges and boundary indices.',
                'time_limit_ms' => $i >= 8 ? 3000 : 2000,
                'created_at' => date('Y-m-d H:i:s', strtotime('2025-05-01 +' . ($i * 4) . ' days')),
            ]);
            $orgProblemMap[$key][] = ['org_problem_id' => $orgProblemId, 'problem_id' => (int)$platform['id']];
        }
    }

    $contestTemplates = [
        'northbridge' => [
            ['title' => 'Northbridge Weekly Sprint 18', 'status' => 'ended', 'org_status' => 'ended', 'offsetStart' => '-48 days', 'durationHours' => 2, 'visibility' => 'public'],
            ['title' => 'Northbridge Freshers Practice Cup', 'status' => 'active', 'org_status' => 'live', 'offsetStart' => '-45 minutes', 'durationHours' => 3, 'visibility' => 'org'],
            ['title' => 'Northbridge Graph Theory Invitational', 'status' => 'upcoming', 'org_status' => 'scheduled', 'offsetStart' => '+8 days', 'durationHours' => 2, 'visibility' => 'public'],
        ],
        'dhaka-dev-circle' => [
            ['title' => 'Dhaka Dev Monthly Challenge', 'status' => 'ended', 'org_status' => 'ended', 'offsetStart' => '-32 days', 'durationHours' => 2, 'visibility' => 'public'],
            ['title' => 'Community Interview Drill', 'status' => 'upcoming', 'org_status' => 'scheduled', 'offsetStart' => '+4 days', 'durationHours' => 2, 'visibility' => 'public'],
            ['title' => 'Beginner Friendly DP Night', 'status' => 'upcoming', 'org_status' => 'scheduled', 'offsetStart' => '+14 days', 'durationHours' => 2, 'visibility' => 'org'],
        ],
        'quantumworks' => [
            ['title' => 'QuantumWorks Backend Hiring Round', 'status' => 'ended', 'org_status' => 'ended', 'offsetStart' => '-21 days', 'durationHours' => 3, 'visibility' => 'org'],
            ['title' => 'QuantumWorks Reliability Cup', 'status' => 'active', 'org_status' => 'live', 'offsetStart' => '-70 minutes', 'durationHours' => 4, 'visibility' => 'public'],
            ['title' => 'QuantumWorks Intern Screening', 'status' => 'upcoming', 'org_status' => 'scheduled', 'offsetStart' => '+11 days', 'durationHours' => 3, 'visibility' => 'org'],
        ],
    ];

    $contestIds = [];
    foreach ($contestTemplates as $key => $templates) {
        foreach ($templates as $index => $template) {
            $start = seedTime($template['offsetStart']);
            $end = seedTime($template['offsetStart'] . ' +' . $template['durationHours'] . ' hours');
            $contestId = seedContest($pdo, $orgIds[$key], $userIds[$organizations[$key]['owner']], [
                'title' => $template['title'],
                'description' => 'A production-style contest by ' . $organizations[$key]['name'] . ' with curated problems, live standings, and realistic participation analytics.',
                'start_time' => $start,
                'end_time' => $end,
                'is_rated' => 1,
                'status' => $template['status'],
                'org_status' => $template['org_status'],
                'is_published' => 1,
                'visibility' => $template['visibility'],
                'created_at' => date('Y-m-d H:i:s', strtotime($start . ' -10 days')),
            ]);
            $contestIds[] = $contestId;

            $problemSet = array_slice($orgProblemMap[$key], $index * 3, 4);
            if (count($problemSet) < 4) $problemSet = array_slice($orgProblemMap[$key], 0, 4);
            foreach ($problemSet as $order => $problem) {
                seedContestProblem($pdo, $contestId, $problem['problem_id'], $problem['org_problem_id'], $order + 1, 100 + ($order * 100));
            }
        }
    }

    $studentUsernames = array_values(array_filter(array_keys($userIds), fn($u) => !str_contains($u, 'admin') && !in_array($u, ['maya_northbridge', 'rafiq_devclub', 'nadia_quantum'], true)));
    $languages = ['cpp', 'python', 'javascript', 'java'];
    $verdicts = ['Accepted', 'Accepted', 'Accepted', 'Wrong Answer', 'Time Limit Exceeded', 'Runtime Error'];

    foreach ($contestIds as $contestIndex => $contestId) {
        $contest = seedRows($pdo, 'SELECT org_id, start_time, status FROM contests WHERE id = ?', [$contestId])[0];
        $contestProblems = seedRows($pdo, 'SELECT problem_id, points FROM contest_problems WHERE contest_id = ? ORDER BY order_index ASC', [$contestId]);
        foreach ($studentUsernames as $uIndex => $username) {
            if (($uIndex + $contestIndex) % 5 === 0) continue;
            $userId = $userIds[$username];
            $baseScore = 0;
            $penalty = 25 + (($uIndex * 13 + $contestIndex * 17) % 160);
            $registeredAt = date('Y-m-d H:i:s', strtotime($contest['start_time'] . ' -' . (2 + $uIndex) . ' days'));
            foreach ($contestProblems as $pIndex => $cp) {
                $attempts = 1 + (($uIndex + $pIndex + $contestIndex) % 3);
                for ($a = 0; $a < $attempts; $a++) {
                    $acceptedBias = ($uIndex + $pIndex + $contestIndex + $a) % 6;
                    $status = $a === $attempts - 1 && $acceptedBias !== 4 ? 'Accepted' : $verdicts[($uIndex + $pIndex + $a) % count($verdicts)];
                    $score = $status === 'Accepted' ? (int)$cp['points'] : 0;
                    if ($status === 'Accepted') $baseScore += $score;
                    seedSubmission($pdo, [
                        'user_id' => $userId,
                        'problem_id' => (int)$cp['problem_id'],
                        'code' => "// Seeded realistic submission\nint main(){return 0;}",
                        'language' => $languages[($uIndex + $pIndex + $a) % count($languages)],
                        'status' => $status,
                        'score' => $score,
                        'hints_used' => ($status === 'Accepted' ? (($uIndex + $pIndex) % 2) : (($uIndex + $pIndex) % 3)),
                        'contest_id' => $contestId,
                        'org_id' => (int)$contest['org_id'],
                        'runtime_ms' => 32 + (($uIndex * 19 + $pIndex * 37 + $a * 11) % 860),
                        'memory_kb' => 14000 + (($uIndex * 977 + $pIndex * 641) % 88000),
                        'test_results' => json_encode(['passed' => $status === 'Accepted' ? 12 : 7, 'total' => 12, 'source' => 'realistic-seed']),
                        'submitted_at' => date('Y-m-d H:i:s', strtotime($contest['start_time'] . ' +' . (12 + $uIndex * 7 + $pIndex * 13 + $a * 9) . ' minutes')),
                        'is_practice' => 0,
                    ]);
                }
            }
            seedParticipant($pdo, $contestId, (int)$contest['org_id'], $userId, min($baseScore, 1000), $penalty, 'approved', $registeredAt);
        }

        $participants = seedRows($pdo, 'SELECT user_id, score, penalty_minutes FROM contest_participants WHERE contest_id = ? ORDER BY score DESC, penalty_minutes ASC', [$contestId]);
        $rank = 1;
        foreach ($participants as $row) {
            seedSimpleUnique(
                $pdo,
                'SELECT id FROM contest_leaderboard WHERE contest_id = ? AND user_id = ? LIMIT 1',
                [$contestId, (int)$row['user_id']],
                'INSERT INTO contest_leaderboard (contest_id, user_id, rank, score, penalty, solved_count, rating_change, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$contestId, (int)$row['user_id'], $rank, (int)$row['score'], (int)$row['penalty_minutes'], (int)floor((int)$row['score'] / 100), max(-45, 60 - ($rank * 7)), date('Y-m-d H:i:s')]
            );
            seedSimpleUnique(
                $pdo,
                'SELECT id FROM user_rating_history WHERE contest_id = ? AND user_id = ? LIMIT 1',
                [$contestId, (int)$row['user_id']],
                'INSERT INTO user_rating_history (user_id, contest_id, old_rating, new_rating, rating_change, rank, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [(int)$row['user_id'], $contestId, 1200 + ($rank * 22), 1200 + ($rank * 22) + max(-45, 60 - ($rank * 7)), max(-45, 60 - ($rank * 7)), $rank, date('Y-m-d H:i:s')]
            );
            $rank++;
        }
    }

    foreach ($studentUsernames as $idx => $username) {
        $userId = $userIds[$username];
        $accepted = 24 + (($idx * 7) % 56);
        $accuracy = 54 + (($idx * 5) % 38);
        seedSimpleUnique(
            $pdo,
            'SELECT id FROM user_practice_stats WHERE user_id = ? LIMIT 1',
            [$userId],
            'INSERT INTO user_practice_stats (user_id, total_solved, accuracy, streak_days, rating, last_active_date, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$userId, $accepted, $accuracy, 2 + ($idx % 18), 1180 + ($idx * 37), date('Y-m-d', strtotime('-' . ($idx % 5) . ' days')), date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]
        );
        for ($d = 1; $d <= min(12, 4 + $idx); $d++) {
            seedSimpleUnique(
                $pdo,
                'SELECT id FROM roadmap_progress WHERE user_id = ? AND day = ? LIMIT 1',
                [$userId, $d],
                'INSERT INTO roadmap_progress (user_id, day, completed_at) VALUES (?, ?, ?)',
                [$userId, $d, date('Y-m-d H:i:s', strtotime('-' . (20 - $d) . ' days'))]
            );
        }
        foreach (array_slice($platformProblems, $idx % 8, 3) as $bookmark) {
            seedSimpleUnique(
                $pdo,
                'SELECT id FROM problem_bookmarks WHERE user_id = ? AND problem_id = ? LIMIT 1',
                [$userId, (int)$bookmark['id']],
                'INSERT INTO problem_bookmarks (user_id, problem_id, created_at) VALUES (?, ?, ?)',
                [$userId, (int)$bookmark['id'], date('Y-m-d H:i:s', strtotime('-' . (3 + $idx) . ' days'))]
            );
        }
    }

    $announcementTemplates = [
        ['target_type' => 'global', 'org' => null, 'contest' => null, 'title' => 'CodeArena Summer Ranking Season is live', 'message' => 'Rated contests now contribute to contest rating, and organization contests appear in the unified contest feed.', 'type' => 'announcement'],
        ['target_type' => 'org', 'org' => 'northbridge', 'contest' => null, 'title' => 'Northbridge practice room opens this week', 'message' => 'Members should complete the warmup set before the Graph Theory Invitational.', 'type' => 'instruction'],
        ['target_type' => 'org', 'org' => 'dhaka-dev-circle', 'contest' => null, 'title' => 'Community mentors added for DP Night', 'message' => 'Mentors will review wrong-answer patterns after the contest ends.', 'type' => 'announcement'],
        ['target_type' => 'org', 'org' => 'quantumworks', 'contest' => null, 'title' => 'Reliability Cup clarification policy', 'message' => 'Clarifications will be answered publicly when they affect multiple participants.', 'type' => 'clarification'],
    ];
    foreach ($announcementTemplates as $idx => $item) {
        $orgId = $item['org'] ? $orgIds[$item['org']] : null;
        seedSimpleUnique(
            $pdo,
            'SELECT id FROM announcements WHERE title = ? AND target_type = ? LIMIT 1',
            [$item['title'], $item['target_type']],
            'INSERT INTO announcements (org_id, contest_id, title, message, target_type, type, is_published, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)',
            [$orgId, null, $item['title'], $item['message'], $item['target_type'], $item['type'], $userIds['avery_admin'], date('Y-m-d H:i:s', strtotime('-' . (6 - $idx) . ' days'))]
        );
    }

    $discussionTemplates = [
        ['user' => 'tanvir_dp', 'problemIndex' => 0, 'title' => 'How do you decide between prefix sums and segment trees?', 'category' => 'General', 'tags' => 'prefix-sum,segment-tree'],
        ['user' => 'sara_graphs', 'problemIndex' => 4, 'title' => 'BFS state compression patterns for grid contests', 'category' => 'Contest', 'tags' => 'bfs,graphs'],
        ['user' => 'priya_segment', 'problemIndex' => 8, 'title' => 'My checklist before submitting hard problems', 'category' => 'Interview', 'tags' => 'debugging,hard'],
        ['user' => 'leah_strings', 'problemIndex' => 2, 'title' => 'String hashing collision handling in practice', 'category' => 'General', 'tags' => 'strings,hashing'],
    ];
    foreach ($discussionTemplates as $idx => $post) {
        $problem = $platformProblems[$post['problemIndex']];
        $postId = seedOne($pdo, 'SELECT id FROM discuss_posts WHERE title = ? LIMIT 1', [$post['title']]);
        if (!$postId) {
            $stmt = $pdo->prepare(
                'INSERT INTO discuss_posts (user_id, problem_id, title, content, category, tags, is_pinned, views, upvotes, downvotes, comment_count, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 2, ?)'
            );
            $stmt->execute([
                $userIds[$post['user']], (int)$problem['id'], $post['title'],
                'Sharing a real contest observation from recent CodeArena practice. What tradeoffs do you use under time pressure?',
                $post['category'], $post['tags'], $idx === 0 ? 1 : 0, 120 + ($idx * 47), 14 + ($idx * 5),
                date('Y-m-d H:i:s', strtotime('-' . (12 - $idx) . ' days')),
            ]);
            $postId = (int)$pdo->lastInsertId();
        }
        foreach (['Great breakdown. I usually start with constraints first.', 'The edge cases around empty ranges are what got me last time.'] as $cIdx => $comment) {
            seedSimpleUnique(
                $pdo,
                'SELECT id FROM discuss_comments WHERE post_id = ? AND content = ? LIMIT 1',
                [(int)$postId, $comment],
                'INSERT INTO discuss_comments (post_id, user_id, parent_id, content, upvotes, downvotes, created_at) VALUES (?, ?, NULL, ?, ?, 0, ?)',
                [(int)$postId, $userIds[$studentUsernames[($idx + $cIdx + 2) % count($studentUsernames)]], $comment, 3 + $cIdx, date('Y-m-d H:i:s', strtotime('-' . (10 - $idx) . ' days +' . $cIdx . ' hours'))]
            );
        }
    }

    foreach (array_slice($platformProblems, 0, 8) as $idx => $problem) {
        seedSimpleUnique(
            $pdo,
            'SELECT id FROM problem_editorials WHERE problem_id = ? LIMIT 1',
            [(int)$problem['id']],
            'INSERT INTO problem_editorials (problem_id, approach, complexity, reference_solution, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
            [(int)$problem['id'], 'Use the constraints to select the simplest data structure that preserves the needed invariant. Validate with sample and boundary cases.', 'O(n log n) time, O(n) memory depending on implementation.', '// Reference solution intentionally concise for seeded editorial.', date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]
        );
    }

    $pdo->prepare('UPDATE problems p SET total_submissions = (SELECT COUNT(*) FROM submissions s WHERE s.problem_id = p.id), total_accepted = (SELECT COUNT(*) FROM submissions s WHERE s.problem_id = p.id AND s.status = "Accepted")')->execute();

    $pdo->commit();

    echo "Realistic dataset seeded successfully.\n";
    echo "Users: " . seedOne($pdo, 'SELECT COUNT(*) FROM users WHERE email LIKE "%@seed.codearena.test"') . " seeded accounts\n";
    echo "Organizations: " . count($orgIds) . "\n";
    echo "Contests: " . count($contestIds) . "\n";
    echo "Submissions: " . seedOne($pdo, 'SELECT COUNT(*) FROM submissions WHERE code LIKE "// Seeded realistic submission%"') . " seeded submissions\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "Seed failed: " . $e->getMessage() . "\n");
    exit(1);
}
