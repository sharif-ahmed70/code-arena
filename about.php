<?php
require_once 'includes/session.php';
require_once 'config/db.php';
$loggedIn = isLoggedIn();

function aboutCount(PDO $pdo, string $sql): int {
    try {
        return (int)$pdo->query($sql)->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

$memberCount = aboutCount($pdo, "SELECT COUNT(*) FROM users WHERE role <> 'admin' AND COALESCE(is_deleted, 0) = 0");
$contestCount = aboutCount($pdo, 'SELECT COUNT(*) FROM contests WHERE COALESCE(is_published, 1) = 1');
$problemCount = aboutCount($pdo, 'SELECT COUNT(*) FROM problems WHERE is_public = 1 AND COALESCE(is_deleted, 0) = 0');
$orgCount = aboutCount($pdo, 'SELECT COUNT(*) FROM organizations WHERE COALESCE(is_deleted, 0) = 0');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About CodeArena</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css?v=20260615-ui5">
    <style>
        :root {
            --page-bg:#080b14;
            --panel:#101522;
            --line:rgba(255,255,255,.1);
            --muted:#9ea6bd;
            --purple:#8e4dff;
            --purple-2:#b060ff;
            --green:#57d680;
        }
        * { box-sizing:border-box; }
        body {
            margin:0;
            min-height:100vh;
            color:#f4f2fb;
            background:
                radial-gradient(circle at 70% 16%, rgba(142,77,255,.24), transparent 22%),
                radial-gradient(circle at 18% 56%, rgba(87,214,128,.1), transparent 24%),
                linear-gradient(180deg,#080b14 0%,#090d16 52%,#080b14 100%);
            border:3px solid rgba(179,114,255,.72);
            font-family:Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        a { color:inherit; text-decoration:none; }
        .topbar {
            height:76px;
            border-bottom:1px solid var(--line);
            background:rgba(7,10,18,.84);
            backdrop-filter:blur(18px);
        }
        .nav {
            width:min(1120px, calc(100% - 42px));
            height:100%;
            margin:0 auto;
            display:grid;
            grid-template-columns:210px 1fr 210px;
            align-items:center;
            gap:18px;
        }
        .brand { display:inline-flex; align-items:center; gap:10px; font-size:17px; font-weight:800; }
        .brand-mark {
            width:29px; height:29px; display:grid; place-items:center;
            border:2px solid rgba(180,117,255,.76); border-radius:9px;
            transform:rotate(45deg); box-shadow:0 0 18px rgba(146,69,255,.36);
        }
        .brand-mark::before { content:""; width:8px; height:8px; border-top:2px solid #d5bcff; border-left:2px solid #d5bcff; }
        .brand span:last-child { color:var(--purple-2); }
        .nav-links { justify-self:center; height:100%; display:flex; align-items:center; gap:24px; font-size:12px; font-weight:700; }
        .nav-links a { height:100%; display:inline-flex; align-items:center; color:#f0eef8; opacity:.92; position:relative; }
        .nav-links a.active { color:var(--purple-2); }
        .nav-links a.active::after {
            content:""; position:absolute; left:0; right:0; bottom:0; height:3px;
            border-radius:10px 10px 0 0; background:linear-gradient(90deg,var(--purple),var(--purple-2));
        }
        .nav-actions { justify-self:end; display:flex; gap:10px; }
        .btn {
            min-width:92px; min-height:38px; display:inline-flex; align-items:center; justify-content:center;
            border-radius:8px; border:1px solid rgba(255,255,255,.16); color:#fff; font-size:12px; font-weight:800;
            transition:transform .3s ease, box-shadow .3s ease, border-color .3s ease;
        }
        .btn:hover { transform:translateY(-1px); }
        .btn-ghost { background:#0b101c; color:#dad7e8; }
        .btn-purple { background:linear-gradient(135deg,#7b35ff,#b24dff); border-color:rgba(205,137,255,.55); box-shadow:0 10px 26px rgba(125,54,255,.34); }
        .container { width:min(1120px, calc(100% - 42px)); margin:0 auto; }
        .hero {
            min-height:430px;
            display:grid;
            grid-template-columns:minmax(0,1.05fr) minmax(320px,.95fr);
            gap:48px;
            align-items:center;
            padding:68px 0 44px;
        }
        .eyebrow {
            display:inline-flex; align-items:center; gap:9px; margin-bottom:18px;
            color:#c69aff; font-size:11px; font-weight:900; letter-spacing:.12em; text-transform:uppercase;
        }
        .eyebrow::before { content:""; width:8px; height:8px; border-radius:50%; background:var(--purple-2); box-shadow:0 0 18px rgba(176,96,255,.85); }
        h1 { margin:0; font-size:clamp(42px,5vw,68px); line-height:1.05; letter-spacing:-.04em; }
        h1 span { color:var(--purple-2); }
        .lead { max-width:680px; margin:24px 0 0; color:#b7bfd4; font-size:15px; line-height:1.8; }
        .hero-card {
            padding:24px;
            border:1px solid rgba(123,132,163,.28);
            border-radius:14px;
            background:
                radial-gradient(circle at 80% 20%, rgba(142,77,255,.22), transparent 32%),
                linear-gradient(180deg, rgba(22,27,43,.95), rgba(12,17,30,.95));
            box-shadow:0 22px 70px rgba(45,23,112,.24);
        }
        .stats { display:grid; grid-template-columns:repeat(2,1fr); gap:12px; }
        .stat {
            padding:16px; border-radius:12px; border:1px solid rgba(255,255,255,.09); background:rgba(255,255,255,.035);
        }
        .stat strong { display:block; font-size:28px; line-height:1; }
        .stat span { display:block; margin-top:8px; color:var(--muted); font-size:12px; }
        .section { padding:28px 0; border-top:1px solid rgba(255,255,255,.08); }
        .section-title { max-width:720px; margin-bottom:22px; }
        .section-title h2 { margin:0; font-size:30px; letter-spacing:-.03em; }
        .section-title p { margin:8px 0 0; color:var(--muted); font-size:13px; line-height:1.7; }
        .grid-3 { display:grid; grid-template-columns:repeat(3,1fr); gap:18px; }
        .info-card {
            min-height:184px;
            padding:20px;
            border:1px solid rgba(123,132,163,.28);
            border-radius:12px;
            background:linear-gradient(180deg, rgba(22,27,43,.92), rgba(14,19,32,.92));
            transition:transform .3s ease, border-color .3s ease, box-shadow .3s ease;
        }
        .info-card:hover { transform:translateY(-4px); border-color:rgba(162,75,255,.8); box-shadow:0 18px 60px rgba(103,39,255,.22); }
        .info-icon {
            width:42px; height:42px; display:grid; place-items:center; margin-bottom:16px;
            border-radius:12px; border:2px solid rgba(147,62,255,.78); color:#ba7cff; background:rgba(105,45,194,.13);
            font-weight:900;
        }
        .info-card h3 { margin:0 0 10px; font-size:17px; }
        .info-card p { margin:0; color:#b0b8cd; font-size:13px; line-height:1.65; }
        .story-panel {
            display:grid; grid-template-columns:minmax(0,1fr) 320px; gap:20px;
            padding:24px; border-radius:14px; border:1px solid rgba(172,69,255,.55);
            background:linear-gradient(90deg, rgba(18,22,36,.98), rgba(13,16,28,.98));
        }
        .story-panel p { margin:0 0 14px; color:#c2c8d9; line-height:1.8; font-size:14px; }
        .mini-list { display:grid; gap:10px; }
        .mini-list div { padding:12px; border-radius:10px; border:1px solid rgba(255,255,255,.08); background:rgba(255,255,255,.035); color:#d9def0; font-size:13px; }
        .cta {
            margin:28px 0 34px; padding:26px; border-radius:14px; border:2px solid rgba(172,69,255,.78);
            background:radial-gradient(circle at 12% 50%, rgba(70,53,255,.44), transparent 18%), linear-gradient(90deg, rgba(18,22,36,.98), rgba(13,16,28,.98));
            display:flex; align-items:center; justify-content:space-between; gap:18px; flex-wrap:wrap;
        }
        .cta h2 { margin:0; font-size:28px; letter-spacing:-.03em; }
        .cta p { margin:6px 0 0; color:#c2c8d9; font-size:13px; }
        .footer { border-top:1px solid rgba(255,255,255,.08); color:var(--muted); text-align:center; font-size:12px; padding:18px 0 22px; }
        @media(max-width:980px) {
            .nav { grid-template-columns:1fr auto; }
            .nav-links { display:none; }
            .hero, .story-panel { grid-template-columns:1fr; }
            .grid-3 { grid-template-columns:1fr; }
        }
        @media(max-width:620px) {
            body { border-width:2px; }
            .topbar { height:auto; padding:14px 0; }
            .nav { grid-template-columns:1fr; justify-items:start; }
            .nav-actions { justify-self:start; flex-wrap:wrap; }
            .stats { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <nav class="nav">
            <a class="brand" href="/code-arena/index.php"><span class="brand-mark"></span><span>Code<span>Arena</span></span></a>
            <div class="nav-links">
                <a href="/code-arena/index.php">Home</a>
                <a class="active" href="/code-arena/about.php">About</a>
                <a href="/code-arena/contests.php">Contests</a>
                <a href="/code-arena/problems.php">Practice</a>
                <a href="/code-arena/leaderboard.php">Leaderboard</a>
                <a href="/code-arena/discuss.php">Community</a>
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
            <div>
                <div class="eyebrow">About CodeArena</div>
                <h1>Built for coders who want to <span>practice, compete, and grow.</span></h1>
                <p class="lead">CodeArena is a competitive programming SaaS platform for students, self-learners, communities, and organizations. It brings together structured practice, rated contests, organization hosting, analytics, and discussion so problem solving feels like a real growth journey.</p>
            </div>
            <aside class="hero-card">
                <div class="stats">
                    <div class="stat"><strong><?= (int)$memberCount ?></strong><span>Members learning and competing</span></div>
                    <div class="stat"><strong><?= (int)$contestCount ?></strong><span>Published contests</span></div>
                    <div class="stat"><strong><?= (int)$problemCount ?></strong><span>Practice problems</span></div>
                    <div class="stat"><strong><?= (int)$orgCount ?></strong><span>Organization workspaces</span></div>
                </div>
            </aside>
        </section>

        <section class="container section">
            <div class="section-title">
                <h2>Why CodeArena exists</h2>
                <p>Many learners solve problems, but struggle to see where they are improving, how contests affect their skill, or how teams can run fair events. CodeArena connects those missing pieces.</p>
            </div>
            <div class="grid-3">
                <article class="info-card"><div class="info-icon">&lt;/&gt;</div><h3>Structured practice</h3><p>Roadmaps, hints, editorials, bookmarks, submissions, and weak-area analytics help learners move with direction instead of guessing what to solve next.</p></article>
                <article class="info-card"><div class="info-icon">C</div><h3>Contest culture</h3><p>Rated contests, leaderboards, participant tracking, and verdict analytics create a realistic competitive environment for growth.</p></article>
                <article class="info-card"><div class="info-icon">O</div><h3>Organization hosting</h3><p>Universities, companies, and communities can create problem banks, publish contests, manage members, and inspect performance safely.</p></article>
            </div>
        </section>

        <section class="container section">
            <div class="story-panel">
                <div>
                    <div class="section-title" style="margin-bottom:12px">
                        <h2>A platform with a practical mission</h2>
                    </div>
                    <p>CodeArena is designed around the same real-world need that many education and career platforms care about: helping people build measurable skill. Here, that skill is algorithmic thinking, coding discipline, contest confidence, and teamwork.</p>
                    <p>For a beginner, CodeArena should feel welcoming. For a serious competitor, it should feel challenging. For an organization, it should feel controlled, secure, and useful enough to run real events.</p>
                </div>
                <div class="mini-list">
                    <div>Skill rating for adaptive practice progress</div>
                    <div>Contest rating for competitive performance</div>
                    <div>Organization dashboards for contest owners</div>
                    <div>Announcements and analytics for real workflows</div>
                </div>
            </div>
        </section>

        <section class="container cta">
            <div>
                <h2>Ready to enter the arena?</h2>
                <p>Practice problems, join contests, or create an organization workspace for your community.</p>
            </div>
            <a class="btn btn-purple" href="/code-arena/register.php">Get Started</a>
        </section>
    </main>

    <footer class="footer">
        <div class="container">© 2025 CodeArena. All rights reserved.</div>
    </footer>
</body>
</html>
