<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        exit('Session expired. Please sign in again.');
    }
}
logout_user();
header('Location: login.php');
exit;
