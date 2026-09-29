<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
if (current_user() === null) {
    header('Location: login.php');
} else {
    header('Location: dashboard.php');
}
exit;
