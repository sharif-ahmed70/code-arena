<?php
require_once '../includes/session.php';
require_once '../config/db.php';
require_once '../includes/organization.php';

$organization = requireOrganizationPage($pdo);

$orgName = $organization['name'] ?? 'Organization';
$orgId = (int)($organization['id'] ?? 0);
$memberStmt = $pdo->prepare('SELECT COUNT(*) FROM organization_members WHERE org_id = ?');
$memberStmt->execute([$orgId]);
$memberCount = (int)$memberStmt->fetchColumn();

$contestStatsStmt = $pdo->prepare(
    'SELECT
        COUNT(*) AS total_contests,
        SUM(status = "active") AS active_contests,
        SUM(status = "upcoming") AS upcoming_contests,
        SUM(status = "ended") AS ended_contests
     FROM contests
     WHERE org_id = ?'
);
$contestStatsStmt->execute([$orgId]);
$contestStats = $contestStatsStmt->fetch() ?: [];

$submissionStatsStmt = $pdo->prepare(
    'SELECT COUNT(s.id) AS total_submissions,
            SUM(s.status = "Accepted") AS accepted_submissions,
            COUNT(DISTINCT s.user_id) AS active_submitters
     FROM contests c
     LEFT JOIN submissions s ON s.contest_id = c.id AND s.is_practice = 0
     WHERE c.org_id = ? AND (s.org_id = c.org_id OR s.id IS NULL)'
);
$submissionStatsStmt->execute([$orgId]);
$submissionStats = $submissionStatsStmt->fetch() ?: [];

$participantStmt = $pdo->prepare(
    'SELECT COUNT(cp.id) AS participant_entries,
            COUNT(DISTINCT cp.user_id) AS unique_participants
     FROM contests c
     LEFT JOIN contest_participants cp ON cp.contest_id = c.id AND cp.org_id = c.org_id
     WHERE c.org_id = ?'
);
$participantStmt->execute([$orgId]);
$participantStats = $participantStmt->fetch() ?: [];

$recentContestsStmt = $pdo->prepare(
    'SELECT c.id, c.title, c.status, c.start_time, c.end_time,
            COUNT(DISTINCT cp.user_id) AS participants,
            COUNT(DISTINCT s.id) AS submissions
     FROM contests c
     LEFT JOIN contest_participants cp ON cp.contest_id = c.id AND cp.org_id = c.org_id
     LEFT JOIN submissions s ON s.contest_id = c.id AND s.org_id = c.org_id AND s.is_practice = 0
     WHERE c.org_id = ?
     GROUP BY c.id
     ORDER BY c.start_time DESC
     LIMIT 6'
);
$recentContestsStmt->execute([$orgId]);
$recentContests = $recentContestsStmt->fetchAll();

$recentSubmissionsStmt = $pdo->prepare(
    'SELECT s.id, s.status, s.language, s.submitted_at, u.username, p.title AS problem_title, c.title AS contest_title
     FROM submissions s
     JOIN contests c ON c.id = s.contest_id
     JOIN users u ON u.id = s.user_id
     JOIN problems p ON p.id = s.problem_id
     WHERE c.org_id = ? AND s.org_id = c.org_id AND s.is_practice = 0
     ORDER BY s.submitted_at DESC
     LIMIT 8'
);
$recentSubmissionsStmt->execute([$orgId]);
$recentSubmissions = $recentSubmissionsStmt->fetchAll();

$graphRows = array_slice($recentContests, 0, 5);
$maxParticipants = max(1, ...array_map(fn($row) => (int)$row['participants'], $graphRows ?: [['participants' => 1]]));

