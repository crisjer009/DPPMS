$(function () {
    const columns = [
        { data: null, render: function (row) { return '<div class="fw-semibold">' + escapeHtml(row.full_name) + '</div><div class="small text-secondary">@' + escapeHtml(row.username) + '</div>'; } },
        { data: 'email', render: escapeHtml },
        { data: 'role', render: function (value) { return '<span class="badge role-badge">' + escapeHtml(value) + '</span>'; } },
        { data: 'status', render: function (value) { return '<span class="badge ' + (value === 'ACTIVE' ? 'text-bg-success-subtle text-success-emphasis' : 'text-bg-secondary-subtle text-secondary-emphasis') + '">' + escapeHtml(value) + '</span>'; } },
        { data: 'last_login_at', render: function (value) { return value ? escapeHtml(value) : '<span class="text-secondary">Never</span>'; } }
    ];
    if (window.DPPMS.canManageUsers) {
        columns.push({ data: null, orderable: false, searchable: false, className: 'text-end', defaultContent: '' });
    }

    const table = $('#users-table').DataTable({
        ajax: {
            url: '../api/users/list.php',
            dataSrc: function (response) { return response.data.users || []; },
            error: function (xhr) { showAlert((xhr.responseJSON || {}).message || 'Unable to load users.', 'danger'); }
        },
        columns: columns,
        order: [[0, 'asc']],
        pageLength: 10,
        language: { emptyTable: 'No users found.', search: '', searchPlaceholder: 'Search users...' },
        createdRow: function (row, data) {
            if (window.DPPMS.canManageUsers) {
                const actions = '<div class="d-flex justify-content-end gap-1"><button class="btn btn-sm btn-light edit-user" data-id="' + Number(data.id) + '" title="Edit"><i class="fa-solid fa-pen"></i></button><button class="btn btn-sm btn-light reset-password" data-id="' + Number(data.id) + '" title="Reset password"><i class="fa-solid fa-key"></i></button><button class="btn btn-sm btn-light toggle-user" data-id="' + Number(data.id) + '" data-status="' + (data.status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE') + '" title="' + (data.status === 'ACTIVE' ? 'Deactivate' : 'Activate') + '"><i class="fa-solid ' + (data.status === 'ACTIVE' ? 'fa-user-slash' : 'fa-user-check') + '"></i></button></div>';
                $('td', row).last().html(actions);
            }
        }
    });

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }
    function showAlert(message, type) {
        $('#users-alert').removeClass('d-none alert-danger alert-success').addClass('alert-' + type).text(message);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    function refresh() { table.ajax.reload(null, false); }
    function openModal(title, data) {
        const form = $('#user-form')[0];
        form.reset();
        $('[name="id"]', form).val(data ? data.id : '');
        $('[name="username"]', form).val(data ? data.username : '').prop('readonly', !!data);
        $('[name="full_name"]', form).val(data ? data.full_name : '');
        $('[name="email"]', form).val(data ? data.email : '');
        $('[name="role"]', form).val(data ? data.role : 'DEVELOPER');
        $('[name="status"]', form).val(data ? data.status : 'ACTIVE');
        $('[name="password"]', form).prop('required', !data).val('');
        $('#user-modal-title').text(title);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('user-modal')).show();
    }

    $('#add-user').on('click', function () { openModal('Add user', null); });
    $('#users-table').on('click', '.edit-user', function () {
        const id = Number($(this).data('id'));
        const user = table.rows().data().toArray().find(function (row) { return Number(row.id) === id; });
        if (user) openModal('Edit user', user);
    });
    $('#user-form').on('submit', function (event) {
        event.preventDefault();
        $.ajax({ url: '../api/users/save.php', method: 'POST', data: $(this).serialize(), dataType: 'json' })
            .done(function (response) {
                bootstrap.Modal.getInstance(document.getElementById('user-modal')).hide();
                showAlert(response.message, 'success'); refresh();
            }).fail(function (xhr) {
                showAlert((xhr.responseJSON || {}).message || 'Unable to save user.', 'danger');
            });
    });
    $('#users-table').on('click', '.toggle-user', function () {
        const button = $(this), id = Number(button.data('id')), status = button.data('status');
        if (!window.confirm((status === 'INACTIVE' ? 'Deactivate' : 'Activate') + ' this user?')) return;
        $.ajax({ url: '../api/users/status.php', method: 'POST', dataType: 'json', data: { id: id, status: status, csrf_token: window.DPPMS.csrfToken } })
            .done(function (response) { showAlert(response.message, 'success'); refresh(); })
            .fail(function (xhr) { showAlert((xhr.responseJSON || {}).message || 'Unable to update status.', 'danger'); });
    });
    $('#users-table').on('click', '.reset-password', function () {
        const id = Number($(this).data('id'));
        const password = window.prompt('Enter a new password:');
        if (password === null) return;
        $.ajax({ url: '../api/users/reset_password.php', method: 'POST', dataType: 'json', data: { id: id, password: password, csrf_token: window.DPPMS.csrfToken } })
            .done(function (response) { showAlert(response.message, 'success'); })
            .fail(function (xhr) { showAlert((xhr.responseJSON || {}).message || 'Unable to reset password.', 'danger'); });
    });
});
