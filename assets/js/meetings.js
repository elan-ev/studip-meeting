(function ($) {
    $(document).ready(function() {
        $('ul.sidebar-meeting-views a.toggle-info').on('click', function (event) {
            event.preventDefault();
            var $el = $(this);

            if ($el.hasClass('show-info')) {
                $el.removeClass('show-info');
                $el.addClass('hide-info');
                $el.text($el.attr('data-hide-text'));
                $('table.conference-meetings tr.info').show();
            } else {
                $el.removeClass('hide-info');
                $el.addClass('show-info');
                $el.text($el.attr('data-show-text'));
                $('table.conference-meetings tr.info').hide();
            }
        });

        $('table.conference-meetings img.info').click(function () {
            $(this).parent().parent().next().toggle();
        });

        $('input[type="checkbox"]').change(function() {
            var $checkbox = $(this);
            var url = $checkbox.attr('data-meeting-enable-url');

            if (url) {
                window.location.href = url;
            }
        });

        /*
        var tableSorterHeaders = {};
        $('table.conference-meetings.admin thead th').each(function (index, cell) {
            if (!$(cell).hasClass('sortable')) {
                tableSorterHeaders[index] = { sorter: false };
            }
        });
        $('table.conference-meetings.admin').tablesorter({
            headers: tableSorterHeaders,
            sortList: [[1, 0]],
            textExtraction: 'complex'
        });
        */

        $('table.conference-meetings a.edit-meeting').click(function (event) {
            event.preventDefault();
            var $editAnchor = $(this);
            var url = $editAnchor.attr('data-meeting-edit-url');
            var $cell = $('td.meeting-name', $editAnchor.closest('tr'));
            var $nameAnchor = $('a', $cell);
            var $nameInput = $('input[name="name"]', $cell);
            var $recordingUrlInput = $('input[name="recording_url"]', $cell);
            var $acceptButton = $('img.accept-button', $cell);
            var $declineButton = $('img.decline-button', $cell);
            var $loadingIndicator = $('img.loading-indicator', $cell);
            var meetingId = $editAnchor.closest('tr').attr('data-meeting-id');
            var $recordingUrlAnchors = $('tr[data-meeting-id='+meetingId+'] a.meeting-recording-url');
            $nameInput.val($('span', $nameAnchor).text());
            var recordingUrl = $('a.meeting-recording-url', $editAnchor.closest('tr')).attr('href');
            $recordingUrlInput.val(recordingUrl);

            function showRenameForm()
            {
                $nameAnchor.hide();
                $nameInput.show();
                $recordingUrlInput.show();
                $acceptButton.show();
                $declineButton.show();
            }

            function hideRenameForm()
            {
                $nameInput.hide();
                $recordingUrlInput.hide();
                $acceptButton.hide();
                $declineButton.hide();
                $nameAnchor.show();

                if (recordingUrl.trim() != '') {
                    $recordingUrlAnchors.show();
                } else {
                    $recordingUrlAnchors.hide();
                }
            }

            function submitRenameForm()
            {
                var newName = $nameInput.val();
                recordingUrl = $recordingUrlInput.val();
                $loadingIndicator.show();

                $.post(url, { name: newName, recording_url: recordingUrl }, function () {
                    // change the name of the meeting room in all courses
                    $('tr[data-meeting-id='+meetingId+'] td.meeting-name a span').text(newName);

                    $recordingUrlAnchors.attr('href', recordingUrl);
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

        $('input[name="check_all"]').click(function () {
            var $controlCheckbox = $(this);
            var $checkboxesToChange = $('.check_all', $(this).closest('table'));

            if ($controlCheckbox.attr('checked')) {
                $checkboxesToChange.attr('checked', 'checked');
            } else {
                $checkboxesToChange.removeAttr('checked');
            }
        });
    });
})(jQuery);
