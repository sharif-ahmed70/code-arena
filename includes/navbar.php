<?php
// ============================================================
//  CODE ARENA — Shared Navbar
//  File: includes/navbar.php
//  Requires: session.php included first
// ============================================================

$currentUser     = currentUsername();
$currentUserRole = currentRole();
$currentUserId   = currentUserId();
$avatarLetter    = $currentUser ? strtoupper($currentUser[0]) : '?';
$currentPage     = basename($_SERVER['PHP_SELF']);

function navActive(array $pages): string {
    global $currentPage;
    return in_array($currentPage, $pages, true) ? 'active' : '';
}
?>

<nav class="navbar ca-navbar">
    <a href="/code-arena/index.php" class="nav-logo">
        Code<span>Arena</span>
    </a>

    <div class="nav-links">
        <?php if (isLoggedIn() && !isAdmin()): ?>
        <a href="<?= htmlspecialchars(authDashboardPath($currentUserRole)) ?>"
           class="nav-link <?= navActive(['dashboard.php']) ?>">
            Dashboard
        </a>
        <a href="/code-arena/review.php"
           class="nav-link <?= navActive(['review.php']) ?>">
            Review
        </a>
        <?php endif; ?>
        <a href="/code-arena/problems.php"
           class="nav-link <?= navActive(['problems.php', 'problem.php', 'editorial_manage.php']) ?>">
            Problems
        </a>
        <?php if (!isAdmin()): ?>
        <a href="/code-arena/roadmap.php"
           class="nav-link <?= navActive(['roadmap.php']) ?>">
            Roadmap
        </a>
        <?php endif; ?>
        <a href="/code-arena/contests.php"
           class="nav-link <?= navActive(['contests.php', 'contest.php', 'contest_manage.php']) ?>">
            Contests
        </a>
        <a href="/code-arena/leaderboard.php"
           class="nav-link <?= navActive(['leaderboard.php']) ?>">
            Leaderboard
        </a>
        <a href="/code-arena/discuss.php"
           class="nav-link <?= navActive(['discuss.php', 'discuss_post.php', 'discuss_create.php']) ?>">
            Discuss
        </a>
        <a href="/code-arena/submissions.php"
           class="nav-link <?= navActive(['submissions.php']) ?>">
            Submissions
        </a>
        <?php if (isLoggedIn() && !isAdmin()): ?>
        <a href="/code-arena/announcements.php"
           class="nav-link <?= navActive(['announcements.php']) ?>">
            Announcements
            <span id="announcement-count-badge" style="display:none;margin-left:6px;padding:1px 7px;border-radius:999px;background:var(--red);color:#fff;font-size:.72rem;font-weight:900;line-height:1.5"></span>
        </a>
        <?php endif; ?>
        <?php if (isInstructor() && !isOrgAdmin()): ?>
        <a href="/code-arena/instructor.php"
           class="nav-link <?= navActive(['instructor.php']) ?>">
            Instructor
        </a>
        <?php endif; ?>
        <?php if (isOrgAdmin()): ?>
        <a href="/code-arena/organization/dashboard.php"
           class="nav-link <?= navActive(['dashboard.php']) ?>">
            Organization
        </a>
        <?php endif; ?>
        <?php if (isAdmin()): ?>
        <a href="/code-arena/admin.php"
           class="nav-link <?= navActive(['admin.php']) ?>">
            Admin
        </a>
        <?php endif; ?>
    </div>

    <div class="nav-right">
        <?php if (isLoggedIn()): ?>
            <?php require __DIR__ . '/admin_control_dropdown.php'; ?>
            <a href="/code-arena/profile.php" class="nav-avatar" title="<?= htmlspecialchars($currentUser) ?>">
                <?= $avatarLetter ?>
            </a>
            <a href="/code-arena/api/auth/logout.php" class="nav-logout">Logout</a>
        <?php else: ?>
            <a href="/code-arena/login.php" class="btn-outline ca-btn ca-btn-outline">Login</a>
            <a href="/code-arena/register.php" class="btn-primary ca-btn ca-btn-primary">Register</a>
        <?php endif; ?>
    </div>
</nav>

<?php if (isLoggedIn() && !isAdmin()): ?>
<script>
(function announcementBadge() {
    const badge = document.getElementById('announcement-count-badge');
    if (!badge) return;

    async function refreshAnnouncementCount() {
        try {
            const res = await fetch('/code-arena/api/announcements/count.php', { credentials: 'include' });
            const data = await res.json();
            const count = Number(data?.data?.count || 0);
            if (count > 0) {
                badge.textContent = '+' + (count > 99 ? '99' : count);
                badge.style.display = 'inline-flex';
            } else {
                badge.style.display = 'none';
                badge.textContent = '';
            }
        } catch (error) {
            badge.style.display = 'none';
        }
    }

    refreshAnnouncementCount();
    setInterval(refreshAnnouncementCount, 30000);
})();
</script>
<?php endif; ?>
