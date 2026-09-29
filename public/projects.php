<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
$user = require_auth();
$canManageProjects = in_array($user['role'], PROJECT_MANAGER_ROLES, true);
$pageTitle = 'Projects';
require dirname(__DIR__) . '/views/layout/header.php';
require dirname(__DIR__) . '/views/projects/index.php';
require dirname(__DIR__) . '/views/layout/footer.php';