$totalContests = (int)($contestStats['total_contests'] ?? 0);
$activeContests = (int)($contestStats['active_contests'] ?? 0);
$totalSubmissions = (int)($submissionStats['total_submissions'] ?? 0);
$acceptedSubmissions = (int)($submissionStats['accepted_submissions'] ?? 0);
$successRate = $totalSubmissions > 0 ? round(($acceptedSubmissions / $totalSubmissions) * 100) : 0;
$participantEntries = (int)($participantStats['participant_entries'] ?? 0);
$participationDenominator = max(1, $totalContests * max(1, $memberCount));
$participationRate = $totalContests > 0 ? min(100, round(($participantEntries / $participationDenominator) * 100)) : 0;
$activeContestId = 0;
foreach ($recentContests as $contest) {
    if ($contest['status'] === 'active') {
        $activeContestId = (int)$contest['id'];
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Control Panel - Code Arena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css?v=20260615-ui5">
    <style>
        body { background:var(--bg); }
        .org-shell { min-height:100vh; display:grid; grid-template-columns:260px minmax(0,1fr); }
        .org-sidebar {
            position:sticky; top:0; height:100vh; padding:22px 16px; border-right:1px solid var(--border);
            background:linear-gradient(180deg, var(--bg-card), rgba(255,255,255,.018));
            display:flex; flex-direction:column; gap:20px;
        }
        .org-brand { padding:0 8px 14px; border-bottom:1px solid var(--border); }
        .org-brand strong { display:block; font-size:1.05rem; color:var(--text); }
        .org-brand span { display:block; color:var(--text-muted); font-size:.78rem; margin-top:4px; text-transform:capitalize; }
        .org-menu { display:grid; gap:6px; }
        .org-menu a {
            display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:var(--radius-sm);
            color:var(--text-muted); font-size:.9rem; transition:background .18s ease, color .18s ease;
        }
        .org-menu a:hover, .org-menu a.active { background:rgba(0,232,122,.08); color:var(--accent); }
        .org-main { min-width:0; }
        .org-topbar {
            position:sticky; top:0; z-index:10; min-height:68px; padding:14px 24px;
            border-bottom:1px solid var(--border); background:rgba(10,10,15,.94);
            display:flex; align-items:center; gap:16px;
        }
        .org-topbar h1 { font-size:1.05rem; margin:0; white-space:nowrap; }
        .org-search {
            flex:1; max-width:520px; padding:10px 12px; border:1px solid var(--border);
            border-radius:var(--radius-sm); background:var(--bg-card2); color:var(--text);
        }
        .top-icon {
            width:38px; height:38px; display:inline-flex; align-items:center; justify-content:center;
            border:1px solid var(--border); border-radius:var(--radius-sm); background:var(--bg-card2); color:var(--text-muted);
        }
        .org-content { padding:24px; display:grid; grid-template-columns:minmax(0,1fr) 300px; gap:22px; align-items:start; }
        .org-section { display:grid; gap:18px; }
        .metric-grid { display:grid; grid-template-columns:repeat(5, minmax(140px,1fr)); gap:14px; }
        .metric-card, .panel-card {
            border:1px solid var(--border); border-radius:var(--radius); background:var(--bg-card); padding:18px;
        }
        .metric-card strong { display:block; font-size:1.8rem; line-height:1; }
        .metric-card span { display:block; color:var(--text-muted); font-size:.76rem; text-transform:uppercase; letter-spacing:.05em; margin-top:8px; }
        .panel-title { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:14px; }
        .panel-title h2, .panel-title h3 { font-size:1rem; margin:0; }
        .analytics-grid { display:grid; grid-template-columns:1.2fr .8fr; gap:16px; }
        .bar-chart { display:grid; grid-template-columns:repeat(5, 1fr); gap:12px; height:180px; align-items:end; padding-top:12px; }
        .bar-item { display:grid; gap:8px; align-items:end; height:100%; }
        .bar { min-height:8px; border-radius:8px 8px 3px 3px; background:linear-gradient(180deg,var(--accent),var(--blue)); }
        .bar-label { color:var(--text-muted); font-size:.7rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .rate-ring {
            width:132px; height:132px; border-radius:50%; display:grid; place-items:center; margin:8px auto 12px;
            background:conic-gradient(var(--accent) <?= $successRate ?>%, var(--border) 0);
        }
        .rate-ring div { width:96px; height:96px; border-radius:50%; background:var(--bg-card); display:grid; place-items:center; font-size:1.5rem; font-weight:800; }
        .table-list { display:grid; gap:10px; }
        .list-row {
            display:grid; grid-template-columns:minmax(0,1fr) auto; gap:12px; align-items:center;
            padding:12px; border:1px solid var(--border); border-radius:var(--radius-sm); background:var(--bg-card2);
        }
        .list-row strong { display:block; color:var(--text); }
        .list-row span { display:block; color:var(--text-muted); font-size:.78rem; margin-top:3px; }
        .status-pill { padding:4px 8px; border-radius:999px; background:rgba(108,160,255,.1); color:var(--blue); font-size:.75rem; text-transform:capitalize; }
        .side-stack { display:grid; gap:14px; position:sticky; top:92px; }
        .quick-actions { display:grid; gap:10px; }
        .live-stat { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:10px; }
        .live-stat div { padding:10px; border-radius:var(--radius-sm); background:var(--bg-card2); }
        .live-stat strong { display:block; }
        .live-stat span { color:var(--text-muted); font-size:.74rem; }
        @media(max-width:1180px) {
            .org-content { grid-template-columns:1fr; }
            .side-stack { position:static; }
            .metric-grid { grid-template-columns:repeat(2, minmax(140px,1fr)); }
        }
        @media(max-width:760px) {
            .org-shell { grid-template-columns:1fr; }
            .org-sidebar { position:static; height:auto; }
            .org-topbar { flex-wrap:wrap; }
            .org-search { order:3; max-width:none; flex-basis:100%; }
            .analytics-grid { grid-template-columns:1fr; }
            .metric-grid { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>
<div class="org-shell">
    <aside class="org-sidebar">
        <div class="org-brand">
            <strong><?= htmlspecialchars($orgName) ?></strong>
            <span><?= htmlspecialchars($organization['type'] ?? 'organization') ?> control panel</span>
        </div>
        <nav class="org-menu" aria-label="Organization navigation">
            <a class="active" href="/code-arena/organization/dashboard.php">Dashboard Overview</a>
            <a href="/code-arena/organization/problems.php">Problem Bank</a>
            <a href="/code-arena/organization/create_contest.php">Create Contest</a>
            <a href="/code-arena/organization/contests.php">Manage Contests</a>
            <a href="/code-arena/organization/participants.php">Manage Members</a>
            <a href="/code-arena/organization/analytics.php">Analytics</a>
            <a href="/code-arena/organization/submissions.php">Submissions Review</a>
            <a href="/code-arena/organization/announcements.php">Announcements</a>
            <a href="/code-arena/organization/settings.php">Organization Settings</a>
        </nav>
    </aside>

    <main class="org-main">
        <header class="org-topbar">
            <h1><?= htmlspecialchars($orgName) ?></h1>
            <input class="org-search" placeholder="Search contests, members, submissions">
            <a class="top-icon" href="#announcements" title="Notifications">!</a>
            <a class="top-icon" href="/code-arena/profile.php" title="Profile"><?= htmlspecialchars(strtoupper(substr(currentUsername() ?? 'O', 0, 1))) ?></a>
            <a class="btn-outline" href="/code-arena/api/auth/logout.php">Logout</a>
        </header>

        <div class="org-content">
            <section class="org-section">
                <div class="metric-grid">
                    <div class="metric-card"><strong><?= $totalContests ?></strong><span>Total Contests</span></div>
                    <div class="metric-card"><strong><?= $activeContests ?></strong><span>Active Contests</span></div>
                    <div class="metric-card"><strong><?= $memberCount ?></strong><span>Total Members</span></div>
                    <div class="metric-card"><strong><?= $totalSubmissions ?></strong><span>Total Submissions</span></div>
                    <div class="metric-card"><strong><?= $participationRate ?>%</strong><span>Participation Rate</span></div>
                </div>

                <div class="analytics-grid" id="analytics">
                    <section class="panel-card">
                        <div class="panel-title">
                            <h2>Contest Participation</h2>
                            <span style="color:var(--text-muted);font-size:.8rem">Recent contests</span>
                        </div>
                        <div class="bar-chart">
                            <?php if ($graphRows): ?>
                                <?php foreach (array_reverse($graphRows) as $row): ?>
                                <?php $height = max(8, round(((int)$row['participants'] / $maxParticipants) * 100)); ?>
                                <div class="bar-item">
                                    <div class="bar" style="height:<?= $height ?>%"></div>
                                    <div class="bar-label" title="<?= htmlspecialchars($row['title']) ?>"><?= htmlspecialchars($row['title']) ?></div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="grid-column:1/-1;color:var(--text-muted);align-self:center">No contest participation data yet.</div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <section class="panel-card">
                        <div class="panel-title"><h2>Submission Success Rate</h2></div>
                        <div class="rate-ring"><div><?= $successRate ?>%</div></div>
                        <p style="color:var(--text-muted);text-align:center;margin:0">
                            <?= $acceptedSubmissions ?> accepted of <?= $totalSubmissions ?> submissions
                        </p>
                    </section>
                </div>

                <section class="panel-card">
                    <div class="panel-title">
                        <h2>Manage Contests</h2>
                        <a class="btn-outline" href="/code-arena/contests.php">Open Contest Center</a>
                    </div>
                    <div class="table-list">
                        <?php if ($recentContests): ?>
                            <?php foreach ($recentContests as $contest): ?>
                            <div class="list-row">
                                <div>
                                    <strong><?= htmlspecialchars($contest['title']) ?></strong>
                                    <span><?= (int)$contest['participants'] ?> participants · <?= (int)$contest['submissions'] ?> submissions · <?= htmlspecialchars($contest['start_time']) ?></span>
                                </div>
                                <div style="display:flex;gap:8px;align-items:center">
                                    <span class="status-pill"><?= htmlspecialchars($contest['status']) ?></span>
                                    <a class="btn-outline" href="/code-arena/contest_manage.php?id=<?= (int)$contest['id'] ?>">Manage</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color:var(--text-muted)">No contests created yet.</p>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="panel-card" id="submissions">
                    <div class="panel-title">
                        <h2>Submissions Review</h2>
                        <span style="color:var(--text-muted);font-size:.8rem">Latest contest submissions</span>
                    </div>
                    <div class="table-list">
                        <?php if ($recentSubmissions): ?>
                            <?php foreach ($recentSubmissions as $submission): ?>
                            <div class="list-row">
                                <div>
                                    <strong><?= htmlspecialchars($submission['username']) ?> · <?= htmlspecialchars($submission['problem_title']) ?></strong>
                                    <span><?= htmlspecialchars($submission['contest_title']) ?> · <?= htmlspecialchars($submission['language']) ?> · <?= htmlspecialchars($submission['submitted_at']) ?></span>
                                </div>
                                <span class="status-pill"><?= htmlspecialchars($submission['status']) ?></span>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color:var(--text-muted)">No submissions to review yet.</p>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="panel-card" id="members">
                    <div class="panel-title"><h2>Member Activity Tracking</h2></div>
                    <p style="color:var(--text-muted);line-height:1.7;margin:0">
                        <?= (int)($participantStats['unique_participants'] ?? 0) ?> unique members have participated across hosted contests.
                        <?= (int)($submissionStats['active_submitters'] ?? 0) ?> members submitted solutions.
                    </p>
                </section>
            </section>

            <aside class="side-stack">
                <section class="panel-card">
                    <div class="panel-title"><h3>Quick Actions</h3></div>
                    <div class="quick-actions">
                        <a class="btn-primary" href="/code-arena/organization/create_contest.php">Create Contest</a>
                        <a class="btn-outline" href="/code-arena/organization/participants.php">Invite Members</a>
                        <a class="btn-outline" href="<?= $activeContestId ? '/code-arena/contest.php?id=' . $activeContestId : '/code-arena/contests.php?status=active' ?>">View Active Contest</a>
                        <a class="btn-outline" href="/code-arena/organization/submissions.php">Review Submissions</a>
                    </div>
                </section>

                <section class="panel-card">
                    <div class="panel-title"><h3>Live Stats</h3></div>
                    <div class="live-stat">
                        <div><strong><?= $activeContests ?></strong><span>Live contests</span></div>
                        <div><strong><?= (int)($submissionStats['active_submitters'] ?? 0) ?></strong><span>Submitters</span></div>
                        <div><strong><?= $successRate ?>%</strong><span>Success</span></div>
                        <div><strong><?= (int)($participantStats['unique_participants'] ?? 0) ?></strong><span>Participants</span></div>
                    </div>
                </section>

                <section class="panel-card" id="announcements">
                    <div class="panel-title"><h3>Announcements</h3></div>
                    <p style="color:var(--text-muted);line-height:1.7;margin:0">No announcements published yet.</p>
                </section>

                <section class="panel-card" id="settings">
                    <div class="panel-title"><h3>Organization Settings</h3></div>
                    <p style="color:var(--text-muted);line-height:1.7;margin:0"><?= htmlspecialchars($organization['description'] ?? 'Add a description and branding for your organization workspace.') ?></p>
                </section>
            </aside>
        </div>
    </main>
</div>
</body>
</html>
