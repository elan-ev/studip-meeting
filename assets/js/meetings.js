(function ($) {
    $(document).ready(function() {
        $('table.conference-meetings img.info').click(function () {
            $('div.info', $(this).closest('td')).toggle();
        });

        $('input[type="checkbox"]').change(function() {
            var $checkbox = $(this);
            var url = $checkbox.attr('data-meeting-enable-url');

            if (url) {
                window.location.href = url;
            }
        });

        $('table.conference-meetings a.edit-meeting').click(function (event) {
            event.preventDefault();
            var $editAnchor = $(this);
            var url = $editAnchor.attr('data-meeting-rename-url');
            var $cell = $('td.meeting-name', $editAnchor.closest('tr'));
            var $nameAnchor = $('a', $cell);
            var $nameInput = $('input', $cell);
            var $acceptButton = $('img.accept-button', $cell);
            var $declineButton = $('img.decline-button', $cell);
            var $loadingIndicator = $('img.loading-indicator', $cell);
            $nameInput.val($nameAnchor.text());

            function showRenameForm()
            {
                $nameAnchor.hide();
                $nameInput.show();
                $acceptButton.show();
                $declineButton.show();
            }

            function hideRenameForm()
            {
                $nameInput.hide();
                $acceptButton.hide();
                $declineButton.hide();
                $nameAnchor.show();
            }

            function submitRenameForm()
            {
                var newName = $nameInput.val();
                $loadingIndicator.show();

                $.post(url, { name: newName }, function () {
                    $nameAnchor.text(newName);
                    $loadingIndicator.hide();
                    hideRenameForm();
                });
            }

            showRenameForm();

            $nameInput.keypress(function (event) {
                if (event.which == 13) {
                    submitRenameForm();
                }
            });

            $acceptButton.click(function () {
                submitRenameForm();
            });

            $declineButton.click(function () {
                hideRenameForm();
            });
        });
    });
})(jQuery);
