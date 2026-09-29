<div id="task-alert" class="alert d-none task-global-alert" role="alert"></div>

<div class="modal fade" id="task-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
        <form id="task-form">
            <div class="modal-header"><div><div class="eyebrow">TASK MANAGEMENT</div><h2 class="modal-title fs-5" id="task-modal-title">Add Task</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id"><input type="hidden" name="project_id">
                <div class="row g-3">
                    <div class="col-12"><label class="form-label" for="task-title">Task Title <span class="text-danger">*</span></label><input class="form-control" id="task-title" name="task_title" maxlength="200" required></div>
                    <div class="col-12"><label class="form-label" for="task-description">Description</label><textarea class="form-control" id="task-description" name="description" rows="3" maxlength="12000"></textarea><div class="form-text">Do not include passwords, tokens, or other secrets.</div></div>
                    <div class="col-md-6"><label class="form-label" for="task-assignee">Assigned Developer</label><select class="form-select" id="task-assignee" name="assigned_to"><option value="">Unassigned</option></select><div class="form-text">Only members of the selected project can be assigned.</div></div>
                    <div class="col-md-3"><label class="form-label" for="task-priority">Priority</label><select class="form-select" id="task-priority" name="priority" required><?php foreach (TASK_PRIORITIES as $priority): ?><option value="<?= e($priority) ?>" <?= $priority === 'NORMAL' ? 'selected' : '' ?>><?= e($priority) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-3"><label class="form-label" for="task-status">Status</label><select class="form-select" id="task-status" name="status" required><?php foreach (TASK_STATUSES as $status): ?><option value="<?= e($status) ?>" <?= $status === 'BACKLOG' ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-3"><label class="form-label" for="task-start-date">Start Date</label><input class="form-control" id="task-start-date" name="start_date" type="date"></div>
                    <div class="col-md-3"><label class="form-label" for="task-target-date">Target Date</label><input class="form-control" id="task-target-date" name="target_date" type="date"></div>
                    <div class="col-md-3"><label class="form-label" for="task-estimated-hours">Estimated Hours</label><input class="form-control" id="task-estimated-hours" name="estimated_hours" type="number" min="0" step="0.01"></div>
                    <div class="col-md-3"><label class="form-label" for="task-actual-hours">Actual Hours</label><input class="form-control" id="task-actual-hours" name="actual_hours" type="number" min="0" step="0.01"></div>
                    <div class="col-12"><div class="form-check"><input class="form-check-input" id="task-is-blocked" name="is_blocked" type="checkbox" value="1"><label class="form-check-label" for="task-is-blocked">This task is blocked</label></div></div>
                    <div class="col-12 d-none" id="task-blocked-reason-wrap"><label class="form-label" for="task-blocked-reason">Blocked Reason <span class="text-danger">*</span></label><textarea class="form-control" id="task-blocked-reason" name="blocked_reason" rows="2" maxlength="5000"></textarea><div class="form-text">Do not include passwords, tokens, or other secrets. Blocked reason changes are saved in task history.</div></div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary" id="save-task">Save Task</button></div>
        </form>
    </div></div>
</div>

<div class="modal fade" id="task-detail-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header"><div><div class="eyebrow" id="detail-task-code"></div><h2 class="modal-title fs-5" id="detail-task-title">Task</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body">
            <p class="text-secondary" id="detail-task-description"></p>
            <div class="row g-3 detail-grid">
                <div class="col-sm-6"><div class="detail-label">Project</div><div id="detail-task-project"></div></div>
                <div class="col-sm-6"><div class="detail-label">Assigned Developer</div><div id="detail-task-assignee"></div></div>
                <div class="col-sm-3"><div class="detail-label">Priority</div><div id="detail-task-priority"></div></div>
                <div class="col-sm-3"><div class="detail-label">Status</div><div id="detail-task-status"></div></div>
                <div class="col-sm-3"><div class="detail-label">Start Date</div><div id="detail-task-start-date"></div></div>
                <div class="col-sm-3"><div class="detail-label">Target Date</div><div id="detail-task-target-date"></div></div>
                <div class="col-sm-3"><div class="detail-label">Estimated Hours</div><div id="detail-task-estimated-hours"></div></div>
                <div class="col-sm-3"><div class="detail-label">Actual Hours</div><div id="detail-task-actual-hours"></div></div>
                <div class="col-sm-3"><div class="detail-label">Blocked</div><div id="detail-task-blocked"></div></div>
                <div class="col-sm-9"><div class="detail-label">Blocked Reason</div><div id="detail-task-blocked-reason"></div></div>
                <div class="col-sm-4"><div class="detail-label">Created By</div><div id="detail-task-created-by"></div></div>
                <div class="col-sm-4"><div class="detail-label">Created At</div><div id="detail-task-created-at"></div></div>
                <div class="col-sm-4"><div class="detail-label">Updated At</div><div id="detail-task-updated-at"></div></div>
                <div class="col-sm-4"><div class="detail-label">Completed At</div><div id="detail-task-completed-at"></div></div>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-light" id="task-detail-history"><i class="fa-solid fa-clock-rotate-left me-1"></i>View History</button><button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button></div>
    </div></div>
</div>

<div class="modal fade" id="task-history-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header"><div><div class="eyebrow">ACTIVITY HISTORY</div><h2 class="modal-title fs-5" id="task-history-title">Task History</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body"><div id="task-history-list" class="task-history-list"></div></div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button></div>
    </div></div>
</div>
