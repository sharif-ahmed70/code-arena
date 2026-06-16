<?php
require_once '../includes/session.php';
require_once '../config/db.php';
require_once '../includes/organization.php';

$organization = requireOrganizationPage($pdo);
$pageTitle = 'Organization Profile';
$activeOrgPage = 'profile';

$orgId = (int)($organization['id'] ?? 0);
$orgName = $organization['name'] ?? 'Organization';
$orgType = $organization['type'] ?? 'community';
$orgDescription = trim((string)($organization['description'] ?? ''));
$orgLogo = trim((string)($organization['logo'] ?? ''));
$orgStatus = $organization['status'] ?? 'active';
$createdAt = $organization['created_at'] ?? null;

function orgProfileOne(PDO $pdo, string $sql, array $params = [], $default = 0) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $value = $stmt->fetchColumn();
        return $value === false || $value === null ? $default : $value;
    } catch (Throwable $e) {
        return $default;
    }
}

function orgProfileRows(PDO $pdo, string $sql, array $params = []): array {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function orgInitials(string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    $letters = '';
    foreach (array_slice($parts ?: ['O'], 0, 2) as $part) {
        $letters .= strtoupper(substr($part, 0, 1));
    }
    return $letters ?: 'O';
}

function orgStatusLabel(string $status): string {
    return match ($status) {
        'active', 'live' => 'Active',
        'upcoming', 'scheduled' => 'Upcoming',
        'ended' => 'Past',
        'draft' => 'Draft',
        'archived' => 'Archived',
        default => ucfirst($status ?: 'Unknown'),
    };
}

$owner = null;
if (!empty($organization['owner_id'])) {
    $ownerRows = orgProfileRows(
        $pdo,
        'SELECT id, COALESCE(NULLIF(name, ""), username) AS display_name, username, email, role
         FROM users
         WHERE id = ?
         LIMIT 1',
        [(int)$organization['owner_id']]
    );
    $owner = $ownerRows[0] ?? null;
}

if (!$owner) {
    $ownerRows = orgProfileRows(
        $pdo,
        'SELECT u.id, COALESCE(NULLIF(u.name, ""), u.username) AS display_name, u.username, u.email, om.role
         FROM organization_members om
         JOIN users u ON u.id = om.user_id
         WHERE om.org_id = ?
         ORDER BY FIELD(om.role, "org_owner", "org_admin", "admin", "member", "org_member"), om.id ASC
         LIMIT 1',
        [$orgId]
    );
    $owner = $ownerRows[0] ?? null;
}

$memberCount = (int)orgProfileOne($pdo, 'SELECT COUNT(*) FROM organization_members WHERE org_id = ?', [$orgId]);
$roleRows = orgProfileRows(
    $pdo,
    'SELECT role, COUNT(*) AS total
     FROM organization_members
     WHERE org_id = ?
     GROUP BY role',
    [$orgId]
);
$roleTotals = [];
foreach ($roleRows as $row) {
    $roleTotals[$row['role'] ?: 'member'] = (int)$row['total'];
}

$members = orgProfileRows(
    $pdo,
    'SELECT u.id, COALESCE(NULLIF(u.name, ""), u.username) AS display_name, u.username, u.email, om.role, COALESCE(om.joined_at, om.created_at) AS joined_at
     FROM organization_members om
     JOIN users u ON u.id = om.user_id
     WHERE om.org_id = ?
     ORDER BY FIELD(om.role, "org_owner", "org_admin", "admin", "member", "org_member"), COALESCE(om.joined_at, om.created_at) DESC
     LIMIT 8',
    [$orgId]
);

$contestStatsRows = orgProfileRows(
    $pdo,
    'SELECT
        COUNT(*) AS total_contests,
        SUM(status IN ("active", "live") OR org_status = "live") AS active_contests,
        SUM(status IN ("upcoming", "scheduled") OR org_status = "scheduled") AS upcoming_contests,
        SUM(status = "ended" OR org_status = "ended") AS past_contests
     FROM contests
     WHERE org_id = ?',
    [$orgId]
);
$contestStats = $contestStatsRows[0] ?? [];
$totalContests = (int)($contestStats['total_contests'] ?? 0);
$activeContests = (int)($contestStats['active_contests'] ?? 0);
$upcomingContests = (int)($contestStats['upcoming_contests'] ?? 0);
$pastContests = (int)($contestStats['past_contests'] ?? 0);

$problemCount = (int)orgProfileOne($pdo, 'SELECT COUNT(*) FROM org_problems WHERE org_id = ? AND COALESCE(is_deleted, 0) = 0', [$orgId]);
$totalSubmissions = (int)orgProfileOne($pdo, 'SELECT COUNT(*) FROM submissions WHERE org_id = ? AND COALESCE(is_practice, 0) = 0', [$orgId]);
$acceptedSubmissions = (int)orgProfileOne(
    $pdo,
    'SELECT COUNT(*) FROM submissions WHERE org_id = ? AND COALESCE(is_practice, 0) = 0 AND status IN ("Accepted", "accepted", "AC")',
    [$orgId]
);
$successRate = $totalSubmissions > 0 ? round(($acceptedSubmissions / $totalSubmissions) * 100) : 0;
$participantEntries = (int)orgProfileOne($pdo, 'SELECT COUNT(*) FROM contest_participants WHERE org_id = ? AND status NOT IN ("removed", "banned", "rejected")', [$orgId]);
$participationRate = $totalContests > 0 && $memberCount > 0 ? min(100, round(($participantEntries / ($totalContests * $memberCount)) * 100)) : 0;

$recentContests = orgProfileRows(
    $pdo,
    'SELECT c.id, c.title, c.status, c.org_status, c.start_time, c.end_time,
            COUNT(DISTINCT cp.user_id) AS participants,
            COUNT(DISTINCT s.id) AS submissions
     FROM contests c
     LEFT JOIN contest_participants cp ON cp.contest_id = c.id AND (cp.org_id = c.org_id OR cp.org_id IS NULL)
     LEFT JOIN submissions s ON s.contest_id = c.id AND (s.org_id = c.org_id OR s.org_id IS NULL) AND COALESCE(s.is_practice, 0) = 0
     WHERE c.org_id = ?
     GROUP BY c.id
     ORDER BY c.start_time DESC, c.id DESC
     LIMIT 5',
    [$orgId]
);

$topProblems = orgProfileRows(
    $pdo,
    'SELECT title, difficulty, tags, created_at
     FROM org_problems
     WHERE org_id = ? AND COALESCE(is_deleted, 0) = 0
     ORDER BY created_at DESC, id DESC
     LIMIT 6',
    [$orgId]
);

$difficultyRows = orgProfileRows(
    $pdo,
    'SELECT difficulty, COUNT(*) AS total
     FROM org_problems
     WHERE org_id = ? AND COALESCE(is_deleted, 0) = 0
     GROUP BY difficulty',
    [$orgId]
);
$difficultyTotals = ['Easy' => 0, 'Medium' => 0, 'Hard' => 0];
foreach ($difficultyRows as $row) {
    $key = ucfirst(strtolower((string)$row['difficulty']));
    if (isset($difficultyTotals[$key])) $difficultyTotals[$key] = (int)$row['total'];
}
$maxDifficulty = max(1, ...array_values($difficultyTotals));

$activityRows = orgProfileRows(
    $pdo,
    'SELECT DATE(submitted_at) AS day, COUNT(*) AS total
     FROM submissions
     WHERE org_id = ? AND submitted_at >= DATE_SUB(CURDATE(), INTERVAL 34 DAY)
     GROUP BY DATE(submitted_at)
     ORDER BY day ASC',
    [$orgId]
);
$activityByDay = [];
foreach ($activityRows as $row) {
    $activityByDay[$row['day']] = (int)$row['total'];
}
$activityValues = [];
for ($i = 34; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-{$i} days"));
    $activityValues[] = $activityByDay[$day] ?? 0;
}
$maxActivity = max(1, ...$activityValues);

$recentAnnouncements = orgProfileRows(
    $pdo,
    'SELECT title, type, created_at
     FROM announcements
     WHERE org_id = ?
     ORDER BY created_at DESC
     LIMIT 4',
    [$orgId]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($orgName) ?> Profile - Code Arena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css?v=20260615-ui5">
    <style>
        .org-profile-hero {
            display:grid; grid-template-columns:minmax(0,1.15fr) 340px; gap:18px; margin-bottom:18px;
        }
        .profile-card {
            border:1px solid var(--border); border-radius:var(--radius); background:var(--bg-card);
            box-shadow:var(--shadow); overflow:hidden;
        }
        .profile-hero-main { padding:24px; position:relative; min-height:292px; display:flex; flex-direction:column; justify-content:space-between; }
        .profile-hero-main::after {
            content:""; position:absolute; right:-90px; top:-110px; width:300px; height:300px; border-radius:50%;
            background:radial-gradient(circle, rgba(124,58,237,.28), rgba(0,232,122,.11), transparent 68%);
            pointer-events:none;
        }
        .org-profile-top { display:flex; align-items:flex-start; gap:18px; position:relative; z-index:1; }
        .org-logo-large {
            width:92px; height:92px; border-radius:26px; flex:0 0 auto; display:grid; place-items:center; overflow:hidden;
            border:1px solid rgba(255,255,255,.14); background:linear-gradient(135deg,var(--accent),var(--blue));
            color:#08100d; font-size:2rem; font-weight:900; box-shadow:0 18px 46px rgba(0,232,122,.16);
        }
        .org-logo-large img { width:100%; height:100%; object-fit:cover; display:block; }
        .org-profile-title h2 { margin:0 0 8px; font-size:clamp(1.7rem,4vw,2.8rem); }
        .org-profile-title p { margin:0; color:var(--text-dim); max-width:680px; }
        .org-chip-row { display:flex; flex-wrap:wrap; gap:8px; margin-top:16px; position:relative; z-index:1; }
        .org-chip {
            display:inline-flex; align-items:center; gap:6px; padding:7px 10px; border-radius:999px;
            border:1px solid var(--border); background:var(--bg-card2); color:var(--text-dim); font-size:.82rem; font-weight:700;
        }
        .org-chip.green { border-color:rgba(0,232,122,.22); color:var(--accent); background:rgba(0,232,122,.08); }
        .owner-card { padding:20px; display:grid; gap:16px; align-content:start; }
        .owner-profile { display:flex; align-items:center; gap:12px; }
        .owner-avatar, .member-avatar {
            position:relative; width:52px; height:52px; border-radius:16px; display:grid; place-items:center;
            background:linear-gradient(135deg,rgba(124,58,237,.32),rgba(108,160,255,.2)); border:1px solid var(--border);
            color:var(--text); font-weight:900;
        }
        .owner-profile strong { display:block; }
        .owner-profile span { color:var(--text-muted); font-size:.84rem; }
        .readonly-note {
            padding:12px; border-radius:var(--radius-sm); border:1px solid rgba(0,232,122,.14);
            background:rgba(0,232,122,.055); color:var(--text-dim); font-size:.86rem;
        }
        .org-metrics { display:grid; grid-template-columns:repeat(4,minmax(140px,1fr)); gap:14px; margin-bottom:18px; }
        .metric-card {
            padding:18px; border:1px solid var(--border); border-radius:var(--radius); background:var(--bg-card);
            transition:transform .2s ease,border-color .2s ease;
        }
        .metric-card:hover { transform:translateY(-3px); border-color:rgba(124,58,237,.55); }
        .metric-card strong { display:block; font-size:1.8rem; line-height:1; }
        .metric-card span { display:block; margin-top:8px; color:var(--text-muted); font-size:.75rem; text-transform:uppercase; letter-spacing:.06em; }
        .org-profile-grid { display:grid; grid-template-columns:minmax(0,1.2fr) minmax(300px,.8fr); gap:18px; align-items:start; }
        .panel { padding:18px; }
        .panel-title { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:14px; }
        .panel-title h3 { margin:0; font-size:1.05rem; }
        .panel-title span { color:var(--text-muted); font-size:.82rem; }
        .role-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; }
        .role-box { padding:12px; border-radius:var(--radius-sm); background:var(--bg-card2); border:1px solid var(--border); }
        .role-box strong { display:block; font-size:1.25rem; }
        .role-box span { color:var(--text-muted); font-size:.78rem; text-transform:capitalize; }
        .avatar-strip { display:flex; flex-wrap:wrap; gap:10px; margin-top:14px; }
        .member-tip {
            position:absolute; bottom:calc(100% + 10px); left:50%; width:230px; transform:translate(-50%,4px);
            opacity:0; pointer-events:none; z-index:20; padding:10px 12px; border-radius:12px;
            border:1px solid rgba(255,255,255,.12); background:rgba(13,16,24,.96);
            box-shadow:0 18px 54px rgba(0,0,0,.42); color:var(--text-dim); font-size:.8rem;
            transition:opacity .18s ease, transform .18s ease;
        }
        .member-avatar:hover .member-tip { opacity:1; transform:translate(-50%,0); }
        .member-tip strong { display:block; color:var(--text); }
        .list-stack { display:grid; gap:10px; }
        .list-item {
            display:grid; grid-template-columns:minmax(0,1fr) auto; gap:12px; align-items:center;
            padding:12px; border-radius:var(--radius-sm); border:1px solid var(--border); background:var(--bg-card2);
        }
        .list-item strong { display:block; color:var(--text); }
        .list-item small { display:block; color:var(--text-muted); margin-top:3px; }
        .pill { padding:5px 8px; border-radius:999px; background:rgba(124,58,237,.12); color:var(--purple); font-size:.74rem; font-weight:800; white-space:nowrap; }
        .pill.green { background:rgba(0,232,122,.09); color:var(--accent); }
        .pill.blue { background:rgba(108,160,255,.1); color:var(--blue); }
        .difficulty-row { display:grid; grid-template-columns:74px 1fr 34px; gap:10px; align-items:center; margin-bottom:10px; }
        .difficulty-row span { color:var(--text-dim); font-size:.84rem; }
        .diff-track { height:8px; border-radius:999px; background:var(--border); overflow:hidden; }
        .diff-fill { height:100%; border-radius:999px; background:linear-gradient(90deg,var(--accent),var(--blue)); }
        .activity-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:6px; }
        .activity-cell { aspect-ratio:1; border-radius:4px; background:var(--bg-card2); border:1px solid var(--border); }
        .activity-cell.l1 { background:rgba(0,232,122,.18); border-color:rgba(0,232,122,.2); }
        .activity-cell.l2 { background:rgba(0,232,122,.34); border-color:rgba(0,232,122,.3); }
        .activity-cell.l3 { background:rgba(0,232,122,.58); border-color:rgba(0,232,122,.46); }
        details.profile-card { padding:0; }
        details summary {
            list-style:none; cursor:pointer; padding:16px 18px; display:flex; justify-content:space-between; align-items:center;
            font-weight:800; color:var(--text);
        }
        details summary::-webkit-details-marker { display:none; }
        details summary::after { content:"+"; color:var(--accent); font-size:1.2rem; }
        details[open] summary::after { content:"-"; }
        .details-body { padding:0 18px 18px; color:var(--text-dim); }
        @media(max-width:1100px) {
            .org-profile-hero, .org-profile-grid { grid-template-columns:1fr; }
            .org-metrics { grid-template-columns:repeat(2,1fr); }
        }
        @media(max-width:620px) {
            .org-profile-top { flex-direction:column; }
            .org-metrics, .role-grid { grid-template-columns:1fr; }
            .list-item { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>
<?php require_once '../includes/organization_shell.php'; ?>
<div class="org-content">
    <section class="org-profile-hero">
        <div class="profile-card profile-hero-main">
            <div class="org-profile-top">
                <div class="org-logo-large">
                    <?php if ($orgLogo): ?>
                        <img src="<?= htmlspecialchars($orgLogo) ?>" alt="<?= htmlspecialchars($orgName) ?> logo">
                    <?php else: ?>
                        <?= htmlspecialchars(orgInitials($orgName)) ?>
                    <?php endif; ?>
                </div>
                <div class="org-profile-title">
                    <h2><?= htmlspecialchars($orgName) ?></h2>
                    <p><?= htmlspecialchars($orgDescription ?: 'A CodeArena organization workspace for hosting contests, managing members, and building a curated problem bank.') ?></p>
                </div>
            </div>
            <div class="org-chip-row">
                <span class="org-chip green"><?= htmlspecialchars(ucfirst((string)$orgStatus)) ?></span>
                <span class="org-chip"><?= htmlspecialchars(ucfirst((string)$orgType)) ?></span>
                <span class="org-chip">Created <?= $createdAt ? htmlspecialchars(date('M Y', strtotime($createdAt))) : 'recently' ?></span>
                <span class="org-chip">Read-only profile</span>
            </div>
        </div>

        <aside class="profile-card owner-card">
            <div class="panel-title">
                <h3>Organization Owner</h3>
                <span>Workspace lead</span>
            </div>
            <div class="owner-profile">
                <div class="owner-avatar"><?= htmlspecialchars(orgInitials($owner['display_name'] ?? 'Owner')) ?></div>
                <div>
                    <strong><?= htmlspecialchars($owner['display_name'] ?? 'Unassigned owner') ?></strong>
                    <span><?= htmlspecialchars($owner['email'] ?? 'No email available') ?></span>
                </div>
            </div>
            <div class="readonly-note">This profile is informational. Manage organization settings from the Settings page.</div>
        </aside>
    </section>

    <section class="org-metrics">
        <div class="metric-card"><strong><?= $memberCount ?></strong><span>Total Members</span></div>
        <div class="metric-card"><strong><?= $activeContests ?></strong><span>Active Contests</span></div>
        <div class="metric-card"><strong><?= $upcomingContests ?></strong><span>Upcoming Contests</span></div>
        <div class="metric-card"><strong><?= $pastContests ?></strong><span>Past Contests</span></div>
        <div class="metric-card"><strong><?= $problemCount ?></strong><span>Total Problems</span></div>
        <div class="metric-card"><strong><?= $participationRate ?>%</strong><span>Participation Rate</span></div>
        <div class="metric-card"><strong><?= $successRate ?>%</strong><span>Submission Success</span></div>
        <div class="metric-card"><strong><?= $totalSubmissions ?></strong><span>Total Submissions</span></div>
    </section>

    <section class="org-profile-grid">
        <div class="profile-card panel">
            <div class="panel-title">
                <h3>Members and roles</h3>
                <span>Hover avatars for quick info</span>
            </div>
            <div class="role-grid">
                <div class="role-box"><strong><?= (int)($roleTotals['org_owner'] ?? 0) ?></strong><span>Owners</span></div>
                <div class="role-box"><strong><?= (int)(($roleTotals['org_admin'] ?? 0) + ($roleTotals['admin'] ?? 0)) ?></strong><span>Admins</span></div>
                <div class="role-box"><strong><?= (int)(($roleTotals['org_member'] ?? 0) + ($roleTotals['member'] ?? 0)) ?></strong><span>Members</span></div>
            </div>
            <div class="avatar-strip">
                <?php if ($members): ?>
                    <?php foreach ($members as $member): ?>
                        <div class="member-avatar">
                            <?= htmlspecialchars(orgInitials($member['display_name'] ?? $member['username'] ?? 'M')) ?>
                            <span class="member-tip">
                                <strong><?= htmlspecialchars($member['display_name'] ?? $member['username']) ?></strong>
                                <?= htmlspecialchars($member['email'] ?? 'No email') ?><br>
                                Role: <?= htmlspecialchars(str_replace('_', ' ', $member['role'] ?? 'member')) ?><br>
                                Joined <?= !empty($member['joined_at']) ? htmlspecialchars(date('M d, Y', strtotime($member['joined_at']))) : 'recently' ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:var(--text-muted)">No members found for this organization yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="profile-card panel">
            <div class="panel-title">
                <h3>Problem difficulty</h3>
                <span><?= $problemCount ?> total</span>
            </div>
            <?php foreach ($difficultyTotals as $difficulty => $count): ?>
                <div class="difficulty-row">
                    <span><?= htmlspecialchars($difficulty) ?></span>
                    <div class="diff-track"><div class="diff-fill" style="width:<?= round(($count / $maxDifficulty) * 100) ?>%"></div></div>
                    <span style="text-align:right"><?= $count ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="profile-card panel">
            <div class="panel-title">
                <h3>Recent contests</h3>
                <span><?= $totalContests ?> hosted</span>
            </div>
            <div class="list-stack">
                <?php if ($recentContests): ?>
                    <?php foreach ($recentContests as $contest): ?>
                        <?php $status = $contest['org_status'] ?: $contest['status']; ?>
                        <div class="list-item">
                            <div>
                                <strong><?= htmlspecialchars($contest['title']) ?></strong>
                                <small><?= (int)$contest['participants'] ?> participants / <?= (int)$contest['submissions'] ?> submissions</small>
                            </div>
                            <span class="pill <?= in_array($status, ['live', 'active'], true) ? 'green' : 'blue' ?>"><?= htmlspecialchars(orgStatusLabel((string)$status)) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:var(--text-muted)">No contests have been created yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="profile-card panel">
            <div class="panel-title">
                <h3>Submission activity</h3>
                <span>Last 35 days</span>
            </div>
            <div class="activity-grid">
                <?php foreach ($activityValues as $value): ?>
                    <?php $level = $value === 0 ? '' : ($value < $maxActivity * .34 ? 'l1' : ($value < $maxActivity * .68 ? 'l2' : 'l3')); ?>
                    <div class="activity-cell <?= htmlspecialchars($level) ?>" title="<?= (int)$value ?> submissions"></div>
                <?php endforeach; ?>
            </div>
        </div>

        <details class="profile-card" open>
            <summary>Recent problem bank</summary>
            <div class="details-body">
                <div class="list-stack">
                    <?php if ($topProblems): ?>
                        <?php foreach ($topProblems as $problem): ?>
                            <div class="list-item">
                                <div>
                                    <strong><?= htmlspecialchars($problem['title']) ?></strong>
                                    <small><?= htmlspecialchars($problem['tags'] ?: 'No tags') ?></small>
                                </div>
                                <span class="pill"><?= htmlspecialchars($problem['difficulty']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color:var(--text-muted)">No organization problems found yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </details>

        <details class="profile-card">
            <summary>Recent announcements</summary>
            <div class="details-body">
                <div class="list-stack">
                    <?php if ($recentAnnouncements): ?>
                        <?php foreach ($recentAnnouncements as $announcement): ?>
                            <div class="list-item">
                                <div>
                                    <strong><?= htmlspecialchars($announcement['title']) ?></strong>
                                    <small><?= htmlspecialchars(ucfirst($announcement['type'])) ?> / <?= htmlspecialchars(date('M d, Y', strtotime($announcement['created_at']))) ?></small>
                                </div>
                                <span class="pill blue">Published</span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color:var(--text-muted)">No announcements published yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </details>
    </section>
</div>
<?php require_once '../includes/organization_shell_end.php'; ?>
</body>
</html>
