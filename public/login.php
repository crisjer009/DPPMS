<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
if (current_user() !== null) {
    header('Location: dashboard.php');
    exit;
}
$pageTitle = 'Sign in';
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= e($pageTitle) ?> · <?= e(env_value('APP_NAME', 'DPPMS')) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="assets/css/app.css" rel="stylesheet"></head>
<body class="login-page"><main class="container"><div class="row justify-content-center align-items-center min-vh-100"><div class="col-12 col-sm-9 col-md-6 col-lg-4"><section class="card shadow-sm border-0"><div class="card-body p-4 p-lg-5"><div class="brand-mark mb-4">D</div><h1 class="h3 mb-1">DPPMS</h1><p class="text-secondary mb-4">Development Project &amp; Performance Monitoring System</p><div id="login-alert" class="alert d-none" role="alert"></div>
<form id="login-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="mb-3"><label class="form-label" for="username">Username</label><input class="form-control" id="username" name="username" autocomplete="username" required maxlength="80"></div><div class="mb-4"><label class="form-label" for="password">Password</label><input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required></div><button class="btn btn-primary w-100" type="submit">Sign in</button></form></div></section><p class="small text-secondary text-center mt-3">Internal IT Development Team</p></div></div></main>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script><script src="assets/js/login.js"></script></body></html>
