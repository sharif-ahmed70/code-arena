<?php
// ============================================================
//  CODE ARENA - Organizations Directory
//  GET /api/organizations/index.php
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/response.php';

methodCheck('GET');

$stmt = $pdo->query(
    'SELECT id, name, type
     FROM organizations
     ORDER BY name ASC
     LIMIT 200'
);

ok(['organizations' => $stmt->fetchAll()]);
