$(function () {
    const canManage = window.DPPMS.canManageProjects;
    const columns = [
        { data: 'project_code', render: function (value) { return '<span class="project-code">' + escapeHtml(value) + '</span>'; } },
        { data: 'project_name', render: escapeHtml },
        { data: 'department_requestor', render: function (value) { return value ? escapeHtml(value) : '<span class="text-secondary">—</span>'; } },
        { data: 'project_owner', render: escapeHtml },
        { data: 'assigned_developers', render: function (value) { return value ? escapeHtml(value) : '<span class="text-secondary">—</span>'; } },
        { data: 'priority', render: priorityBadge },
        { data: 'start_date', render: dateCell },
        { data: 'target_date', render: dateCell },
        { data: 'status', render: statusBadge },
        { data: null, orderable: false, searchable: false, className: 'text-end', render: actionButtons }
    ];

    const table = $('#projects-table').DataTable({
        ajax: {
            url: '../api/projects/list.php',
            dataSrc: function (response) { return response.data.projects || []; },
            error: function (xhr) { showAlert((xhr.responseJSON || {}).message || 'Unable to load projects.', 'danger'); }
        },
        columns: columns,
        order: [[1, 'asc']],
        pageLength: 10,
        language: { emptyTable: 'No projects have been created yet.', search: '', searchPlaceholder: 'Search projects...' }
    });

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }
    function priorityBadge(value) {
        const style = { LOW: 'priority-low', NORMAL: 'priority-normal', HIGH: 'priority-high', URGENT: 'priority-urgent' }[value] || 'priority-normal';
        return '<span class="badge ' + style + '">' + escapeHtml(value) + '</span>';
    }
    function statusBadge(value) {
        const style = { PLANNING: 'status-planning', ACTIVE: 'status-active', 'ON HOLD': 'status-hold', 'FOR TESTING': 'status-testing', 'FOR DEPLOYMENT': 'status-deployment', COMPLETED: 'status-completed', CANCELLED: 'status-cancelled' }[value] || 'status-planning';
        return '<span class="badge ' + style + '">' + escapeHtml(value) + '</span>';
    }
    function dateCell(value) {
        return value ? escapeHtml(value) : '<span class="text-secondary">—</span>';
    }
    function actionButtons(row) {
        let actions = '<button type="button" class="btn btn-sm btn-light view-project" data-id="' + Number(row.id) + '" title="View project"><i class="fa-solid fa-eye"></i></button>';
        if (canManage) actions += ' <button type="button" class="btn btn-sm btn-light edit-project" data-id="' + Number(row.id) + '" title="Edit project"><i class="fa-solid fa-pen"></i></button>';
        return actions;
    }
    function showAlert(message, type) {
        $('#projects-alert').removeClass('d-none alert-danger alert-success').addClass('alert-' + type).text(message);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    function loadOptions() {
        return $.getJSON('../api/projects/options.php').done(function (response) {
            const owner = $('#project-owner').empty().append($('<option>').val('').text('Select owner'));
            const members = $('#project-members').empty();
            response.data.users.forEach(function (user) {
                const label = user.full_name + ' · ' + user.role;
                owner.append($('<option>').val(user.id).text(label));
                members.append($('<option>').val(user.id).text(label));
            });
            const $alert = $('#projects-alert');
            if ($alert.text().indexOf('Unable to load active users') === 0) {
                $alert.addClass('d-none').removeClass('alert-danger').text('');
            }
        }).fail(function (xhr) {
            showAlert((xhr.responseJSON || {}).message || 'Unable to load active users.', 'danger');
        });
    }
    function openProjectForm(project) {
        const form = $('#project-form')[0];
        form.reset();
        $('[name="id"]', form).val(project ? project.id : '');
        $('[name="project_code"]', form).val(project ? project.project_code : '').prop('readonly', false);
        $('[name="project_name"]', form).val(project ? project.project_name : '');
        $('[name="description"]', form).val(project ? project.description : '');
        $('[name="department_requestor"]', form).val(project ? project.department_requestor : '');
        $('[name="project_owner_id"]', form).val(project ? String(project.project_owner_id) : '');
        $('[name="priority"]', form).val(project ? project.priority : 'NORMAL');
        $('[name="status"]', form).val(project ? project.status : 'PLANNING');
        $('[name="start_date"]', form).val(project ? project.start_date : '');
        $('[name="target_date"]', form).val(project ? project.target_date : '');
        const memberIds = project ? project.members.map(function (member) { return String(member.id); }) : [];
        $('#project-members').val(memberIds);
        $('#project-modal-title').text(project ? 'Edit project' : 'New project');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('project-modal')).show();
    }
    function getProject(id) {
        return $.getJSON('../api/projects/get.php', { id: id });
    }
    function textField(id, value) {
        $('#' + id).text(value || '—');
    }
    function renderDetail(project) {
        textField('detail-project-code', project.project_code);
        textField('detail-project-name', project.project_name);
        textField('detail-project-description', project.description || 'No description provided.');
        textField('detail-project-owner', project.project_owner);
        textField('detail-project-department', project.department_requestor);
        $('#detail-project-priority').html(priorityBadge(project.priority));
        $('#detail-project-status').html(statusBadge(project.status));
        textField('detail-project-start-date', project.start_date);
        textField('detail-project-target-date', project.target_date);
        const $members = $('#detail-project-members').empty();
        if (project.members.length) {
            project.members.forEach(function (member) {
                $members.append($('<span>').addClass('member-chip').text(member.full_name));
            });
        } else {
            $members.text('No developers assigned.');
        }
        textField('detail-project-created-by', project.created_by_name);
        textField('detail-project-created-at', project.created_at);
        textField('detail-project-updated-at', project.updated_at);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('project-detail-modal')).show();
    }

    if (canManage) {
        $('#add-project').on('click', function () {
            loadOptions().done(function () { openProjectForm(null); });
        });
        $('#project-form').on('submit', function (event) {
            event.preventDefault();
            const $button = $('#save-project').prop('disabled', true).text('Saving...');
            $.ajax({ url: '../api/projects/save.php', method: 'POST', data: $(this).serialize(), dataType: 'json' })
                .done(function (response) {
                    bootstrap.Modal.getInstance(document.getElementById('project-modal')).hide();
                    showAlert(response.message, 'success');
                    table.ajax.reload(null, false);
                }).fail(function (xhr) {
                    showAlert((xhr.responseJSON || {}).message || 'Unable to save the project.', 'danger');
                }).always(function () {
                    $button.prop('disabled', false).text('Save project');
                });
        });
    }
    $('#projects-table').on('click', '.view-project, .edit-project', function () {
        const id = Number($(this).data('id'));
        const editing = $(this).hasClass('edit-project');
        getProject(id).done(function (response) {
            if (editing) {
                loadOptions().done(function () { openProjectForm(response.data.project); });
            } else {
                renderDetail(response.data.project);
            }
        }).fail(function (xhr) {
            showAlert((xhr.responseJSON || {}).message || 'Unable to load project details.', 'danger');
        });
    });
});
