$(function () {
    const canManage = window.DPPMS.canManageTasks;
    let currentProjectId = null;
    let lastDetailTaskId = null;
    let projectTasksTable = null;
    let globalTasksTable = null;

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }
    function dash() {
        return '\u2014';
    }
    function priorityBadge(value) {
        const style = { LOW: 'priority-low', NORMAL: 'priority-normal', HIGH: 'priority-high', URGENT: 'priority-urgent' }[value] || 'priority-normal';
        return '<span class="badge ' + style + '">' + escapeHtml(value) + '</span>';
    }
    function statusBadge(value) {
        const style = { BACKLOG: 'task-status-backlog', 'TO DO': 'task-status-todo', 'IN PROGRESS': 'task-status-progress', 'FOR TESTING': 'task-status-testing', 'FOR REVISION': 'task-status-revision', 'FOR DEPLOYMENT': 'task-status-deployment', COMPLETED: 'task-status-completed', CANCELLED: 'task-status-cancelled' }[value] || 'task-status-backlog';
        return '<span class="badge ' + style + '">' + escapeHtml(value) + '</span>';
    }
    function dateCell(value) {
        return value ? escapeHtml(value) : '<span class="text-secondary">' + dash() + '</span>';
    }
    function blockedBadge(value) {
        return Number(value) === 1 ? '<span class="badge text-bg-danger-subtle text-danger-emphasis">Blocked</span>' : '<span class="text-secondary">' + dash() + '</span>';
    }
    function taskActions(task) {
        let actions = '<button type="button" class="btn btn-sm btn-light view-task" data-id="' + Number(task.id) + '" title="View task"><i class="fa-solid fa-eye"></i></button>';
        if (canManage) actions += ' <button type="button" class="btn btn-sm btn-light edit-task" data-id="' + Number(task.id) + '" title="Edit task"><i class="fa-solid fa-pen"></i></button>';
        actions += ' <button type="button" class="btn btn-sm btn-light history-task" data-id="' + Number(task.id) + '" title="View task history"><i class="fa-solid fa-clock-rotate-left"></i></button>';
        return actions;
    }
    function showTaskAlert(message, type) {
        const $alert = $('#task-alert').length ? $('#task-alert') : $('#tasks-page-alert');
        $alert.removeClass('d-none alert-danger alert-success alert-warning').addClass('alert-' + type).text(message);
    }
    function clearTaskAlert() {
        $('#task-alert, #tasks-page-alert').addClass('d-none').removeClass('alert-danger alert-success alert-warning').text('');
    }
    function loadProjectMembers(projectId) {
        return $.getJSON('../api/tasks/options.php', { project_id: projectId }).done(function (response) {
            const $assignee = $('#task-assignee').empty().append($('<option>').val('').text('Unassigned'));
            response.data.members.forEach(function (member) {
                const inactive = member.status === 'ACTIVE' ? '' : ' (inactive)';
                $assignee.append($('<option>').val(member.id).text(member.full_name + ' · ' + member.role + inactive));
            });
            clearTaskAlert();
        }).fail(function (xhr) {
            showTaskAlert((xhr.responseJSON || {}).message || 'Unable to load project members.', 'danger');
        });
    }
    function openTaskForm(task, projectId) {
        const form = $('#task-form')[0];
        form.reset();
        $('[name="id"]', form).val(task ? task.id : '');
        $('[name="project_id"]', form).val(projectId);
        $('[name="task_title"]', form).val(task ? task.task_title : '');
        $('[name="description"]', form).val(task ? task.description : '');
        $('[name="assigned_to"]', form).val(task && task.assigned_to ? String(task.assigned_to) : '');
        $('[name="priority"]', form).val(task ? task.priority : 'NORMAL');
        $('[name="status"]', form).val(task ? task.status : 'BACKLOG');
        $('[name="start_date"]', form).val(task ? task.start_date : '');
        $('[name="target_date"]', form).val(task ? task.target_date : '');
        $('[name="estimated_hours"]', form).val(task ? task.estimated_hours : '');
        $('[name="actual_hours"]', form).val(task ? task.actual_hours : '');
        $('#task-is-blocked').prop('checked', !!(task && Number(task.is_blocked) === 1)).trigger('change');
        $('[name="blocked_reason"]', form).val(task ? task.blocked_reason : '');
        $('#task-modal-title').text(task ? 'Edit Task' : 'Add Task');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('task-modal')).show();
    }
    function getTask(taskId) {
        return $.getJSON('../api/tasks/get.php', { id: taskId });
    }
    function textField(id, value) {
        $('#' + id).text(value === null || value === '' ? '—' : value);
    }
    function showTaskDetails(task) {
        lastDetailTaskId = Number(task.id);
        textField('detail-task-code', task.task_code);
        textField('detail-task-title', task.task_title);
        textField('detail-task-description', task.description || 'No description provided.');
        textField('detail-task-project', task.project_code + ' · ' + task.project_name);
        textField('detail-task-assignee', task.assigned_developer || 'Unassigned');
        $('#detail-task-priority').html(priorityBadge(task.priority));
        $('#detail-task-status').html(statusBadge(task.status));
        textField('detail-task-start-date', task.start_date);
        textField('detail-task-target-date', task.target_date);
        textField('detail-task-estimated-hours', task.estimated_hours === null ? null : task.estimated_hours + ' h');
        textField('detail-task-actual-hours', task.actual_hours === null ? null : task.actual_hours + ' h');
        $('#detail-task-blocked').html(blockedBadge(task.is_blocked));
        textField('detail-task-blocked-reason', Number(task.is_blocked) === 1 ? task.blocked_reason : null);
        textField('detail-task-created-by', task.created_by_name);
        textField('detail-task-created-at', task.created_at);
        textField('detail-task-updated-at', task.updated_at);
        textField('detail-task-completed-at', task.completed_at);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('task-detail-modal')).show();
    }
    function actionTitle(entry) {
        const titles = {
            TASK_CREATED: 'Task created',
            STATUS_CHANGED: 'Status changed',
            ASSIGNEE_CHANGED: 'Assigned developer changed',
            PRIORITY_CHANGED: 'Priority changed',
            TARGET_DATE_CHANGED: 'Target date changed',
            BLOCKED: 'Task blocked',
            UNBLOCKED: 'Task unblocked',
            BLOCK_REASON_CHANGED: 'Blocked reason changed',
            TASK_COMPLETED: 'Task completed',
            TASK_REOPENED: 'Task reopened',
            TASK_TITLE_CHANGED: 'Task title changed',
            DESCRIPTION_UPDATED: 'Description updated',
            FIELD_CHANGED: (entry.field_name || 'Task field').replace(/_/g, ' ') + ' changed'
        };
        return titles[entry.action_type] || entry.action_type.replace(/_/g, ' ').toLowerCase();
    }
    $('#project-detail-modal').on('shown.bs.modal', function () {
        if (projectTasksTable) projectTasksTable.columns.adjust();
    });
    function renderHistory(entries, taskTitle) {
        $('#task-history-title').text('History · ' + taskTitle);
        const $list = $('#task-history-list').empty();
        if (!entries.length) {
            $list.append($('<div>').addClass('empty-state').append($('<p>').addClass('mb-0').text('No history has been recorded for this task yet.')));
        }
        entries.forEach(function (entry) {
            const $item = $('<article>').addClass('task-history-item');
            const $top = $('<div>').addClass('task-history-top');
            $top.append($('<strong>').text(actionTitle(entry)));
            $top.append($('<time>').text(entry.created_at));
            $item.append($top);
            if (entry.action_type === 'STATUS_CHANGED' || entry.action_type === 'ASSIGNEE_CHANGED' || entry.action_type === 'PRIORITY_CHANGED' || entry.action_type === 'TARGET_DATE_CHANGED' || entry.action_type === 'TASK_TITLE_CHANGED' || entry.action_type === 'FIELD_CHANGED' || entry.action_type === 'BLOCK_REASON_CHANGED') {
                const $change = $('<div>').addClass('task-history-change');
                $change.append($('<span>').text(entry.old_value || '(none)'));
                $change.append($('<i>').addClass('fa-solid fa-arrow-right').attr('aria-hidden', 'true'));
                $change.append($('<span>').text(entry.new_value || '(none)'));
                $item.append($change);
            } else if (entry.action_type === 'TASK_CREATED') {
                $item.append($('<div>').addClass('task-history-change').text(entry.new_value || 'Task created.'));
            } else if (entry.action_type === 'BLOCKED' || entry.action_type === 'UNBLOCKED') {
                if (entry.old_value || entry.new_value) {
                    $item.append($('<div>').addClass('task-history-note').text(entry.new_value || entry.old_value));
                }
            }
            if (entry.changed_by_name) $item.append($('<div>').addClass('task-history-actor').text('By ' + entry.changed_by_name));
            $list.append($item);
        });
        bootstrap.Modal.getOrCreateInstance(document.getElementById('task-history-modal')).show();
    }
    function openHistory(taskId, taskTitle) {
        $.getJSON('../api/tasks/history.php', { task_id: taskId }).done(function (response) {
            renderHistory(response.data.history, taskTitle || ('Task #' + taskId));
        }).fail(function (xhr) {
            showTaskAlert((xhr.responseJSON || {}).message || 'Unable to load task history.', 'danger');
        });
    }
    function loadTaskDetails(taskId, action) {
        getTask(taskId).done(function (response) {
            const task = response.data.task;
            if (action === 'view') showTaskDetails(task);
            if (action === 'history') openHistory(task.id, task.task_title);
            if (action === 'edit') {
                loadProjectMembers(task.project_id).done(function () { openTaskForm(task, task.project_id); });
            }
        }).fail(function (xhr) {
            showTaskAlert((xhr.responseJSON || {}).message || 'Unable to load task details.', 'danger');
        });
    }
    function baseColumns(projectColumn) {
        const columns = [{ data: 'task_code', render: function (value) { return '<span class="project-code">' + escapeHtml(value) + '</span>'; } }];
        if (projectColumn) columns.push({ data: null, render: function (task) { return '<div class="fw-semibold">' + escapeHtml(task.project_code) + '</div><div class="small text-secondary">' + escapeHtml(task.project_name) + '</div>'; } });
        columns.push(
            { data: 'task_title', render: escapeHtml },
            { data: 'assigned_developer', render: function (value) { return value ? escapeHtml(value) : '<span class="text-secondary">Unassigned</span>'; } },
            { data: 'priority', render: priorityBadge },
            { data: 'status', render: statusBadge }
        );
        if (projectColumn) columns.push({ data: 'start_date', render: dateCell });
        columns.push({ data: 'target_date', render: dateCell });
        columns.push({ data: 'is_blocked', render: blockedBadge });
        columns.push({ data: null, orderable: false, searchable: false, className: 'text-end', render: taskActions });
        return columns;
    }
    function updateProjectSummary(summary) {
        if (!summary) return;
        Object.keys(summary).forEach(function (key) {
            if (key !== 'progress_percent') $('[data-summary="' + key + '"]').text(summary[key]);
        });
        const percent = Number(summary.progress_percent || 0);
        $('#project-task-progress-label').text(percent + '%');
        $('#project-task-progress').css('width', percent + '%').attr('aria-valuenow', percent);
    }
    function showProjectTasks(projectId) {
        currentProjectId = Number(projectId);
        if (!projectTasksTable) {
            projectTasksTable = $('#project-tasks-table').DataTable({
                ajax: {
                    url: '../api/tasks/list.php',
                    data: function (request) { request.project_id = currentProjectId; },
                    dataSrc: function (response) {
                        updateProjectSummary(response.data.summary);
                        return response.data.tasks || [];
                    },
                    error: function (xhr) { showTaskAlert((xhr.responseJSON || {}).message || 'Unable to load project tasks.', 'danger'); }
                },
                columns: baseColumns(false),
                order: [[0, 'asc']],
                pageLength: 5,
                lengthChange: false,
                searching: true,
                language: { emptyTable: 'No tasks have been created for this project.', search: '', searchPlaceholder: 'Search project tasks...' }
            });
        } else {
            projectTasksTable.ajax.reload();
        }
    }
    function loadGlobalFilters() {
        $.getJSON('../api/tasks/filters.php').done(function (response) {
            const $projects = $('#filter-project').empty().append($('<option>').val('').text('All projects'));
            response.data.projects.forEach(function (project) {
                $projects.append($('<option>').val(project.id).text(project.project_code + ' · ' + project.project_name));
            });
            const $users = $('#filter-assignee').empty().append($('<option>').val('').text('All developers'));
            response.data.users.forEach(function (user) {
                $users.append($('<option>').val(user.id).text(user.full_name));
            });
        }).fail(function (xhr) {
            $('#tasks-page-alert').removeClass('d-none').addClass('alert-danger').text((xhr.responseJSON || {}).message || 'Unable to load task filters.');
        });
    }
    function initializeGlobalTasksTable() {
        globalTasksTable = $('#tasks-table').DataTable({
            ajax: {
                url: '../api/tasks/list.php',
                data: function (request) {
                    request.project_id = $('#filter-project').val();
                    request.assigned_to = $('#filter-assignee').val();
                    request.status = $('#filter-status').val();
                    request.priority = $('#filter-priority').val();
                },
                dataSrc: function (response) { return response.data.tasks || []; },
                error: function (xhr) { $('#tasks-page-alert').removeClass('d-none').addClass('alert-danger').text((xhr.responseJSON || {}).message || 'Unable to load tasks.'); }
            },
            columns: baseColumns(true),
            order: [[0, 'asc']],
            pageLength: 10,
            language: { emptyTable: 'No tasks match these filters.', search: '', searchPlaceholder: 'Search tasks...' }
        });
        $('#filter-project, #filter-assignee, #filter-status, #filter-priority').on('change', function () { globalTasksTable.ajax.reload(); });
        $('#clear-task-filters').on('click', function () {
            $('#filter-project, #filter-assignee, #filter-status, #filter-priority').val('');
            globalTasksTable.ajax.reload();
        });
        loadGlobalFilters();
    }

    window.DPPMSTasks = { showProjectTasks: showProjectTasks };
    $(document).on('dppms:project-opened', function (event, projectId) { showProjectTasks(projectId); });
    $('#add-project-task').on('click', function () {
        if (!currentProjectId) return;
        loadProjectMembers(currentProjectId).done(function () { openTaskForm(null, currentProjectId); });
    });
    $('#task-is-blocked').on('change', function () {
        const blocked = $(this).is(':checked');
        $('#task-blocked-reason-wrap').toggleClass('d-none', !blocked);
        $('#task-blocked-reason').prop('required', blocked);
    });
    $('#task-form').on('submit', function (event) {
        event.preventDefault();
        const $button = $('#save-task').prop('disabled', true).text('Saving...');
        $.ajax({ url: '../api/tasks/save.php', method: 'POST', data: $(this).serialize(), dataType: 'json' })
            .done(function (response) {
                bootstrap.Modal.getInstance(document.getElementById('task-modal')).hide();
                showTaskAlert(response.message + (response.data.task_code ? ' (' + response.data.task_code + ')' : ''), 'success');
                if (projectTasksTable) projectTasksTable.ajax.reload(null, false);
                if (globalTasksTable) globalTasksTable.ajax.reload(null, false);
            }).fail(function (xhr) {
                showTaskAlert((xhr.responseJSON || {}).message || 'Unable to save the task.', 'danger');
            }).always(function () {
                $button.prop('disabled', false).text('Save Task');
            });
    });
    $(document).on('click', '.view-task, .edit-task, .history-task', function () {
        const taskId = Number($(this).data('id'));
        const action = $(this).hasClass('edit-task') ? 'edit' : ($(this).hasClass('history-task') ? 'history' : 'view');
        loadTaskDetails(taskId, action);
    });
    $('#task-detail-history').on('click', function () {
        const title = $('#detail-task-title').text();
        const modal = bootstrap.Modal.getInstance(document.getElementById('task-detail-modal'));
        if (modal) modal.hide();
        openHistory(lastDetailTaskId, title);
    });
    if ($('#tasks-table').length) initializeGlobalTasksTable();
});
