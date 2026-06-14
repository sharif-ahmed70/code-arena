<?php
// ============================================================
//  CODE ARENA — Landing Page
// ============================================================
require_once 'includes/session.php';
require_once 'config/db.php';

$loggedIn = isLoggedIn();

$totalProblems = (int) $pdo->query('SELECT COUNT(*) FROM problems WHERE is_public=1')->fetchColumn();
$totalUsers    = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalSubs     = (int) $pdo->query('SELECT COUNT(*) FROM submissions')->fetchColumn();
$totalAccepted = (int) $pdo->query('SELECT COUNT(*) FROM submissions WHERE status="Accepted"')->fetchColumn();
$acceptRate    = $totalSubs > 0 ? round($totalAccepted / $totalSubs * 100) : 0;

$previewStmt = $pdo->query(
    'SELECT title, slug, difficulty, tags, total_submissions, total_accepted
     FROM problems WHERE is_public=1
     ORDER BY difficulty ASC, total_submissions DESC
     LIMIT 6'
);
$previewProblems = $previewStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code Arena — Compete. Learn. Improve.</title>
    <link rel="stylesheet" href="/code-arena/assets/css/style.css">
    <style>
    /* ── Fix: descender clipping on all headings ──────────── */
    h1, h2, h3, h4 {
        line-height: 1.25;
        overflow: visible;
        padding-bottom: 0.05em; /* ensures g/y/p aren't clipped */
    }

    /* ── Gradient text helper — needs padding-bottom for descenders */
    .grad-text {
        background: linear-gradient(90deg, var(--accent) 0%, var(--blue) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        display: inline-block; /* required: inline-block clips to content box */
        padding-bottom: 0.1em;
        line-height: 1.25;
    }

    /* ── Navbar ───────────────────────────────────────────── */
    .lp-nav {
        position: fixed; top: 0; left: 0; right: 0; z-index: 100;
        display: flex; align-items: center; justify-content: space-between;
        padding: 0 40px; height: 64px;
        background: rgba(10,10,15,.9);
        backdrop-filter: blur(12px);
        border-bottom: 1px solid var(--border);
    }
    .lp-nav-logo {
        font-family: 'DM Sans', sans-serif; font-weight: 600; font-size: 1.25rem;
        color: var(--text); letter-spacing: -.01em;
    }
    .lp-nav-logo span { color: var(--accent); }
    .lp-nav-links { display: flex; gap: 4px; }
    .lp-nav-links a {
        padding: 6px 14px; border-radius: var(--radius-sm);
        font-size: .88rem; color: var(--text-muted);
        transition: color .15s, background .15s;
    }
    .lp-nav-links a:hover { color: var(--text); background: var(--bg-card); }
    .lp-nav-actions { display: flex; gap: 10px; }

    /* ── Page wrapper ─────────────────────────────────────── */
    .lp-page { padding-top: 64px; }

    /* ── Hero ─────────────────────────────────────────────── */
    .hero {
        text-align: center;
        padding: 96px 24px 80px;
        max-width: 760px;
        margin: 0 auto;
    }
    .hero h1 {
        font-family: 'DM Sans', sans-serif;
        font-weight: 600;
        font-size: clamp(2.4rem, 5vw, 3.6rem);
        letter-spacing: -.01em;
        line-height: 1.25;
        overflow: visible;
        margin-bottom: 20px;
    }
    .hero p {
        font-size: 1.05rem;
        color: var(--text-muted);
        line-height: 1.75;
        max-width: 520px;
        margin: 0 auto 36px;
    }
    .hero-cta {
        display: flex; gap: 12px;
        justify-content: center; flex-wrap: wrap;
    }
    .hero-cta .btn-primary { padding: 12px 28px; font-size: .95rem; }
    .hero-cta .btn-outline  { padding: 12px 28px; font-size: .95rem; }

    /* ── Stats ────────────────────────────────────────────── */
    .stats-bar {
        border-top: 1px solid var(--border);
        border-bottom: 1px solid var(--border);
        background: var(--bg-card);
        padding: 40px 24px;
    }
    .stats-bar-inner {
        max-width: 800px; margin: 0 auto;
        display: grid; grid-template-columns: repeat(4, 1fr);
        gap: 24px; text-align: center;
    }
    @media (max-width: 600px) {
        .stats-bar-inner { grid-template-columns: repeat(2, 1fr); }
    }
    .stat-item-num {
        font-family: 'DM Sans', sans-serif;
        font-size: 2rem; font-weight: 600;
        color: var(--accent); line-height: 1.25;
        overflow: visible;
    }
    .stat-item-lbl {
        font-size: .78rem; font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase; letter-spacing: .07em;
        margin-top: 4px;
    }

    /* ── Section wrapper ──────────────────────────────────── */
    .lp-section {
        max-width: 1080px; margin: 0 auto;
        padding: 80px 24px;
    }
    .lp-section-header {
        margin-bottom: 48px;
    }
    .lp-section-header .eyebrow {
        font-size: .72rem; font-weight: 700; letter-spacing: .12em;
        text-transform: uppercase; color: var(--accent);
        margin-bottom: 10px;
    }
    .lp-section-header h2 {
        font-family: 'DM Sans', sans-serif; font-weight: 600;
        font-size: clamp(1.6rem, 3vw, 2.2rem);
        line-height: 1.25; overflow: visible;
        letter-spacing: -.01em; margin-bottom: 10px;
    }
    .lp-section-header p {
        color: var(--text-muted); font-size: .95rem;
        line-height: 1.7; max-width: 500px;
    }

    /* ── Feature grid ─────────────────────────────────────── */
    .feat-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }
    @media (max-width: 860px) { .feat-grid { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 540px) { .feat-grid { grid-template-columns: 1fr; } }

    .feat-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 28px;
        transition: border-color .2s;
    }
    .feat-card:hover { border-color: rgba(0,255,136,.2); }
    .feat-icon {
        font-size: 1.5rem; margin-bottom: 14px;
        width: 44px; height: 44px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        background: var(--bg-card2); border: 1px solid var(--border);
    }
    .feat-card h3 {
        font-size: .95rem; font-weight: 700;
        line-height: 1.3; overflow: visible;
        margin-bottom: 8px;
    }
    .feat-card p { font-size: .87rem; color: var(--text-muted); line-height: 1.7; }

    /* Roadmap day grid */
    .day-grid { display: flex; gap: 5px; flex-wrap: wrap; margin-top: 14px; }
    .day-pip {
        width: 26px; height: 26px; border-radius: 6px;
        display: flex; align-items: center; justify-content: center;
        font-family: 'JetBrains Mono', monospace;
        font-size: .65rem; font-weight: 700;
    }
    .dp-done   { background: rgba(0,255,136,.12); color: var(--accent); border: 1px solid rgba(0,255,136,.2); }
    .dp-active { background: rgba(0,255,136,.2);  color: var(--accent); border: 1px solid var(--accent); }
    .dp-locked { background: var(--bg-card2); color: var(--text-muted); border: 1px solid var(--border); }

    /* Hint tiers */
    .hint-list { display: flex; flex-direction: column; gap: 7px; margin-top: 14px; }
    .hint-row {
        display: flex; align-items: center; gap: 8px;
        font-size: .82rem; padding: 8px 12px;
        border-radius: var(--radius-sm);
    }
    .hr-1 { background: rgba(255,209,102,.06); border: 1px solid rgba(255,209,102,.15); color: var(--yellow); }
    .hr-2 { background: rgba(255,163,77,.05);  border: 1px solid rgba(255,163,77,.12);  color: #c97c27; }
    .hr-3 { background: rgba(255,79,79,.05);   border: 1px solid rgba(255,79,79,.12);   color: var(--red); }
    .hint-row span:last-child { margin-left: auto; font-size: .75rem; opacity: .7; }

    /* Rating bars */
    .rating-row { display: flex; flex-direction: column; gap: 10px; margin-top: 14px; }
    .rr-item { display: flex; flex-direction: column; gap: 4px; }
    .rr-label { display: flex; justify-content: space-between; font-size: .8rem; }
    .rr-bar   { height: 5px; background: var(--border); border-radius: 3px; overflow: hidden; }
    .rr-fill  { height: 100%; border-radius: 3px; }
    .rr-hc    { width: 62%; background: var(--red); }
    .rr-lr    { width: 74%; background: var(--blue); }

    /* ── Problem preview ──────────────────────────────────── */
    .prob-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 14px;
    }
    @media (max-width: 860px) { .prob-grid { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 540px) { .prob-grid { grid-template-columns: 1fr; } }

    .prob-card {
        background: var(--bg-card); border: 1px solid var(--border);
        border-radius: var(--radius); padding: 20px;
        display: flex; flex-direction: column; gap: 10px;
        transition: border-color .2s;
    }
    .prob-card:hover { border-color: rgba(255,255,255,.15); }
    .prob-card-title { font-weight: 600; font-size: .9rem; line-height: 1.35; overflow: visible; }
    .prob-card-title a { color: var(--text); }
    .prob-card-title a:hover { color: var(--accent); }
    .prob-card-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; }
    .prob-card-tags { display: flex; gap: 5px; flex-wrap: wrap; }
    .prob-card-tag {
        font-size: .7rem; padding: 2px 7px;
        border-radius: 4px; background: var(--bg-card2);
        color: var(--text-muted); border: 1px solid var(--border);
    }
    .prob-card-meta { font-size: .78rem; color: var(--text-muted); }

    /* ── CTA ──────────────────────────────────────────────── */
    .cta-section {
        background: var(--bg-card);
        border-top: 1px solid var(--border);
        text-align: center;
        padding: 80px 24px;
    }
    .cta-section h2 {
        font-family: 'DM Sans', sans-serif; font-weight: 600;
        font-size: clamp(1.8rem, 3.5vw, 2.6rem);
        line-height: 1.25; overflow: visible;
        letter-spacing: -.01em; margin-bottom: 14px;
    }
    .cta-section p { color: var(--text-muted); font-size: .95rem; line-height: 1.7; margin-bottom: 32px; }
    .cta-pills {
        display: flex; gap: 8px; justify-content: center;
        flex-wrap: wrap; margin-bottom: 32px;
    }
    .cta-pill {
        font-size: .78rem; padding: 5px 12px;
        border-radius: 100px;
        background: var(--bg-card2); color: var(--text-muted);
        border: 1px solid var(--border);
    }
    .cta-section .btn-primary { padding: 13px 32px; font-size: .95rem; }

    /* ── Footer ───────────────────────────────────────────── */
    .lp-footer {
        padding: 28px 40px;
        border-top: 1px solid var(--border);
        display: flex; align-items: center;
        justify-content: space-between; flex-wrap: wrap; gap: 12px;
        font-size: .82rem; color: var(--text-muted);
    }
    .lp-footer a { color: var(--text-muted); }
    .lp-footer a:hover { color: var(--text); }
    .lp-footer-links { display: flex; gap: 20px; }
    </style>
