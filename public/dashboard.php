<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
$user = require_auth();
$pageTitle = 'Dashboard';
$activeProjects = null;
$taskCounts = null;
try {
    $activeProjects = (int) db()->query("SELECT COUNT(*) FROM projects WHERE status = 'ACTIVE'")->fetchColumn();
    $taskCounts = db()->query("SELECT COUNT(*) AS total,
                                      COALESCE(SUM(status <> 'COMPLETED'), 0) AS open,
                                      COALESCE(SUM(status = 'IN PROGRESS'), 0) AS in_progress,
                                      COALESCE(SUM(status = 'FOR TESTING'), 0) AS for_testing,
                                      COALESCE(SUM(status = 'COMPLETED'), 0) AS completed
                               FROM tasks")->fetch();
} catch (Throwable $exception) {
    app_log('Dashboard development counts unavailable: ' . $exception->getMessage());
}
require dirname(__DIR__) . '/views/layout/header.php';
?>
<div class="page-heading"><div><div class="eyebrow">OVERVIEW</div><h1 class="h3 mb-1">Dashboard</h1><p class="text-secondary mb-0">Development and infrastructure status at a glance.</p></div></div>
<div class="row g-3 mt-1">
    <div class="col-12 col-xl-7"><section class="card h-100"><div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start"><div><div class="eyebrow">DEVELOPMENT</div><h2 class="h5 mt-1">Development overview</h2></div><span class="badge text-bg-light">Phase 2B</span></div>
        <div class="row row-cols-2 row-cols-lg-4 g-3 mt-1">
            <div class="col"><div class="metric-tile"><div class="metric-label">ACTIVE PROJECTS</div><div class="metric-value"><?= $activeProjects === null ? '—' : e($activeProjects) ?></div><a href="projects.php" class="small text-decoration-none">View projects</a></div></div>
            <div class="col"><div class="metric-tile"><div class="metric-label">OPEN TASKS</div><div class="metric-value"><?= $taskCounts === null ? '—' : e($taskCounts['open']) ?></div><a href="tasks.php" class="small text-decoration-none">View tasks</a></div></div>
            <div class="col"><div class="metric-tile"><div class="metric-label">IN PROGRESS</div><div class="metric-value"><?= $taskCounts === null ? '—' : e($taskCounts['in_progress']) ?></div><a href="tasks.php" class="small text-decoration-none">View tasks</a></div></div>
            <div class="col"><div class="metric-tile"><div class="metric-label">FOR TESTING</div><div class="metric-value"><?= $taskCounts === null ? '—' : e($taskCounts['for_testing']) ?></div><a href="tasks.php" class="small text-decoration-none">View tasks</a></div></div>
            <div class="col"><div class="metric-tile"><div class="metric-label">COMPLETED TASKS</div><div class="metric-value"><?= $taskCounts === null ? '—' : e($taskCounts['completed']) ?></div><a href="tasks.php" class="small text-decoration-none">View tasks</a></div></div>
            <div class="col"><div class="metric-tile"><div class="metric-label">TOTAL TASKS</div><div class="metric-value"><?= $taskCounts === null ? '—' : e($taskCounts['total']) ?></div><a href="tasks.php" class="small text-decoration-none">View tasks</a></div></div>
        </div>
        <div class="empty-state mt-4"><div class="empty-icon"><i class="fa-solid fa-chart-simple"></i></div><h3 class="h6">Development metrics are not available yet</h3><p class="mb-0">Task counts are live. Developer performance metrics will be added in a later phase.</p></div>
    </div></section></div>
    <div class="col-12 col-xl-5"><section class="card h-100"><div class="card-body p-4"><div class="eyebrow">INFRASTRUCTURE</div><h2 class="h5 mt-1">Infrastructure overview</h2><div class="empty-state mt-4"><div class="empty-icon">⌁</div><h3 class="h6">No infrastructure data yet</h3><p class="mb-0">Systems, servers, and monitoring will be added in a later phase.</p></div></div></section></div>
</div>
<div class="row g-3 mt-1"><div class="col-12"><section class="card"><div class="card-body p-4"><div class="eyebrow">WELCOME</div><h2 class="h5 mt-1 mb-2">Welcome, <?= e($user['full_name']) ?></h2><p class="text-secondary mb-0">You are signed in as <?= e($user['role']) ?>. Use the sidebar to access DPPMS modules.</p></div></section></div></div>
<?php require dirname(__DIR__) . '/views/layout/footer.php'; ?>
