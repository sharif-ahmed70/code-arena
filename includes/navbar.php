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
?>

<nav class="navbar">
    <a href="/code-arena/index.php" class="nav-logo">
        Code<span>Arena</span>
    </a>

    <div class="nav-links">
        <?php if (isLoggedIn() && !isAdmin()): ?>
        <a href="/code-arena/dashboard.php"
           class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
            Dashboard
        </a>
        <a href="/code-arena/review.php"
           class="nav-link <?= $currentPage === 'review.php' ? 'active' : '' ?>">
            Review
        </a>
        <?php endif; ?>
        <a href="/code-arena/problems.php"
           class="nav-link <?= $currentPage === 'problems.php' ? 'active' : '' ?>">
            Problems
        </a>
        <?php if (!isAdmin()): ?>
        <a href="/code-arena/roadmap.php"
           class="nav-link <?= $currentPage === 'roadmap.php' ? 'active' : '' ?>">
            Roadmap
        </a>
        <?php endif; ?>
        <a href="/code-arena/contests.php"
           class="nav-link <?= $currentPage === 'contests.php' ? 'active' : '' ?>">
            Contests
        </a>
        <a href="/code-arena/leaderboard.php"
           class="nav-link <?= $currentPage === 'leaderboard.php' ? 'active' : '' ?>">
            Leaderboard
        </a>
        <a href="/code-arena/discuss.php"
           class="nav-link <?= in_array($currentPage, ['discuss.php','discuss_post.php','discuss_create.php']) ? 'active' : '' ?>">
            Discuss
        </a>
        <a href="/code-arena/submissions.php"
           class="nav-link <?= $currentPage === 'submissions.php' ? 'active' : '' ?>">
            Submissions
        </a>
        <?php if (isInstructor()): ?>
        <a href="/code-arena/instructor.php"
           class="nav-link <?= $currentPage === 'instructor.php' ? 'active' : '' ?>">
            Instructor
        </a>
        <?php endif; ?>
        <?php if (isAdmin()): ?>
        <a href="/code-arena/admin.php"
           class="nav-link <?= $currentPage === 'admin.php' ? 'active' : '' ?>">
            Admin
        </a>
        <?php endif; ?>
    </div>

    <div class="nav-right">
        <?php if (isLoggedIn()): ?>
            <a href="/code-arena/profile.php" class="nav-avatar" title="<?= htmlspecialchars($currentUser) ?>">
                <?= $avatarLetter ?>
            </a>
            <a href="/code-arena/api/auth/logout.php" class="nav-logout">Logout</a>
        <?php else: ?>
            <a href="/code-arena/login.php" class="btn-outline">Login</a>
            <a href="/code-arena/register.php" class="btn-primary">Register</a>
        <?php endif; ?>
    </div>
</nav>
