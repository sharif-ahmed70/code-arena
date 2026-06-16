<?php
require_once 'includes/session.php';
require_once 'config/db.php';
$loggedIn = isLoggedIn();

function landingCount(PDO $pdo, string $sql, array $params = []): int {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('landing count failed: ' . $e->getMessage());
        return 0;
    }
}

function landingRows(PDO $pdo, string $sql, array $params = []): array {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        error_log('landing rows failed: ' . $e->getMessage());
        return [];
    }
}

function landingFormatCount(int $value): string {
    if ($value >= 1000) {
        $short = $value / 1000;
        return ($short >= 10 ? (string)round($short) : rtrim(rtrim(number_format($short, 1), '0'), '.')) . 'K+';
    }
    return (string)$value;
}

$memberCount = landingCount($pdo, "SELECT COUNT(*) FROM users WHERE role <> 'admin' AND COALESCE(is_deleted, 0) = 0");
$contestCount = landingCount($pdo, 'SELECT COUNT(*) FROM contests WHERE COALESCE(is_published, 1) = 1');
$problemCount = landingCount($pdo, 'SELECT COUNT(*) FROM problems WHERE is_public = 1 AND COALESCE(is_deleted, 0) = 0');
$activeUserCount = landingCount(
    $pdo,
    'SELECT COUNT(DISTINCT user_id) FROM submissions WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)'
);
if ($activeUserCount === 0) {
    $activeUserCount = landingCount($pdo, "SELECT COUNT(*) FROM users WHERE role <> 'admin' AND COALESCE(is_deleted, 0) = 0");
}

$upcomingContests = landingRows(
    $pdo,
    'SELECT c.id, c.title, c.description, c.start_time, c.end_time, c.status,
            COUNT(DISTINCT cp.user_id) AS participant_count
     FROM contests c
     LEFT JOIN contest_participants cp
        ON cp.contest_id = c.id
       AND COALESCE(cp.status, "registered") NOT IN ("removed", "banned", "rejected")
     WHERE COALESCE(c.is_published, 1) = 1
       AND COALESCE(c.visibility, "public") IN ("public", "org")
       AND COALESCE(c.org_status, "scheduled") IN ("scheduled", "live", "ended")
       AND c.status IN ("upcoming", "active")
     GROUP BY c.id
     ORDER BY CASE c.status WHEN "active" THEN 0 ELSE 1 END, c.start_time ASC
     LIMIT 12'
);

