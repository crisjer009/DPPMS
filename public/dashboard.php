<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
$user = require_auth();
$pageTitle = 'Dashboard';
$activeProjects = null;
try {
    $activeProjects = (int) db()->query("SELECT COUNT(*) FROM projects WHERE status = 'ACTIVE'")->fetchColumn();
} catch (Throwable $exception) {
    app_log('Dashboard active-project count unavailable: ' . $exception->getMessage());
}
require dirname(__DIR__) . '/views/layout/header.php';
?>
<div class="page-heading"><div><div class="eyebrow">OVERVIEW</div><h1 class="h3 mb-1">Dashboard</h1><p class="text-secondary mb-0">Development and infrastructure status at a glance.</p></div></div>
<div class="row g-3 mt-1"><div class="col-12 col-xl-7"><section class="card h-100"><div class="card-body p-4"><div class="d-flex justify-content-between align-items-start"><div><div class="eyebrow">DEVELOPMENT</div><h2 class="h5 mt-1">Development overview</h2></div><span class="badge text-bg-light">Phase 2A</span></div><div class="row g-3 mt-2"><div class="col-sm-6"><div class="metric-tile"><div class="metric-label">ACTIVE PROJECTS</div><div class="metric-value"><?= $activeProjects === null ? '—' : e($activeProjects) ?></div><a href="projects.php" class="small text-decoration-none">View projects <i class="fa-solid fa-arrow-right ms-1"></i></a></div></div></div><div class="empty-state mt-4"><div class="empty-icon">⌁</div><h3 class="h6">Task metrics are not available yet</h3><p class="mb-0">Tasks, development board, and development metrics will be added in later phases.</p></div></div></section></div><div class="col-12 col-xl-5"><section class="card h-100"><div class="card-body p-4"><div class="eyebrow">INFRASTRUCTURE</div><h2 class="h5 mt-1">Infrastructure overview</h2><div class="empty-state mt-4"><div class="empty-icon">⌁</div><h3 class="h6">No infrastructure data yet</h3><p class="mb-0">Systems, servers, and monitoring will be added in a later phase.</p></div></div></section></div></div>
<div class="row g-3 mt-1"><div class="col-12"><section class="card"><div class="card-body p-4"><div class="eyebrow">WELCOME</div><h2 class="h5 mt-1 mb-2">Welcome, <?= e($user['full_name']) ?></h2><p class="text-secondary mb-0">You are signed in as <?= e($user['role']) ?>. Use the sidebar to manage user access.</p></div></section></div></div>
<?php require dirname(__DIR__) . '/views/layout/footer.php'; ?>
