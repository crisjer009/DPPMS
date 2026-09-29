<div class="page-heading">
    <div>
        <div class="eyebrow">DEVELOPMENT</div>
        <h1 class="h3 mb-1">Projects</h1>
        <p class="text-secondary mb-0">Manage projects and their assigned developers.</p>
    </div>
    <?php if ($canManageProjects): ?><button class="btn btn-primary" id="add-project"><i class="fa-solid fa-plus me-2"></i>New project</button><?php endif; ?>
</div>
<div id="projects-alert" class="alert d-none" role="alert"></div>
<section class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="projects-table" class="table table-hover align-middle mb-0">
                <thead><tr><th>Project Code</th><th>Project Name</th><th>Department / Requestor</th><th>Project Owner</th><th>Assigned Developers</th><th>Priority</th><th>Start Date</th><th>Target Date</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</section>

<div class="modal fade" id="project-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
        <form id="project-form">
            <div class="modal-header"><div><div class="eyebrow">PROJECT DETAILS</div><h2 class="modal-title fs-5" id="project-modal-title">New project</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id">
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label" for="project-code">Project Code <span class="text-danger">*</span></label><input class="form-control" id="project-code" name="project_code" maxlength="40" required></div>
                    <div class="col-md-8"><label class="form-label" for="project-name">Project Name <span class="text-danger">*</span></label><input class="form-control" id="project-name" name="project_name" maxlength="200" required></div>
                    <div class="col-12"><label class="form-label" for="project-description">Description</label><textarea class="form-control" id="project-description" name="description" rows="3" maxlength="12000"></textarea></div>
                    <div class="col-md-6"><label class="form-label" for="project-owner">Project Owner <span class="text-danger">*</span></label><select class="form-select" id="project-owner" name="project_owner_id" required><option value="">Select owner</option></select></div>
                    <div class="col-md-6"><label class="form-label" for="project-department">Department / Requestor</label><input class="form-control" id="project-department" name="department_requestor" maxlength="190"></div>
                    <div class="col-md-4"><label class="form-label" for="project-priority">Priority</label><select class="form-select" id="project-priority" name="priority" required><?php foreach (PROJECT_PRIORITIES as $priority): ?><option value="<?= e($priority) ?>" <?= $priority === 'NORMAL' ? 'selected' : '' ?>><?= e($priority) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-4"><label class="form-label" for="project-status">Status</label><select class="form-select" id="project-status" name="status" required><?php foreach (PROJECT_STATUSES as $status): ?><option value="<?= e($status) ?>" <?= $status === 'PLANNING' ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-4"><label class="form-label" for="project-start-date">Start Date</label><input class="form-control" id="project-start-date" name="start_date" type="date"></div>
                    <div class="col-md-4"><label class="form-label" for="project-target-date">Target Date</label><input class="form-control" id="project-target-date" name="target_date" type="date"></div>
                    <div class="col-12"><label class="form-label" for="project-members">Assigned Developers</label><select class="form-select" id="project-members" name="member_ids[]" multiple size="5"></select><div class="form-text">Hold Ctrl (Windows) or Command (Mac) to select multiple users. Owner and members are managed separately.</div></div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary" id="save-project">Save project</button></div>
        </form>
    </div></div>
</div>

<div class="modal fade" id="project-detail-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header"><div><div class="eyebrow" id="detail-project-code"></div><h2 class="modal-title fs-5" id="detail-project-name">Project</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body">
            <p class="text-secondary" id="detail-project-description"></p>
            <div class="row g-3 detail-grid">
                <div class="col-sm-6"><div class="detail-label">Project Owner</div><div id="detail-project-owner"></div></div>
                <div class="col-sm-6"><div class="detail-label">Department / Requestor</div><div id="detail-project-department"></div></div>
                <div class="col-sm-3"><div class="detail-label">Priority</div><div id="detail-project-priority"></div></div>
                <div class="col-sm-3"><div class="detail-label">Status</div><div id="detail-project-status"></div></div>
                <div class="col-sm-3"><div class="detail-label">Start Date</div><div id="detail-project-start-date"></div></div>
                <div class="col-sm-3"><div class="detail-label">Target Date</div><div id="detail-project-target-date"></div></div>
                <div class="col-12"><div class="detail-label">Assigned Developers</div><div id="detail-project-members" class="member-list"></div></div>
                <div class="col-sm-4"><div class="detail-label">Created By</div><div id="detail-project-created-by"></div></div>
                <div class="col-sm-4"><div class="detail-label">Created At</div><div id="detail-project-created-at"></div></div>
                <div class="col-sm-4"><div class="detail-label">Updated At</div><div id="detail-project-updated-at"></div></div>
            </div>
            <hr class="my-4">
            <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                <div><div class="eyebrow">TASK SUMMARY</div><h3 class="h6 mt-1 mb-0">Project tasks</h3></div>
                <?php if ($canManageProjects): ?><button type="button" class="btn btn-sm btn-primary" id="add-project-task"><i class="fa-solid fa-plus me-1"></i>Add Task</button><?php endif; ?>
            </div>
            <div class="project-progress-wrap mt-3"><div class="d-flex justify-content-between small mb-1"><span>Project task completion</span><strong id="project-task-progress-label">0%</strong></div><div class="progress" role="progressbar" aria-label="Project task completion" aria-valuemin="0" aria-valuemax="100"><div id="project-task-progress" class="progress-bar" style="width:0%"></div></div></div>
            <div class="row row-cols-2 row-cols-md-4 g-2 mt-1" id="project-task-summary">
                <?php foreach (['total' => 'Total Tasks', 'backlog' => 'Backlog', 'to_do' => 'To Do', 'in_progress' => 'In Progress', 'for_testing' => 'For Testing', 'for_revision' => 'For Revision', 'for_deployment' => 'For Deployment', 'completed' => 'Completed'] as $summaryKey => $summaryLabel): ?>
                    <div class="col"><div class="task-summary-tile"><div><?= e($summaryLabel) ?></div><strong data-summary="<?= e($summaryKey) ?>">—</strong></div></div>
                <?php endforeach; ?>
            </div>
            <div class="table-responsive mt-3"><table id="project-tasks-table" class="table table-hover align-middle mb-0 w-100"><thead><tr><th>Task Code</th><th>Task Title</th><th>Assigned To</th><th>Priority</th><th>Status</th><th>Start Date</th><th>Target Date</th><th>Blocked</th><th class="text-end">Actions</th></tr></thead><tbody></tbody></table></div>
        </div>
    </div></div>
</div>
