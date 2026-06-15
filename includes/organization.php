<?php
// ============================================================
//  Code Arena - Organization access helpers
// ============================================================

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/contest.php';

function currentOrganization(PDO $pdo): ?array {
    static $cached = null;
    if ($cached !== null) return $cached;
    if (!canAccessOrganizationDashboard()) return null;

    $userId = currentUserId();
    if (!$userId) return null;

    if (isAdminOrgView()) {
        $viewOrgId = adminViewOrgId();
        if (!$viewOrgId) return null;
        $stmt = $pdo->prepare('SELECT * FROM organizations WHERE id = ? LIMIT 1');
        $stmt->execute([$viewOrgId]);
        $org = $stmt->fetch();
        $cached = $org ?: null;
        return $cached;
    }

    $stmt = $pdo->prepare(
        'SELECT o.*
         FROM users u
         JOIN organizations o ON o.id = u.org_id
         WHERE u.id = ? AND COALESCE(u.is_deleted, 0) = 0
         LIMIT 1'
    );
    $stmt->execute([$userId]);
    $org = $stmt->fetch();

    if (!$org) {
        $stmt = $pdo->prepare(
            'SELECT o.*
             FROM organizations o
             LEFT JOIN organization_members om ON om.org_id = o.id
             WHERE o.owner_id = ? OR om.user_id = ?
             ORDER BY o.owner_id = ? DESC, o.id ASC
             LIMIT 1'
        );
        $stmt->execute([$userId, $userId, $userId]);
        $org = $stmt->fetch();
    }

    $cached = $org ?: null;
    return $cached;
}

function currentOrganizationId(PDO $pdo): ?int {
    $org = currentOrganization($pdo);
    return $org ? (int)$org['id'] : null;
}

function requireOrganizationPage(PDO $pdo): array {
    requireLogin();
    if (!canAccessOrganizationDashboard()) {
        safeRedirect(authDashboardPath(currentRole()));
    }
    $org = currentOrganization($pdo);
    if (!$org) {
        safeRedirect('/code-arena/profile_complete.php');
    }
    return $org;
}

function requireOrganizationApi(PDO $pdo): array {
    require_once __DIR__ . '/response.php';
    requireLogin();
    if (!canAccessOrganizationDashboard()) err('Organization admin access required', 403);
    $org = currentOrganization($pdo);
    if (!$org) err('No organization is assigned to this account', 403);
    return $org;
}

function requireOwnedContest(PDO $pdo, int $orgId, int $contestId): array {
    $stmt = $pdo->prepare('SELECT * FROM contests WHERE id = ? AND org_id = ?');
    $stmt->execute([$contestId, $orgId]);
    $contest = $stmt->fetch();
    if (!$contest) {
        if (function_exists('err')) err('Contest not found for this organization', 404);
        throw new RuntimeException('Contest not found for this organization');
    }
    return $contest;
}

function orgPublicStatusFromLifecycle(string $orgStatus, string $startTime, string $endTime): string {
    if ($orgStatus === 'draft' || $orgStatus === 'archived') return 'upcoming';
    if ($orgStatus === 'scheduled') return 'upcoming';
    if ($orgStatus === 'live') return 'active';
    if ($orgStatus === 'ended') return 'ended';
    return computedContestStatus($startTime, $endTime);
}