</head>
<body>

<!-- ── Navbar ─────────────────────────────────────────────── -->
<nav class="lp-nav">
    <a href="/code-arena/index.php" class="lp-nav-logo">Code<span>Arena</span></a>

    <div class="lp-nav-links">
        <a href="/code-arena/problems.php">Problems</a>
        <a href="/code-arena/roadmap.php">Roadmap</a>
        <a href="/code-arena/contests.php">Contests</a>
    </div>

    <div class="lp-nav-actions">
        <?php if ($loggedIn): ?>
            <a href="/code-arena/problems.php" class="btn-primary">Go to Problems</a>
        <?php else: ?>
            <a href="/code-arena/login.php"    class="btn-outline">Login</a>
            <a href="/code-arena/register.php" class="btn-primary">Register Free</a>
        <?php endif; ?>
    </div>
</nav>

<div class="lp-page">

    <!-- ── Hero ───────────────────────────────────────────── -->
    <section class="hero">
        <h1>
            Master Algorithms.<br>
            <span class="grad-text">Compete. Grow.</span>
        </h1>
        <p>
            Solve handcrafted problems, follow a 30-day roadmap, compete in rated
            contests, and track your progress with dual ratings — Hardcore and Learning.
        </p>
        <div class="hero-cta">
            <?php if ($loggedIn): ?>
                <a href="/code-arena/problems.php" class="btn-primary">Continue Solving →</a>
                <a href="/code-arena/roadmap.php"  class="btn-outline">View Roadmap</a>
            <?php else: ?>
                <a href="/code-arena/register.php" class="btn-primary">Start for Free →</a>
                <a href="/code-arena/problems.php" class="btn-outline">Browse Problems</a>
            <?php endif; ?>
        </div>
    </section>

    <!-- ── Stats ──────────────────────────────────────────── -->
    <div class="stats-bar">
        <div class="stats-bar-inner">
            <div>
                <div class="stat-item-num"><?= number_format($totalProblems) ?></div>
                <div class="stat-item-lbl">Problems</div>
            </div>
            <div>
                <div class="stat-item-num"><?= number_format($totalUsers) ?></div>
                <div class="stat-item-lbl">Developers</div>
            </div>
            <div>
                <div class="stat-item-num"><?= number_format($totalSubs) ?></div>
                <div class="stat-item-lbl">Submissions</div>
            </div>
            <div>
                <div class="stat-item-num"><?= $acceptRate ?>%</div>
                <div class="stat-item-lbl">Acceptance</div>
            </div>
        </div>
    </div>

    <!-- ── Features ───────────────────────────────────────── -->
    <div class="lp-section">
        <div class="lp-section-header">
            <p class="eyebrow">Why Code Arena</p>
            <h2>Everything you need to level up.</h2>
            <p>A complete competitive programming environment — structured learning, real judging, and meaningful ratings.</p>
        </div>

        <div class="feat-grid">

            <!-- 30-day roadmap -->
            <div class="feat-card">
                <div class="feat-icon">🗺️</div>
                <h3>30-Day Sequential Roadmap</h3>
                <p>Each day unlocks only after you solve the previous day's problems. Build real algorithmic intuition day by day.</p>
                <div class="day-grid">
                    <?php
                    $states = ['dp-done','dp-done','dp-done','dp-done','dp-active',
                               'dp-locked','dp-locked','dp-locked','dp-locked','dp-locked'];
                    for ($d = 1; $d <= 10; $d++):
                    ?>
                    <div class="day-pip <?= $states[$d-1] ?>"><?= $d ?></div>
                    <?php endfor; ?>
                    <div class="day-pip dp-locked" style="width:auto;padding:0 6px;font-size:.6rem">+20</div>
                </div>
            </div>

            <!-- 3-tier hints -->
            <div class="feat-card">
                <div class="feat-icon">💡</div>
                <h3>3-Tier Progressive Hints</h3>
                <p>Never fully stuck. Unlock hints one tier at a time — each tier costs a fraction of your Learning rating gain.</p>
                <div class="hint-list">
                    <div class="hint-row hr-1">💡 Tier 1 — Approach hint<span>−25% LR</span></div>
                    <div class="hint-row hr-2">🔒 Tier 2 — Algorithm hint<span>−50% LR</span></div>
                    <div class="hint-row hr-3">🔒 Tier 3 — Key insight<span>−75% LR</span></div>
                </div>
            </div>

            <!-- Dual rating -->
            <div class="feat-card">
                <div class="feat-icon">📊</div>
                <h3>Dual Rating System</h3>
                <p>Two independent ratings measure different skills. Hints block Hardcore gains — forcing you to grow both ways.</p>
                <div class="rating-row">
                    <div class="rr-item">
                        <div class="rr-label">
                            <span style="color:var(--red)">Hardcore</span>
                            <span style="color:var(--text-muted)">No hints only</span>
                        </div>
                        <div class="rr-bar"><div class="rr-fill rr-hc"></div></div>
                    </div>
                    <div class="rr-item">
                        <div class="rr-label">
                            <span style="color:var(--blue)">Learning</span>
                            <span style="color:var(--text-muted)">Hints allowed</span>
                        </div>
                        <div class="rr-bar"><div class="rr-fill rr-lr"></div></div>
                    </div>
                </div>
            </div>

            <!-- Instant judging -->
            <div class="feat-card">
                <div class="feat-icon">⚡</div>
                <h3>Instant Automated Judging</h3>
                <p>Code is run against hidden test cases in real-time. Get your verdict in seconds — Accepted, WA, TLE, or RE.</p>
            </div>

            <!-- Contests -->
            <div class="feat-card">
                <div class="feat-icon">🏆</div>
                <h3>Rated Contests</h3>
                <p>Compete in timed contests with live leaderboards. Earn contest rating and measure yourself against peers.</p>
            </div>

            <!-- Languages -->
            <div class="feat-card">
                <div class="feat-icon">🌐</div>
                <h3>12 Languages</h3>
                <p>Python, C++, Java, JavaScript, Go, Rust, Ruby, TypeScript, Kotlin, Swift, C, PHP.</p>
                <div style="display:flex;gap:5px;flex-wrap:wrap;margin-top:12px">
                    <?php foreach (['JS','PY','C++','Java','Go','Rust','Ruby','+5'] as $l): ?>
                    <span style="font-family:'JetBrains Mono',monospace;font-size:.68rem;
                                 padding:2px 8px;border-radius:4px;
                                 background:var(--bg-card2);color:var(--text-muted);
                                 border:1px solid var(--border)"><?= $l ?></span>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>

    <!-- ── Problem preview ────────────────────────────────── -->
    <?php if ($previewProblems): ?>
    <div style="border-top:1px solid var(--border)">
    <div class="lp-section">
        <div class="lp-section-header" style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:16px;margin-bottom:32px">
            <div>
                <p class="eyebrow">Problems</p>
                <h2 style="margin-bottom:0">Dive in right now</h2>
            </div>
            <a href="/code-arena/problems.php"
               style="font-size:.88rem;color:var(--accent);white-space:nowrap">
                View all <?= $totalProblems ?> →
            </a>
        </div>

        <div class="prob-grid">
            <?php foreach ($previewProblems as $p):
                $diffLower = strtolower($p['difficulty']);
                $diffCls   = ['easy'=>'badge-easy','medium'=>'badge-medium','hard'=>'badge-hard'][$diffLower] ?? '';
                $rate      = $p['total_submissions'] > 0
                             ? round($p['total_accepted'] / $p['total_submissions'] * 100).'%'
                             : '—';
                $tags = $p['tags'] ? array_slice(array_map('trim', explode(',', $p['tags'])), 0, 3) : [];
            ?>
            <div class="prob-card">
                <div class="prob-card-top">
                    <div class="prob-card-title">
                        <a href="/code-arena/problem.php?slug=<?= htmlspecialchars($p['slug']) ?>">
                            <?= htmlspecialchars($p['title']) ?>
                        </a>
                    </div>
                    <span class="badge <?= $diffCls ?>" style="flex-shrink:0"><?= $p['difficulty'] ?></span>
                </div>
                <?php if ($tags): ?>
                <div class="prob-card-tags">
                    <?php foreach ($tags as $tag): ?>
                    <span class="prob-card-tag"><?= htmlspecialchars($tag) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <div class="prob-card-meta">
                    <?= number_format($p['total_submissions']) ?> submissions &middot; <?= $rate ?> accepted
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    </div>
    <?php endif; ?>

    <!-- ── Final CTA ──────────────────────────────────────── -->
    <section class="cta-section">
        <h2>Ready to start competing?</h2>
        <p>
            Join <?= number_format($totalUsers) ?> developers already on Code Arena.<br>
            Free forever — no credit card required.
        </p>
        <div class="cta-pills">
            <span class="cta-pill">✓ Free forever</span>
            <span class="cta-pill">✓ 12 languages</span>
            <span class="cta-pill">✓ Instant judging</span>
            <span class="cta-pill">✓ 30-day roadmap</span>
            <span class="cta-pill">✓ Dual ratings</span>
        </div>
        <?php if ($loggedIn): ?>
            <a href="/code-arena/problems.php" class="btn-primary">Continue Solving →</a>
        <?php else: ?>
            <a href="/code-arena/register.php" class="btn-primary">Create Free Account →</a>
        <?php endif; ?>
    </section>

    <!-- ── Footer ─────────────────────────────────────────── -->
    <footer class="lp-footer">
        <span>Code Arena &copy; <?= date('Y') ?></span>
        <div class="lp-footer-links">
            <a href="/code-arena/problems.php">Problems</a>
            <a href="/code-arena/roadmap.php">Roadmap</a>
            <a href="/code-arena/contests.php">Contests</a>
        </div>
        <span>Built for coders.</span>
    </footer>

</div><!-- /.lp-page -->
</body>
</html>
