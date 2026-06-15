<?php
require_once '../includes/session.php';
require_once '../config/db.php';
require_once '../includes/organization.php';
$organization = requireOrganizationPage($pdo);
$pageTitle = 'Edit Problem';
$activeOrgPage = 'problems';
$problemId = (int)($_GET['id'] ?? 0);
if (!$problemId) safeRedirect('/code-arena/organization/problems.php');
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Edit Problem - Code Arena</title><link rel="stylesheet" href="/code-arena/assets/css/style.css?v=20260615-ui2"></head><body>
<?php require_once '../includes/organization_shell.php'; ?>
<div class="org-content"><?php $problemFormMode = 'edit'; require __DIR__ . '/problem_form_partial.php'; ?></div>
<?php require_once '../includes/organization_shell_end.php'; ?>
</body></html>
