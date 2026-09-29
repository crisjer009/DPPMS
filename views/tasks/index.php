<div class="page-heading">
    <div><div class="eyebrow">DEVELOPMENT</div><h1 class="h3 mb-1">Tasks</h1><p class="text-secondary mb-0">View tasks across projects and filter by project, owner, status, or priority.</p></div>
</div>
<div id="tasks-page-alert" class="alert d-none" role="alert"></div>
<section class="card mb-3"><div class="card-body"><div class="row g-3 align-items-end">
    <div class="col-sm-6 col-lg-3"><label class="form-label" for="filter-project">Project</label><select class="form-select form-select-sm" id="filter-project"><option value="">All projects</option></select></div>
    <div class="col-sm-6 col-lg-3"><label class="form-label" for="filter-assignee">Assigned Developer</label><select class="form-select form-select-sm" id="filter-assignee"><option value="">All developers</option></select></div>
    <div class="col-sm-6 col-lg-2"><label class="form-label" for="filter-status">Status</label><select class="form-select form-select-sm" id="filter-status"><option value="">All statuses</option><?php foreach (TASK_STATUSES as $status): ?><option value="<?= e($status) ?>"><?= e($status) ?></option><?php endforeach; ?></select></div>
    <div class="col-sm-6 col-lg-2"><label class="form-label" for="filter-priority">Priority</label><select class="form-select form-select-sm" id="filter-priority"><option value="">All priorities</option><?php foreach (TASK_PRIORITIES as $priority): ?><option value="<?= e($priority) ?>"><?= e($priority) ?></option><?php endforeach; ?></select></div>
    <div class="col-lg-2"><button type="button" class="btn btn-sm btn-light w-100" id="clear-task-filters">Clear filters</button></div>
</div></div></section>
<section class="card"><div class="card-body p-0"><div class="table-responsive"><table id="tasks-table" class="table table-hover align-middle mb-0 w-100"><thead><tr><th>Task Code</th><th>Project</th><th>Task</th><th>Assigned Developer</th><th>Priority</th><th>Status</th><th>Target Date</th><th>Blocked</th><th class="text-end">Actions</th></tr></thead><tbody></tbody></table></div></div></section>
