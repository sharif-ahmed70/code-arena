<?php
// ============================================================
//  CODE ARENA — Logout
//  File: api/auth/logout.php
// ============================================================

require_once '../../includes/session.php';

logoutUser();

// Redirect to landing page
header('Location: /code-arena/index.php');
exit;
