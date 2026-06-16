<?php
// UIU_CCL presentation-ready organization seed.
// Login: uiuccl@gmail.com / CodeArena@2026
require_once __DIR__ . '/config/db.php';

date_default_timezone_set('Asia/Dhaka');
const UIU_CCL_NOW = '2026-06-16 10:30:00';
const UIU_CCL_PASSWORD = 'CodeArena@2026';

function uiuOne(PDO $pdo, string $sql, array $params = [], $default = null) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $value = $stmt->fetchColumn();
    return $value === false ? $default : $value;
}

function uiuRows(PDO $pdo, string $sql, array $params = []): array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll() ?: [];
}

function uiuSlug(string $text): string {
    return strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $text), '-')) ?: 'item';
}

function uiuTime(string $modifier): string {
    return date('Y-m-d H:i:s', strtotime($modifier, strtotime(UIU_CCL_NOW)));
}

function uiuUser(PDO $pdo, string $name, string $username, string $email, string $role, ?int $orgId, int $rating, int $contestRating, string $createdAt): int {
    $existing = uiuOne($pdo, 'SELECT id FROM users WHERE email = ? LIMIT 1', [$email]);
    $password = password_hash(UIU_CCL_PASSWORD, PASSWORD_DEFAULT);
    if ($existing) {
        $stmt = $pdo->prepare(
            'UPDATE users
             SET name=?, username=?, password=?, role=?, country="Bangladesh", university="United International University",
                 org_id=?, profile_completed=1, skill_rating=?, skill_mode="hardcore", contest_rating=?,
                 hardcore_rating=?, learning_rating=?, is_blocked=0, is_deleted=0
             WHERE id=?'
        );
        $stmt->execute([$name, $username, $password, $role, $orgId, $rating, $contestRating, $rating, max(1000, $rating - 80), (int)$existing]);
        return (int)$existing;
    }
    $stmt = $pdo->prepare(
        'INSERT INTO users
            (name, username, email, password, role, country, university, org_id, profile_completed,
             skill_rating, skill_mode, contest_rating, hardcore_rating, learning_rating, created_at)
         VALUES (?, ?, ?, ?, ?, "Bangladesh", "United International University", ?, 1, ?, "hardcore", ?, ?, ?, ?)'
    );
    $stmt->execute([$name, $username, $email, $password, $role, $orgId, $rating, $contestRating, $rating, max(1000, $rating - 80), $createdAt]);
    return (int)$pdo->lastInsertId();
}

