<?php
declare(strict_types=1);
$user = $user ?? require_auth();
$pageTitle = $pageTitle ?? 'DPPMS';
$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? 'dashboard.php');
$canManageProjects = in_array($user['role'], PROJECT_MANAGER_ROLES, true);
$needsDataTables = in_array($currentPage, ['users.php', 'projects.php'], true);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> · <?= e(env_value('APP_NAME', 'DPPMS')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <?php if ($needsDataTables): ?><link href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css" rel="stylesheet"><?php endif; ?>
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body class="app-shell">
<aside class="sidebar" id="sidebar">
    <a class="sidebar-brand" href="dashboard.php"><span class="brand-mark">D</span><span><strong>DPPMS</strong><small>Development &amp; Operations</small></span></a>
    <nav class="side-nav">
        <div class="nav-section-label">WORKSPACE</div>
        <a class="side-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a>
        <div class="nav-section-label">DEVELOPMENT</div>
        <a class="side-link <?= $currentPage === 'projects.php' ? 'active' : '' ?>" href="projects.php"><i class="fa-solid fa-diagram-project"></i><span>Projects</span></a>
        <a class="side-link disabled-link" href="#" aria-disabled="true"><i class="fa-solid fa-table-columns"></i><span>Development Board</span><span class="coming-soon">Soon</span></a>
        <a class="side-link disabled-link" href="#" aria-disabled="true"><i class="fa-solid fa-list-check"></i><span>Tasks</span><span class="coming-soon">Soon</span></a>
        <a class="side-link disabled-link" href="#" aria-disabled="true"><i class="fa-solid fa-chart-line"></i><span>Metrics</span><span class="coming-soon">Soon</span></a>
        <div class="nav-section-label">INFRASTRUCTURE</div>
        <a class="side-link disabled-link" href="#" aria-disabled="true"><i class="fa-solid fa-cubes"></i><span>Systems</span><span class="coming-soon">Soon</span></a>
        <a class="side-link disabled-link" href="#" aria-disabled="true"><i class="fa-solid fa-server"></i><span>Servers</span><span class="coming-soon">Soon</span></a>
        <a class="side-link disabled-link" href="#" aria-disabled="true"><i class="fa-solid fa-wave-square"></i><span>Monitoring</span><span class="coming-soon">Soon</span></a>
        <a class="side-link disabled-link" href="#" aria-disabled="true"><i class="fa-solid fa-triangle-exclamation"></i><span>Incidents</span><span class="coming-soon">Soon</span></a>
        <div class="nav-section-label">ADMINISTRATION</div>
        <a class="side-link <?= $currentPage === 'users.php' ? 'active' : '' ?>" href="users.php"><i class="fa-solid fa-users"></i><span>Users</span></a>
        <a class="side-link disabled-link" href="#" aria-disabled="true"><i class="fa-solid fa-gear"></i><span>Settings</span><span class="coming-soon">Soon</span></a>
    </nav>
    <div class="sidebar-bottom"><div class="sidebar-status"><span class="status-dot"></span>System ready <span class="phase-label">Phase 2A</span></div></div>
</aside>
<div class="main-column">
    <header class="topbar">
        <button class="btn btn-light sidebar-toggle" id="sidebar-toggle" aria-label="Toggle navigation"><i class="fa-solid fa-bars"></i></button>
        <div class="topbar-context"><span class="text-secondary">Internal IT</span><span class="topbar-divider">/</span><span class="fw-semibold"><?= e($pageTitle) ?></span></div>
        <div class="topbar-user">
            <div class="avatar"><?= e(mb_strtoupper(mb_substr($user['full_name'], 0, 1))) ?></div>
            <div class="d-none d-sm-block"><div class="user-name"><?= e($user['full_name']) ?></div><div class="user-role"><?= e($user['role']) ?></div></div>
            <div class="dropdown">
                <button class="btn btn-light btn-sm" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu"><i class="fa-solid fa-chevron-down"></i></button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text small text-secondary">Signed in as <?= e($user['username']) ?></span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><form method="post" action="logout.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="dropdown-item" type="submit"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Sign out</button></form></li>
                </ul>
            </div>
        </div>
    </header>
    <main class="main-content">
