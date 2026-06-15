<?php
// ============================================================
//  Organization Settings API
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/organization.php';
require_once '../../includes/response.php';

$org = requireOrganizationApi($pdo);
$orgId = (int)$org['id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    ok(['organization' => $org]);
}

if ($method === 'PUT') {
    $body = jsonBody();
    $name = cleanString($body['name'] ?? '', 160);
    $description = cleanString($body['description'] ?? '', 2000);
    $logo = cleanString($body['logo'] ?? '', 255);
    if (!$name) err('Organization name is required');
    $stmt = $pdo->prepare('UPDATE organizations SET name = ?, description = ?, logo = ? WHERE id = ?');
    $stmt->execute([$name, $description ?: null, $logo ?: null, $orgId]);
    ok(null, 'Organization settings updated');
}

err('Method not allowed', 405);