$categoryDefs = [
    ['Arrays', 'arrays', '▦'],
    ['Dynamic Programming', 'dynamic-programming', 'ϟ'],
    ['Graphs', 'graphs', '⌘'],
    ['Greedy', 'greedy', '◎'],
    ['String', 'string', 'T'],
    ['Math', 'math', 'Σ'],
];
$categoryCounts = [];
foreach ($categoryDefs as $category) {
    $categoryCounts[$category[1]] = landingCount(
        $pdo,
        'SELECT COUNT(*) FROM problems
         WHERE is_public = 1
           AND COALESCE(is_deleted, 0) = 0
           AND (tags LIKE ? OR FIND_IN_SET(?, REPLACE(tags, " ", "")))',
        ['%' . $category[1] . '%', $category[1]]
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CodeArena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css?v=20260615-ui5">
    <style>
        :root {
            --bg: #080b14;
            --panel: #101522;
            --panel-2: #121827;
            --line: rgba(151, 116, 255, .25);
            --line-soft: rgba(255, 255, 255, .1);
            --text: #f4f2fb;
            --muted: #9ea6bd;
            --muted-2: #747d96;
            --purple: #8e4dff;
            --purple-2: #b060ff;
            --violet: #6f35ff;
            --gold: #c88b16;
            --green: #57d680;
            --radius: 10px;
            --shadow: 0 18px 60px rgba(103, 39, 255, .22);
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            min-height: 100vh;
            color: var(--text);
            background:
                radial-gradient(circle at 72% 19%, rgba(143, 47, 255, .24), transparent 18%),
                radial-gradient(circle at 58% 22%, rgba(97, 48, 255, .14), transparent 22%),
                linear-gradient(180deg, #080b14 0%, #090d16 48%, #080b14 100%);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            letter-spacing: 0;
            border: 3px solid rgba(179, 114, 255, .72);
        }

        a { color: inherit; text-decoration: none; }
        button, input { font: inherit; }

        .topbar {
            height: 76px;
            border-bottom: 1px solid var(--line-soft);
            background: rgba(7, 10, 18, .84);
            backdrop-filter: blur(18px);
        }
        .nav {
            width: min(1120px, calc(100% - 42px));
            height: 100%;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 210px 1fr 210px;
            align-items: center;
            gap: 18px;
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 17px;
            font-weight: 800;
        }
        .brand-mark {
            width: 29px;
            height: 29px;
            position: relative;
            display: grid;
            place-items: center;
            border: 2px solid rgba(180, 117, 255, .76);
            border-radius: 9px;
            transform: rotate(45deg);
            box-shadow: 0 0 18px rgba(146, 69, 255, .36);
        }
        .brand-mark::before {
            content: "";
            width: 8px;
            height: 8px;
            border-top: 2px solid #d5bcff;
            border-left: 2px solid #d5bcff;
        }
        .brand span:last-child { color: var(--purple-2); }

        .nav-links {
            justify-self: center;
            height: 100%;
            display: flex;
            align-items: center;
            gap: 31px;
            font-size: 12px;
            font-weight: 700;
        }
        .nav-links a {
            height: 100%;
            display: inline-flex;
            align-items: center;
            color: #f0eef8;
            opacity: .92;
            position: relative;
        }
        .nav-links a.active { color: var(--purple-2); }
        .nav-links a.active::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 3px;
            border-radius: 10px 10px 0 0;
            background: linear-gradient(90deg, var(--purple), var(--purple-2));
        }
        .nav-actions {
            justify-self: end;
            display: flex;
            gap: 10px;
        }
        .btn {
            min-width: 82px;
            min-height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 7px;
            border: 1px solid rgba(255, 255, 255, .16);
            color: #fff;
            font-size: 12px;
            font-weight: 800;
            transition: transform .3s ease, box-shadow .3s ease, border-color .3s ease;
            cursor: pointer;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn-ghost { background: #0b101c; color: #dad7e8; }
        .btn-purple {
            background: linear-gradient(135deg, #7b35ff, #b24dff);
            border-color: rgba(205, 137, 255, .55);
            box-shadow: 0 10px 26px rgba(125, 54, 255, .34);
        }
        .btn-outline { background: transparent; color: #f5f1ff; border-color: rgba(122, 125, 157, .75); }

        .container { width: min(1120px, calc(100% - 42px)); margin: 0 auto; }

        .hero {
            min-height: 500px;
            display: grid;
            grid-template-columns: 1fr 535px;
            align-items: center;
            gap: 86px;
            padding: 48px 0 44px;
        }
        .hero-copy { padding-top: 16px; }
        .hero h1 {
            margin: 0;
            max-width: 470px;
            font-size: clamp(42px, 4.3vw, 58px);
            line-height: 1.08;
            letter-spacing: -.035em;
            font-weight: 850;
        }
        .hero h1 .grad {
            display: inline-block;
            background: linear-gradient(90deg, #884dff 2%, #d9bdff 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            padding-bottom: 4px;
        }
        .hero p {
            width: 385px;
            margin: 30px 0 34px;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.75;
            font-weight: 500;
        }
        .hero-buttons { display: flex; gap: 16px; }
        .hero-buttons .btn { min-width: 126px; min-height: 43px; }

        .code-window {
            min-height: 344px;
            border: 1px solid rgba(157, 85, 255, .58);
            border-radius: 13px;
            background:
                radial-gradient(circle at 74% 40%, rgba(106, 59, 255, .2), transparent 42%),
                linear-gradient(135deg, rgba(19, 24, 38, .98), rgba(9, 13, 24, .98));
            box-shadow: 0 0 0 1px rgba(107, 56, 255, .12), 0 32px 90px rgba(122, 36, 255, .36);
            overflow: hidden;
        }
        .code-head {
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 18px 0 20px;
            color: #bfc6db;
            font-size: 12px;
            font-weight: 700;
        }
        .dots { display: flex; gap: 8px; }
        .dot { width: 10px; height: 10px; border-radius: 50%; }
        .red { background: #ff5e57; }
        .yellow { background: #ffbd2e; }
        .green { background: #28c840; }
        pre {
            margin: 0;
            padding: 0 28px 22px;
            color: #bfcae9;
            font: 14px/1.72 "JetBrains Mono", Consolas, monospace;
            white-space: pre-wrap;
        }
        .kw { color: #d16dff; }
        .type { color: #cfa0ff; }
        .fn { color: #89c9ff; }
        .str { color: #69df91; }
        .num { color: #ff88c8; }

        .stat-row {
            display: grid;
            grid-template-columns: repeat(4, max-content);
            gap: 38px;
            margin-top: 60px;
        }
        .stat {
            display: grid;
            grid-template-columns: 40px auto;
            align-items: center;
            gap: 12px;
        }
        .icon {
            width: 39px;
            height: 39px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            border: 2px solid rgba(147, 62, 255, .78);
            color: #ba7cff;
            background: rgba(105, 45, 194, .13);
            box-shadow: 0 0 18px rgba(139, 57, 255, .28);
            font-size: 20px;
            line-height: 1;
        }
        .stat strong { display: block; font-size: 18px; line-height: 1; }
        .stat span { display: block; margin-top: 4px; color: var(--muted); font-size: 11px; }

        .divider { border-top: 1px solid var(--line-soft); }
        .section { padding: 24px 0 0; }
        .section-title { text-align: center; margin: 0 0 21px; }
        .section-title h2 {
            margin: 0;
            font-size: 30px;
            line-height: 1.2;
            letter-spacing: -.03em;
        }
        .section-title h2 span { color: var(--purple-2); }
        .section-title p { margin: 7px 0 0; color: var(--muted); font-size: 13px; }

        .features {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 24px;
            margin-top: 26px;
        }
        .feature-card {
            min-height: 164px;
            padding: 21px 20px 18px;
            text-align: center;
            border: 1px solid rgba(123, 132, 163, .28);
            border-radius: 9px;
            background: linear-gradient(180deg, rgba(22, 27, 43, .92), rgba(15, 19, 32, .92));
            transition: transform .3s ease, border-color .3s ease, box-shadow .3s ease;
        }
        .feature-card:hover,
        .contest-card:hover,
        .category-card:hover {
            transform: translateY(-4px);
            border-color: rgba(162, 75, 255, .8);
            box-shadow: var(--shadow);
        }
        .feature-card .icon { margin: 0 auto 16px; width: 45px; height: 45px; }
        .feature-card h3 { margin: 0 0 11px; font-size: 16px; }
        .feature-card p {
            margin: 0 auto;
            color: #b0b8cd;
            font-size: 12.5px;
            line-height: 1.55;
            max-width: 150px;
        }

        .section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 23px 0 14px;
            border-top: 1px solid rgba(255, 255, 255, .08);
            padding-top: 21px;
        }
        .section-head.no-line { border-top: 0; padding-top: 0; margin-top: 29px; }
        .section-head h2 { margin: 0; font-size: 21px; letter-spacing: -.02em; }
        .text-link { color: #b576ff; font-size: 13px; font-weight: 800; }

        .contests {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
        }
        .contest-slider {
            position: relative;
            overflow: hidden;
        }
        .contest-track {
            display: flex;
            gap: 22px;
            transition: transform .55s ease;
            will-change: transform;
        }
        .contest-card {
            min-height: 158px;
            flex: 0 0 calc((100% - 44px) / 3);
            position: relative;
            padding: 18px 18px 14px;
            border-radius: 9px;
            border: 1px solid rgba(151, 66, 255, .88);
            background: linear-gradient(180deg, rgba(20, 25, 41, .96), rgba(12, 17, 30, .96));
            box-shadow: 0 15px 35px rgba(101, 44, 220, .12);
            transition: transform .3s ease, box-shadow .3s ease;
        }
        .contest-card h3 { margin: 0 88px 7px 0; font-size: 17px; letter-spacing: -.015em; }
        .pill {
            position: absolute;
            top: 15px;
            right: 17px;
            padding: 4px 12px;
            border-radius: 999px;
            background: rgba(172, 116, 12, .56);
            color: #ffd375;
            font-size: 10px;
            font-weight: 900;
        }
        .date {
            color: #8791aa;
            font-size: 11px;
            font-weight: 650;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .contest-card p { min-height: 42px; color: #bac1d5; font-size: 12.5px; line-height: 1.55; margin: 14px 0 14px; }
        .contest-foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .participants { color: #c7cde0; font-size: 11px; font-weight: 700; }
        .register {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 104px;
            min-height: 34px;
            border-radius: 7px;
            border: 0;
            color: #fff;
            font-weight: 900;
            font-size: 11px;
            background: linear-gradient(135deg, #8339ff, #b44dff);
            box-shadow: 0 10px 24px rgba(130, 55, 255, .35);
        }
        .slider-dots {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 14px;
        }
        .slider-dot {
            width: 8px;
            height: 8px;
            border: 0;
            border-radius: 999px;
            background: rgba(255,255,255,.24);
            cursor: pointer;
            transition: width .25s ease, background .25s ease;
        }
        .slider-dot.active {
            width: 24px;
            background: linear-gradient(90deg, var(--purple), var(--purple-2));
        }

        .categories {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 14px;
        }
        .category-card {
            min-height: 76px;
            display: grid;
            grid-template-columns: 43px auto;
            align-items: center;
            gap: 12px;
            padding: 13px 14px;
            border-radius: 8px;
            border: 1px solid rgba(123, 132, 163, .28);
            background: linear-gradient(180deg, rgba(22, 27, 43, .92), rgba(14, 19, 32, .92));
            transition: transform .3s ease, border-color .3s ease, box-shadow .3s ease;
        }
        .category-card .icon { width: 39px; height: 39px; }
        .category-card strong { display: block; font-size: 12.5px; }
        .category-card span { color: var(--muted); font-size: 11px; }

        .cta {
            margin: 29px 0 22px;
            min-height: 134px;
            display: grid;
            grid-template-columns: 310px 1fr;
            align-items: center;
            border-radius: 10px;
            border: 2px solid rgba(172, 69, 255, .92);
            background:
                radial-gradient(circle at 13% 54%, rgba(70, 53, 255, .62), transparent 16%),
                linear-gradient(90deg, rgba(18, 22, 36, .98), rgba(13, 16, 28, .98));
            overflow: hidden;
        }
        .cta-art {
            height: 134px;
            position: relative;
            background: radial-gradient(circle at 42% 58%, rgba(98, 55, 255, .5), transparent 45%);
        }
        .person {
            position: absolute;
            bottom: 18px;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #c9c9ff;
            box-shadow: 0 0 26px rgba(112, 85, 255, .75);
        }
        .person::after {
            content: "";
            position: absolute;
            left: -18px;
            top: 44px;
            width: 88px;
            height: 56px;
            border-radius: 26px 26px 8px 8px;
            background: linear-gradient(135deg, #643cff, #151a33);
        }
        .p1 { left: 86px; }
        .p2 { left: 188px; transform: scale(.82); opacity: .9; }
        .screen {
            position: absolute;
            bottom: 20px;
            left: 126px;
            width: 78px;
            height: 47px;
            border-radius: 6px;
            background: #10182d;
            border: 1px solid rgba(136, 96, 255, .8);
            box-shadow: 0 0 20px rgba(119, 68, 255, .55);
        }
        .cta-copy { padding: 0 28px; }
        .cta h2 { margin: 0 0 9px; font-size: 29px; letter-spacing: -.03em; }
        .cta p { margin: 0 0 20px; color: #c2c8d9; font-size: 13px; }
        .cta .btn { width: 151px; min-height: 38px; }

        .footer-wrap {
            border-top: 1px solid rgba(255, 255, 255, .08);
            padding-top: 20px;
        }
        .footer {
            display: grid;
            grid-template-columns: 250px repeat(4, 1fr);
            gap: 46px;
            padding: 0 12px 20px;
        }
        .footer .brand { margin-bottom: 14px; }
        .footer p { color: var(--muted); font-size: 12px; line-height: 1.65; margin: 0 0 14px; }
        .socials { display: flex; gap: 10px; }
        .social {
            width: 28px;
            height: 28px;
            display: grid;
            place-items: center;
            border-radius: 8px;
            background: #161c2b;
            color: #bbc2d5;
            font-size: 12px;
            font-weight: 800;
        }
        .foot-col h3 { margin: 0 0 13px; font-size: 13px; }
        .foot-col a {
            display: block;
            margin: 0 0 7px;
            color: var(--muted);
            font-size: 12px;
        }
        .copyright {
            border-top: 1px solid rgba(255, 255, 255, .08);
            color: var(--muted);
            text-align: center;
            font-size: 12px;
            padding: 16px 0 20px;
        }

        @media (max-width: 980px) {
            .nav { grid-template-columns: 1fr auto; }
            .nav-links { display: none; }
            .hero { grid-template-columns: 1fr; gap: 34px; padding-top: 40px; }
            .hero p { width: auto; max-width: 450px; }
            .stat-row { grid-template-columns: repeat(2, max-content); }
            .features { grid-template-columns: repeat(2, 1fr); }
            .contests { grid-template-columns: 1fr; }
            .contest-card { flex-basis: 100%; }
            .categories { grid-template-columns: repeat(2, 1fr); }
            .cta { grid-template-columns: 1fr; }
            .cta-art { display: none; }
            .cta-copy { padding: 28px; }
            .footer { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 620px) {
            body { border-width: 2px; }
            .topbar { height: auto; padding: 14px 0; }
            .nav { grid-template-columns: 1fr; justify-items: start; }
            .nav-actions { justify-self: start; }
            .hero h1 { font-size: 42px; }
            .hero-buttons, .nav-actions { flex-wrap: wrap; }
            .stat-row { grid-template-columns: 1fr; gap: 18px; }
            .features, .categories, .footer { grid-template-columns: 1fr; }
            .code-window { min-height: 0; }
            pre { font-size: 11px; padding: 0 16px 18px; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <nav class="nav">
            <a class="brand" href="/code-arena/index.php">
                <span class="brand-mark"></span>
                <span>Code<span>Arena</span></span>
            </a>
            <div class="nav-links">
                <a class="active" href="/code-arena/index.php">Home</a>
                <a href="/code-arena/contests.php">Contests</a>
                <a href="/code-arena/problems.php">Practice</a>
                <a href="/code-arena/leaderboard.php">Leaderboard</a>
                <a href="/code-arena/discuss.php">Community</a>
                <a href="#">Blog</a>
            </div>
            <div class="nav-actions">
                <?php if ($loggedIn): ?>
                    <a class="btn btn-ghost" href="/code-arena/dashboard.php">Dashboard</a>
                <?php else: ?>
                    <a class="btn btn-ghost" href="/code-arena/login.php">Login</a>
                    <a class="btn btn-purple" href="/code-arena/register.php">Register</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main>
        <section class="container hero">
            <div class="hero-copy">
                <h1>Master Algorithms.<br><span class="grad">Compete. Grow.</span></h1>
                <p>Join a community of coders, solve challenging problems, compete in contests, and level up your skills.</p>
                <div class="hero-buttons">
                    <a class="btn btn-purple" href="/code-arena/register.php">Join the Arena</a>
                    <a class="btn btn-outline" href="/code-arena/contests.php">Explore Contests</a>
                </div>
                <div class="stat-row">
                    <div class="stat"><span class="icon">♙</span><div><strong><?= htmlspecialchars(landingFormatCount($memberCount)) ?></strong><span>Members</span></div></div>
                    <div class="stat"><span class="icon">♕</span><div><strong><?= htmlspecialchars(landingFormatCount($contestCount)) ?></strong><span>Contests</span></div></div>
                    <div class="stat"><span class="icon">&lt;/&gt;</span><div><strong><?= htmlspecialchars(landingFormatCount($problemCount)) ?></strong><span>Problems</span></div></div>
                    <div class="stat"><span class="icon">♧</span><div><strong><?= htmlspecialchars(landingFormatCount($activeUserCount)) ?></strong><span>Active Users</span></div></div>
                </div>
            </div>
            <div class="code-window" aria-label="Code preview">
                <div class="code-head">
                    <div class="dots"><span class="dot red"></span><span class="dot yellow"></span><span class="dot green"></span></div>
                    <span>main.cpp</span>
                </div>
<pre><span class="kw">#include</span> &lt;bits/stdc++.h&gt;
<span class="kw">using namespace</span> std;
<span class="type">int</span> main(){
    ios::sync_with_stdio(false);
    cin.tie(nullptr);
    <span class="type">int</span> n;
    cin &gt;&gt; n;
    vector&lt;<span class="type">int</span>&gt; a(n);
    <span class="kw">for</span>(<span class="type">int</span> i=<span class="num">0</span>;i&lt;n;i++) cin &gt;&gt; a[i];
    sort(a.begin(), a.end());
    cout &lt;&lt; <span class="str">"Code. Compete. Conquer!"</span> &lt;&lt; endl;
    <span class="kw">return</span> <span class="num">0</span>;
}</pre>
            </div>
        </section>

        <div class="divider">
            <section class="container section">
                <div class="section-title">
                    <h2>Everything you need to <span>level up.</span></h2>
                    <p>Practice, compete, and collaborate with coders worldwide.</p>
                </div>
                <div class="features">
                    <article class="feature-card"><span class="icon">&lt;/&gt;</span><h3>Practice Problems</h3><p>Solve curated problems ranging from beginner to advanced level.</p></article>
                    <article class="feature-card"><span class="icon">♕</span><h3>Contests</h3><p>Compete in weekly and monthly contests and improve your ranking.</p></article>
                    <article class="feature-card"><span class="icon">▥</span><h3>Leaderboard</h3><p>Track your progress and see how you rank among top coders.</p></article>
                    <article class="feature-card"><span class="icon">♧</span><h3>Community</h3><p>Discuss, share ideas, and grow together with fellow programmers.</p></article>
                    <article class="feature-card"><span class="icon">□</span><h3>Earn Rewards</h3><p>Win exciting prizes and recognition for your achievements.</p></article>
                </div>

                <div class="section-head">
                    <h2>Upcoming Contests</h2>
                    <a class="text-link" href="/code-arena/contests.php">View all contests →</a>
                </div>
                <div class="contest-slider" id="contest-slider">
                    <div class="contest-track" id="contest-track">
                        <?php if ($upcomingContests): ?>
                            <?php foreach ($upcomingContests as $contest): ?>
                                <?php
                                    $statusLabel = ($contest['status'] ?? '') === 'active' ? 'Live' : 'Upcoming';
                                    $startLabel = $contest['start_time'] ? date('M d, Y - h:i A', strtotime($contest['start_time'])) : 'Schedule pending';
                                    $description = trim((string)($contest['description'] ?? ''));
                                    if ($description === '') $description = 'Join this contest and solve challenging problems.';
                                ?>
                                <article class="contest-card">
                                    <span class="pill"><?= htmlspecialchars($statusLabel) ?></span>
                                    <h3><?= htmlspecialchars($contest['title']) ?></h3>
                                    <div class="date">◷ <?= htmlspecialchars($startLabel) ?></div>
                                    <p><?= htmlspecialchars(mb_strimwidth($description, 0, 95, '...')) ?></p>
                                    <div class="contest-foot">
                                        <span class="participants">♧ <?= (int)$contest['participant_count'] ?> Participants</span>
                                        <a class="register" href="/code-arena/contest.php?id=<?= (int)$contest['id'] ?>">Register</a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <article class="contest-card">
                                <span class="pill">Upcoming</span>
                                <h3>No contests scheduled</h3>
                                <div class="date">◷ Check back soon</div>
                                <p>New contests will appear here as soon as they are published.</p>
                                <div class="contest-foot"><span class="participants">♧ 0 Participants</span><a class="register" href="/code-arena/contests.php">Explore</a></div>
                            </article>
                        <?php endif; ?>
                    </div>
                    <div class="slider-dots" id="contest-dots" aria-label="Contest slider navigation"></div>
                </div>
                <div class="contests" style="display:none">
                    <article class="contest-card">
                        <span class="pill">Upcoming</span>
                        <h3>CodeArena Weekly Contest #45</h3>
                        <div class="date">◷ May 25, 2025 · 08:00 PM</div>
                        <p>Weekly contest with interesting problems and fun challenges.</p>
                        <div class="contest-foot"><span class="participants">♧ 120 Participants</span><a class="register" href="/code-arena/register.php">Register</a></div>
                    </article>
                    <article class="contest-card">
                        <span class="pill">Upcoming</span>
                        <h3>CodeArena Monthly Challenge</h3>
                        <div class="date">◷ June 05, 2025 · 08:00 PM</div>
                        <p>Monthly challenge with advanced problems and great rewards.</p>
                        <div class="contest-foot"><span class="participants">♧ 230 Participants</span><a class="register" href="/code-arena/register.php">Register</a></div>
                    </article>
                    <article class="contest-card">
                        <span class="pill">Upcoming</span>
                        <h3>Summer Special Contest</h3>
                        <div class="date">◷ June 15, 2025 · 07:00 PM</div>
                        <p>Special summer contest with exciting challenges!</p>
                        <div class="contest-foot"><span class="participants">♧ 150 Participants</span><a class="register" href="/code-arena/register.php">Register</a></div>
                    </article>
                </div>

                <div class="section-head no-line">
                    <h2>Popular Problem Categories</h2>
                    <a class="text-link" href="/code-arena/problems.php">Explore all problems →</a>
                </div>
                <div class="categories">
                    <?php foreach ($categoryDefs as $category): ?>
                        <a class="category-card" href="/code-arena/problems.php?tag=<?= urlencode($category[1]) ?>">
                            <span class="icon"><?= htmlspecialchars($category[2]) ?></span>
                            <div>
                                <strong><?= htmlspecialchars($category[0]) ?></strong>
                                <span><?= (int)($categoryCounts[$category[1]] ?? 0) ?> Problems</span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="categories" style="display:none">
                    <a class="category-card" href="/code-arena/problems.php?tag=arrays"><span class="icon">▦</span><div><strong>Arrays</strong><span>256 Problems</span></div></a>
                    <a class="category-card" href="/code-arena/problems.php?tag=dynamic-programming"><span class="icon">ϟ</span><div><strong>Dynamic Programming</strong><span>312 Problems</span></div></a>
                    <a class="category-card" href="/code-arena/problems.php?tag=graphs"><span class="icon">⌘</span><div><strong>Graphs</strong><span>198 Problems</span></div></a>
                    <a class="category-card" href="/code-arena/problems.php?tag=greedy"><span class="icon">◎</span><div><strong>Greedy</strong><span>145 Problems</span></div></a>
                    <a class="category-card" href="/code-arena/problems.php?tag=string"><span class="icon">T</span><div><strong>String</strong><span>210 Problems</span></div></a>
                    <a class="category-card" href="/code-arena/problems.php?tag=math"><span class="icon">Σ</span><div><strong>Math</strong><span>178 Problems</span></div></a>
                </div>

                <section class="cta">
                    <div class="cta-art" aria-hidden="true"><span class="person p1"></span><span class="person p2"></span><span class="screen"></span></div>
                    <div class="cta-copy">
                        <h2>Ready to start your journey?</h2>
                        <p>Join thousands of coders who are learning, competing, and growing together.</p>
                        <a class="btn btn-purple" href="/code-arena/register.php">Join the Community</a>
                    </div>
                </section>
            </section>
        </div>
    </main>

    <footer class="footer-wrap">
        <div class="container footer">
            <div>
                <a class="brand" href="/code-arena/index.php"><span class="brand-mark"></span><span>Code<span>Arena</span></span></a>
                <p>A platform for coders to practice, compete and grow together.</p>
                <div class="socials"><span class="social">G</span><span class="social">X</span><span class="social">D</span><span class="social">Y</span></div>
            </div>
            <div class="foot-col"><h3>Platform</h3><a href="/code-arena/contests.php">Contests</a><a href="/code-arena/problems.php">Practice</a><a href="/code-arena/leaderboard.php">Leaderboard</a><a href="#">Blog</a></div>
            <div class="foot-col"><h3>Community</h3><a href="/code-arena/discuss.php">Discuss</a><a href="#">Groups</a><a href="/code-arena/contests.php">Events</a><a href="#">Members</a></div>
            <div class="foot-col"><h3>Resources</h3><a href="#">Tutorials</a><a href="#">FAQs</a><a href="#">Rules</a><a href="#">Support</a></div>
            <div class="foot-col"><h3>Company</h3><a href="#">About Us</a><a href="#">Contact</a><a href="#">Terms</a><a href="#">Privacy</a></div>
        </div>
        <div class="container copyright">© 2025 CodeArena. All rights reserved.</div>
    </footer>

    <script>
        const contestTrack = document.getElementById('contest-track');
        const contestDots = document.getElementById('contest-dots');
        const visibleContestCards = contestTrack ? [...contestTrack.querySelectorAll('.contest-card')] : [];
        let contestSlideIndex = 0;
        let contestSlideTimer = null;

        function contestCardsPerView() {
            return window.innerWidth <= 980 ? 1 : 3;
        }

        function renderContestDots() {
            if (!contestDots || !visibleContestCards.length) return;
            const pages = Math.max(1, visibleContestCards.length - contestCardsPerView() + 1);
            contestDots.innerHTML = Array.from({ length: pages }, (_, index) =>
                `<button class="slider-dot ${index === contestSlideIndex ? 'active' : ''}" aria-label="Show contest slide ${index + 1}" onclick="goToContestSlide(${index})"></button>`
            ).join('');
        }

        function goToContestSlide(index) {
            if (!contestTrack || !visibleContestCards.length) return;
            const maxIndex = Math.max(0, visibleContestCards.length - contestCardsPerView());
            contestSlideIndex = Math.max(0, Math.min(index, maxIndex));
            const cardWidth = visibleContestCards[0].getBoundingClientRect().width;
            const gap = 22;
            contestTrack.style.transform = `translateX(-${contestSlideIndex * (cardWidth + gap)}px)`;
            renderContestDots();
        }

        function startContestSlider() {
            if (contestSlideTimer) clearInterval(contestSlideTimer);
            if (!visibleContestCards.length || visibleContestCards.length <= contestCardsPerView()) {
                renderContestDots();
                return;
            }
            contestSlideTimer = setInterval(() => {
                const maxIndex = Math.max(0, visibleContestCards.length - contestCardsPerView());
                goToContestSlide(contestSlideIndex >= maxIndex ? 0 : contestSlideIndex + 1);
            }, 3500);
        }

        window.addEventListener('resize', () => {
            goToContestSlide(contestSlideIndex);
            startContestSlider();
        });
        renderContestDots();
        startContestSlider();

        document.querySelectorAll('.feature-card,.contest-card,.category-card').forEach(card => {
            card.addEventListener('mousemove', event => {
                const rect = card.getBoundingClientRect();
                card.style.setProperty('--mx', `${event.clientX - rect.left}px`);
                card.style.setProperty('--my', `${event.clientY - rect.top}px`);
            });
        });
    </script>
</body>
</html>