function uiuMember(PDO $pdo, int $orgId, int $userId, string $role, string $joinedAt): void {
    $existing = uiuOne($pdo, 'SELECT id FROM organization_members WHERE org_id=? AND user_id=? LIMIT 1', [$orgId, $userId]);
    if ($existing) {
        $stmt = $pdo->prepare('UPDATE organization_members SET role=?, joined_at=? WHERE id=?');
        $stmt->execute([$role, $joinedAt, (int)$existing]);
        return;
    }
    $stmt = $pdo->prepare('INSERT INTO organization_members (user_id, org_id, role, created_at, joined_at) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $orgId, $role, $joinedAt, $joinedAt]);
}

function uiuOrgProblem(PDO $pdo, int $orgId, int $ownerId, array $platform, array $problem, int $index): int {
    $slug = 'uiu-ccl-' . uiuSlug($problem['title']);
    $existing = uiuOne($pdo, 'SELECT id FROM org_problems WHERE org_id=? AND slug=? LIMIT 1', [$orgId, $slug]);
    $tests = json_encode([
        ['input' => "5\n1 2 3 4 5", 'output' => '15'],
        ['input' => "4\n10 20 30 40", 'output' => '100'],
        ['input' => "1\n7", 'output' => '7'],
    ], JSON_UNESCAPED_SLASHES);
    $params = [
        (int)$platform['id'], $problem['title'], $problem['description'], $problem['difficulty'], $problem['tags'],
        'First line contains n. Second line contains n integers or the dataset described in the statement.',
        'Print the required answer exactly.',
        '1 <= n <= 200000. Use efficient algorithms suitable for contest constraints.',
        "5\n1 2 3 4 5", '15', $tests,
        'Start by reading constraints and identifying the expected complexity.',
        'Try prefix sums, sorting, graph traversal, or dynamic programming depending on the statement.',
        'Check edge cases: n=1, duplicate values, disconnected graphs, and maximum constraints.',
        $problem['difficulty'] === 'Hard' ? 3000 : 2000, $ownerId,
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
    $stmt->execute([$orgId, $params[0], $params[1], $slug, ...array_slice($params, 2), uiuTime('-' . (90 - $index * 4) . ' days')]);
    return (int)$pdo->lastInsertId();
}

function uiuContest(PDO $pdo, int $orgId, int $ownerId, array $contest): int {
    $slug = 'uiu-ccl-' . uiuSlug($contest['title']);
    $existing = uiuOne($pdo, 'SELECT id FROM contests WHERE slug=? LIMIT 1', [$slug]);
    $params = [
        $orgId, $contest['title'], $contest['description'], $contest['start'], $contest['end'], $ownerId, 1,
        $contest['status'], $contest['org_status'], 1, $contest['visibility'],
    ];
    if ($existing) {
        $stmt = $pdo->prepare('UPDATE contests SET org_id=?, title=?, description=?, start_time=?, end_time=?, created_by=?, is_rated=?, status=?, org_status=?, is_published=?, visibility=? WHERE id=?');
        $stmt->execute([...$params, (int)$existing]);
        return (int)$existing;
    }
    $stmt = $pdo->prepare('INSERT INTO contests (org_id,title,slug,description,start_time,end_time,created_by,is_rated,status,org_status,is_published,visibility,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$orgId, $contest['title'], $slug, $contest['description'], $contest['start'], $contest['end'], $ownerId, 1, $contest['status'], $contest['org_status'], 1, $contest['visibility'], uiuTime('-45 days')]);
    return (int)$pdo->lastInsertId();
}

function uiuContestProblem(PDO $pdo, int $contestId, int $problemId, int $orgProblemId, int $order): void {
    $existing = uiuOne($pdo, 'SELECT id FROM contest_problems WHERE contest_id=? AND org_problem_id=? LIMIT 1', [$contestId, $orgProblemId]);
    if ($existing) {
        $stmt = $pdo->prepare('UPDATE contest_problems SET problem_id=?, points=?, order_index=? WHERE id=?');
        $stmt->execute([$problemId, 100 * $order, $order, (int)$existing]);
        return;
    }
    $stmt = $pdo->prepare('INSERT INTO contest_problems (contest_id, problem_id, org_problem_id, points, order_index) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$contestId, $problemId, $orgProblemId, 100 * $order, $order]);
}

function uiuParticipant(PDO $pdo, int $contestId, int $orgId, int $userId, int $score, int $penalty, string $registeredAt): void {
    $existing = uiuOne($pdo, 'SELECT id FROM contest_participants WHERE contest_id=? AND user_id=? LIMIT 1', [$contestId, $userId]);
    if ($existing) {
        $stmt = $pdo->prepare('UPDATE contest_participants SET org_id=?, score=?, penalty_minutes=?, status="approved", registered_at=? WHERE id=?');
        $stmt->execute([$orgId, $score, $penalty, $registeredAt, (int)$existing]);
        return;
    }
    $stmt = $pdo->prepare('INSERT INTO contest_participants (contest_id, org_id, user_id, score, penalty_minutes, registered_at, status) VALUES (?, ?, ?, ?, ?, ?, "approved")');
    $stmt->execute([$contestId, $orgId, $userId, $score, $penalty, $registeredAt]);
}

function uiuSubmission(PDO $pdo, array $s): void {
    $existing = uiuOne($pdo, 'SELECT id FROM submissions WHERE user_id=? AND problem_id=? AND contest_id=? AND submitted_at=? LIMIT 1', [$s['user_id'], $s['problem_id'], $s['contest_id'], $s['submitted_at']]);
    if ($existing) return;
    $stmt = $pdo->prepare(
        'INSERT INTO submissions (user_id,problem_id,code,language,status,score,hints_used,contest_id,org_id,runtime_ms,memory_kb,test_results,submitted_at,is_practice)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,0)'
    );
    $stmt->execute([
        $s['user_id'], $s['problem_id'], '// UIU_CCL presentation demo submission', $s['language'], $s['status'], $s['score'], $s['hints'],
        $s['contest_id'], $s['org_id'], $s['runtime'], $s['memory'],
        json_encode(['passed' => $s['status'] === 'Accepted' ? 12 : 7, 'total' => 12, 'seed' => 'uiu-ccl-demo']),
        $s['submitted_at'],
    ]);
}

$pdo->beginTransaction();
try {
    $platformProblems = uiuRows($pdo, 'SELECT id,title,difficulty,tags FROM problems WHERE is_public=1 AND COALESCE(is_deleted,0)=0 ORDER BY id ASC LIMIT 80');
    if (count($platformProblems) < 20) throw new RuntimeException('Need public platform problems before creating UIU_CCL demo.');

    $adminId = uiuUser($pdo, 'UIU CCL Admin', 'uiu_ccl_admin', 'uiuccl@gmail.com', 'org_admin', null, 1740, 1815, uiuTime('-180 days'));

    $orgExisting = uiuOne($pdo, 'SELECT id FROM organizations WHERE name=? LIMIT 1', ['UIU_CCL']);
    if ($orgExisting) {
        $orgId = (int)$orgExisting;
        $stmt = $pdo->prepare('UPDATE organizations SET type="university", description=?, logo="", owner_id=?, status="active", is_deleted=0 WHERE id=?');
        $stmt->execute(['UIU_CCL is the United International University Competitive Coding League: a university club workspace for contests, practice, announcements, participant management, and performance analytics.', $adminId, $orgId]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO organizations (name,type,description,logo,owner_id,created_at,status,is_deleted) VALUES ("UIU_CCL","university",?,"",?,?,"active",0)');
        $stmt->execute(['UIU_CCL is the United International University Competitive Coding League: a university club workspace for contests, practice, announcements, participant management, and performance analytics.', $adminId, uiuTime('-170 days')]);
        $orgId = (int)$pdo->lastInsertId();
    }
    $pdo->prepare('UPDATE users SET org_id=? WHERE id=?')->execute([$orgId, $adminId]);
    uiuMember($pdo, $orgId, $adminId, 'org_owner', uiuTime('-170 days'));

    $memberIds = [$adminId];
    $names = [
        ['Samin Rahman','samin_uiu'], ['Nusrat Jahan','nusrat_uiu'], ['Fahim Islam','fahim_uiu'], ['Maliha Karim','maliha_uiu'],
        ['Rafi Chowdhury','rafi_uiu'], ['Tanjim Hasan','tanjim_uiu'], ['Zarin Ahmed','zarin_uiu'], ['Adnan Sakib','adnan_uiu'],
        ['Mehedi Hasan','mehedi_uiu'], ['Farzana Akter','farzana_uiu'], ['Raisa Noor','raisa_uiu'], ['Shuvo Das','shuvo_uiu'],
        ['Anika Sultana','anika_uiu'], ['Nayeem Hossain','nayeem_uiu'], ['Tasnim Alam','tasnim_uiu'], ['Sadia Rahman','sadia_uiu'],
        ['Rakib Hasan','rakib_uiu'], ['Jannatul Mawa','jannatul_uiu'], ['Mahin Khan','mahin_uiu'], ['Iffat Ara','iffat_uiu'],
        ['Abrar Hossain','abrar_uiu'], ['Lamisa Islam','lamisa_uiu'], ['Sakib Mahmud','sakib_uiu'], ['Mushfiq Amin','mushfiq_uiu'],
        ['Nabila Ferdous','nabila_uiu'], ['Tahmid Rahman','tahmid_uiu'], ['Fariha Islam','fariha_uiu'], ['Saif Ahmed','saif_uiu'],
        ['Rodela Jahan','rodela_uiu'], ['Maruf Hossain','maruf_uiu'],
    ];
    foreach ($names as $i => [$name, $username]) {
        $id = uiuUser($pdo, $name, $username, $username . '@uiu-ccl.seed.test', $i < 2 ? 'org_admin' : 'user', $orgId, 1120 + (($i * 47) % 780), 1080 + (($i * 53) % 850), uiuTime('-' . (150 - $i * 3) . ' days'));
        $memberIds[] = $id;
        uiuMember($pdo, $orgId, $id, $i < 2 ? 'org_admin' : 'org_member', uiuTime('-' . (145 - $i * 3) . ' days'));
    }

    $problemTemplates = [
        ['UIU Bus Route Optimizer','Find the minimum transfer route between campus stops using graph traversal.','Easy','graphs,bfs,campus'],
        ['Trimester Course Planner','Select a valid course sequence respecting prerequisites.','Medium','topological-sort,graphs'],
        ['Club Budget Knapsack','Maximize event value under a limited club budget.','Medium','dynamic-programming,knapsack'],
        ['Library Seat Allocation','Assign seats to students with conflict rules and priorities.','Easy','greedy,sorting'],
        ['Contest Rank Merge','Merge multiple practice rankings into one fair leaderboard.','Medium','sorting,heap'],
        ['Cafeteria Queue Simulation','Simulate multiple service counters under rush-hour load.','Easy','queue,simulation'],
        ['Lab Network Recovery','Reconnect lab machines with minimum cable cost.','Hard','mst,graphs'],
        ['Scholarship Score Window','Answer range queries over student performance scores.','Medium','prefix-sum,sliding-window'],
        ['Freshers Pairing Challenge','Pair beginners with mentors using compatibility scores.','Medium','matching,greedy'],
        ['Campus Event Scheduler','Schedule events without room conflicts.','Hard','intervals,greedy'],
        ['Debug Sprint Analyzer','Detect the most common failed verdict pattern.','Easy','hash-map,implementation'],
        ['Advanced DP Bootcamp','Solve a multi-stage optimization problem for bootcamp planning.','Hard','dynamic-programming'],
    ];
    $orgProblems = [];
    foreach ($problemTemplates as $i => $p) {
        $platform = $platformProblems[($i * 5 + 2) % count($platformProblems)];
        $orgProblemId = uiuOrgProblem($pdo, $orgId, $adminId, $platform, ['title'=>$p[0], 'description'=>$p[1], 'difficulty'=>$p[2], 'tags'=>$p[3]], $i + 1);
        $orgProblems[] = ['org_problem_id' => $orgProblemId, 'problem_id' => (int)$platform['id']];
    }

    $contests = [
        ['UIU_CCL Freshers Practice Cup','Beginner-friendly onboarding contest for new UIU CCL members.',uiuTime('-55 days'),uiuTime('-55 days +2 hours'),'ended','ended','public',0],
        ['UIU_CCL Weekly Rated Round 07','A rated internal contest covering arrays, graphs, and DP basics.',uiuTime('-21 days'),uiuTime('-21 days +3 hours'),'ended','ended','public',3],
        ['UIU_CCL Live Selection Mock','Live team selection mock for upcoming inter-university programming contest.',uiuTime('-40 minutes'),uiuTime('+3 hours'),'active','live','org',5],
        ['UIU_CCL Graph Theory Night','Upcoming focused contest on BFS, MST, shortest paths, and graph modeling.',uiuTime('+5 days'),uiuTime('+5 days +2 hours'),'upcoming','scheduled','public',7],
        ['UIU_CCL Intra University Championship','Flagship upcoming contest for all UIU departments.',uiuTime('+18 days'),uiuTime('+18 days +4 hours'),'upcoming','scheduled','public',2],
    ];
    $languages = ['cpp','python','javascript','java'];
    $verdicts = ['Accepted','Accepted','Accepted','Wrong Answer','Time Limit Exceeded','Runtime Error','Compilation Error'];
    foreach ($contests as $ci => $c) {
        $contestId = uiuContest($pdo, $orgId, $adminId, ['title'=>$c[0], 'description'=>$c[1], 'start'=>$c[2], 'end'=>$c[3], 'status'=>$c[4], 'org_status'=>$c[5], 'visibility'=>$c[6]]);
        $slice = array_slice($orgProblems, $c[7], 5);
        if (count($slice) < 5) $slice = array_slice($orgProblems, 0, 5);
        foreach ($slice as $order => $problem) {
            uiuContestProblem($pdo, $contestId, $problem['problem_id'], $problem['org_problem_id'], $order + 1);
        }
        foreach ($memberIds as $mi => $memberId) {
            if ($mi === 0 || ($mi + $ci) % 8 === 0) continue;
            $totalScore = 0;
            foreach ($slice as $pi => $problem) {
                $attempts = 1 + (($mi + $pi + $ci) % 3);
                for ($a = 0; $a < $attempts; $a++) {
                    $status = $a === $attempts - 1 && (($mi + $pi + $ci) % 5 !== 0) ? 'Accepted' : $verdicts[($mi + $pi + $a + $ci) % count($verdicts)];
                    $score = $status === 'Accepted' ? ($pi + 1) * 100 : 0;
                    if ($status === 'Accepted') $totalScore += $score;
                    uiuSubmission($pdo, [
                        'user_id'=>$memberId, 'problem_id'=>$problem['problem_id'], 'contest_id'=>$contestId, 'org_id'=>$orgId,
                        'language'=>$languages[($mi + $pi + $a) % count($languages)], 'status'=>$status, 'score'=>$score,
                        'hints'=>($mi + $pi) % 3, 'runtime'=>35 + (($mi * 29 + $pi * 41 + $a * 13) % 900),
                        'memory'=>16000 + (($mi * 811 + $pi * 677) % 70000),
                        'submitted_at'=>date('Y-m-d H:i:s', strtotime($c[2] . ' +' . (10 + $mi * 5 + $pi * 12 + $a * 8) . ' minutes')),
                    ]);
                }
            }
            uiuParticipant($pdo, $contestId, $orgId, $memberId, min($totalScore, 1500), 20 + (($mi * 17 + $ci * 11) % 180), date('Y-m-d H:i:s', strtotime($c[2] . ' -' . (1 + ($mi % 4)) . ' days')));
        }
    }

    $announcements = [
        ['UIU_CCL orientation contest published','Freshers Practice Cup is available for all new members. Please register before the deadline.','announcement'],
        ['Selection mock rules and clarification policy','Submissions are frozen during the final 20 minutes. Clarifications will be answered publicly.','clarification'],
        ['Graph Theory Night preparation guide','Members should revise BFS, DFS, MST, and shortest path problems before the contest.','instruction'],
        ['Intra University Championship registration open','Department representatives can invite participants through the organization panel.','announcement'],
    ];
    foreach ($announcements as $i => $a) {
        $existing = uiuOne($pdo, 'SELECT id FROM announcements WHERE org_id=? AND title=? LIMIT 1', [$orgId, $a[0]]);
        if ($existing) {
            $stmt = $pdo->prepare('UPDATE announcements SET message=?, type=?, target_type="org", is_published=1, created_by=? WHERE id=?');
            $stmt->execute([$a[1], $a[2], $adminId, (int)$existing]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO announcements (org_id,contest_id,title,message,target_type,type,is_published,created_by,created_at) VALUES (?,NULL,?,?, "org", ?, 1, ?, ?)');
            $stmt->execute([$orgId, $a[0], $a[1], $a[2], $adminId, uiuTime('-' . (8 - $i) . ' days')]);
        }
    }

    foreach ($memberIds as $i => $memberId) {
        $existing = uiuOne($pdo, 'SELECT id FROM user_practice_stats WHERE user_id=? LIMIT 1', [$memberId]);
        if ($existing) {
            $stmt = $pdo->prepare('UPDATE user_practice_stats SET total_solved=?, accuracy=?, streak_days=?, rating=?, last_active_date=?, updated_at=? WHERE id=?');
            $stmt->execute([22 + ($i * 3) % 70, 55 + ($i * 4) % 39, $i % 15, 1120 + ($i * 31) % 760, date('Y-m-d', strtotime(UIU_CCL_NOW . ' -' . ($i % 6) . ' days')), UIU_CCL_NOW, (int)$existing]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO user_practice_stats (user_id,total_solved,accuracy,streak_days,rating,last_active_date,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?)');
            $stmt->execute([$memberId, 22 + ($i * 3) % 70, 55 + ($i * 4) % 39, $i % 15, 1120 + ($i * 31) % 760, date('Y-m-d', strtotime(UIU_CCL_NOW . ' -' . ($i % 6) . ' days')), UIU_CCL_NOW, UIU_CCL_NOW]);
        }
    }

    $pdo->exec('UPDATE problems p SET total_submissions = (SELECT COUNT(*) FROM submissions s WHERE s.problem_id = p.id), total_accepted = (SELECT COUNT(*) FROM submissions s WHERE s.problem_id = p.id AND s.status = "Accepted")');
    $pdo->commit();

    echo "UIU_CCL demo organization seeded successfully.\n";
    echo "Login email: uiuccl@gmail.com\n";
    echo "Password: " . UIU_CCL_PASSWORD . "\n";
    echo "Organization ID: {$orgId}\n";
    echo "Members: " . uiuOne($pdo, 'SELECT COUNT(*) FROM organization_members WHERE org_id=?', [$orgId]) . "\n";
    echo "Contests: " . uiuOne($pdo, 'SELECT COUNT(*) FROM contests WHERE org_id=?', [$orgId]) . "\n";
    echo "Problems: " . uiuOne($pdo, 'SELECT COUNT(*) FROM org_problems WHERE org_id=? AND is_deleted=0', [$orgId]) . "\n";
    echo "Submissions: " . uiuOne($pdo, 'SELECT COUNT(*) FROM submissions WHERE org_id=?', [$orgId]) . "\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "UIU_CCL seed failed: " . $e->getMessage() . "\n");
    exit(1);
}
