<?php
// Shared organization navigation. Expects $organization and $activeOrgPage.
$activeOrgPage = $activeOrgPage ?? 'dashboard';
$orgShellName = $organization['name'] ?? 'Organization';
if (!function_exists('orgNavActive')) {
    function orgNavActive(string $key, string $activeOrgPage): string {
        return $key === $activeOrgPage ? 'active' : '';
    }
}
?>
<style>
    .org-shell { min-height:100vh; display:grid; grid-template-columns:260px minmax(0,1fr); background:var(--bg); }
    .org-sidebar { position:sticky; top:0; height:100vh; padding:22px 16px; border-right:1px solid var(--border); background:linear-gradient(180deg,var(--bg-card),rgba(255,255,255,.018)); }
    .org-brand { padding:0 8px 16px; border-bottom:1px solid var(--border); margin-bottom:18px; }
    .org-brand strong { display:block; color:var(--text); }
    .org-brand span { color:var(--text-muted); font-size:.78rem; }
    .org-menu { display:grid; gap:6px; }
    .org-menu a { padding:10px 12px; border-radius:var(--radius-sm); color:var(--text-muted); transition:background .18s,color .18s; }
    .org-menu a:hover, .org-menu a.active { background:rgba(0,232,122,.08); color:var(--accent); }
    .org-main { min-width:0; }
    .org-topbar { position:sticky; top:0; z-index:10; min-height:66px; padding:14px 24px; border-bottom:1px solid var(--border); background:rgba(10,10,15,.94); display:flex; align-items:center; gap:14px; }
    .org-topbar h1 { font-size:1.05rem; margin:0; }
    .org-search { flex:1; max-width:520px; padding:10px 12px; border:1px solid var(--border); border-radius:var(--radius-sm); background:var(--bg-card2); color:var(--text); }
    .org-content { padding:24px; }
    .org-card { border:1px solid var(--border); border-radius:var(--radius); background:var(--bg-card); padding:18px; }
    .org-grid { display:grid; gap:18px; }
    .org-toolbar { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:16px; }
    .org-toolbar input, .org-toolbar select { min-width:180px; }
    @media(max-width:760px){ .org-shell{grid-template-columns:1fr}.org-sidebar{position:static;height:auto}.org-topbar{flex-wrap:wrap}.org-search{order:3;max-width:none;flex-basis:100%} }
</style>
<div class="org-shell">
    <aside class="org-sidebar">
        <div class="org-brand">
            <strong><?= htmlspecialchars($orgShellName) ?></strong>
            <span>Organization control panel</span>
        </div>
        <nav class="org-menu">
            <a class="<?= orgNavActive('dashboard', $activeOrgPage) ?>" href="/code-arena/organization/dashboard.php">Dashboard</a>
            <a class="<?= orgNavActive('problems', $activeOrgPage) ?>" href="/code-arena/organization/problems.php">Problem Bank</a>
            <a class="<?= orgNavActive('contests', $activeOrgPage) ?>" href="/code-arena/organization/contests.php">Contests</a>
            <a class="<?= orgNavActive('participants', $activeOrgPage) ?>" href="/code-arena/organization/participants.php">Participants</a>
            <a class="<?= orgNavActive('submissions', $activeOrgPage) ?>" href="/code-arena/organization/submissions.php">Submissions</a>
            <a class="<?= orgNavActive('announcements', $activeOrgPage) ?>" href="/code-arena/organization/announcements.php">Announcements</a>
            <a class="<?= orgNavActive('analytics', $activeOrgPage) ?>" href="/code-arena/organization/analytics.php">Analytics</a>
            <a class="<?= orgNavActive('settings', $activeOrgPage) ?>" href="/code-arena/organization/settings.php">Settings</a>
        </nav>
    </aside>
    <main class="org-main">
        <header class="org-topbar">
            <h1><?= htmlspecialchars($pageTitle ?? 'Organization') ?></h1>
            <input class="org-search" placeholder="Search organization workspace">
            <a class="btn-outline" href="/code-arena/api/auth/logout.php">Logout</a>
        </header>
