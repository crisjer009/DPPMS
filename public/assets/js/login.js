$(function () {
    $('#login-form').on('submit', function (event) {
        event.preventDefault();
        const $form = $(this);
        const $button = $form.find('button[type="submit"]');
        const $alert = $('#login-alert');
        $button.prop('disabled', true).text('Signing in…');
        $alert.addClass('d-none').removeClass('alert-danger alert-success').text('');

        $.ajax({
            url: '../api/auth/login.php',
            method: 'POST',
            data: $form.serialize(),
            dataType: 'json'
        }).done(function (response) {
            window.location.href = response.data.redirect || 'dashboard.php';
        }).fail(function (xhr) {
            const response = xhr.responseJSON || {};
            $alert.addClass('alert-danger').removeClass('d-none').text(response.message || 'Unable to sign in. Try again later.');
            $button.prop('disabled', false).text('Sign in');
        });
    });
});
