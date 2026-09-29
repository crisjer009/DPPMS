$(function () {
    $('#sidebar-toggle').on('click', function () {
        $('#sidebar').toggleClass('sidebar-open');
    });
    $(document).on('click', function (event) {
        if ($(window).width() < 992 && !$(event.target).closest('#sidebar, #sidebar-toggle').length) {
            $('#sidebar').removeClass('sidebar-open');
        }
    });
});
