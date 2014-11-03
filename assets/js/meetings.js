(function ($) {
    $(document).ready(function() {
        $('input[type="checkbox"]').change(function() {
            var $checkbox = $(this);
            var url = $checkbox.attr('data-meeting-enable-url');

            if (url) {
                window.location.href = url;
            }
        })
    });
})(jQuery);
