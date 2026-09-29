<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
$user = require_auth();
$pageTitle = 'Tasks';
require dirname(__DIR__) . '/views/layout/header.php';
require dirname(__DIR__) . '/views/tasks/index.php';
require dirname(__DIR__) . '/views/tasks/modals.php';
require dirname(__DIR__) . '/views/layout/footer.php';
