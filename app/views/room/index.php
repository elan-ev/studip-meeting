<form method="post" action="<?= $controller->url_for('room/join_meeting/' . $invitations_link->hex . "/$cid")?>" class="default">
    <h1><?= $_('Sie wurden zur Teilnahme eingeladen') ?></h1>
    <label>
        <?= $_('Geben Sie einen Namen ein') ?>
        <input type="text" name="name" value="" autofocus>
    </label>
    <label>
        <?= $_('Oder verwenden Sie den Standard-Gästename') ?>
        <?= tooltipIcon(_('Sofern Sie keinen Namen eingeben, wird dieser standardmäßig verwendet.')) ?>
        <input type="text" value="<?= htmlReady($invitations_link->default_name) ?>" readonly>
    </label>
    <? if (!empty($check_recording_privacy_agreement)): ?>
        <label style="word-break: break-word !important;">
            <input type="checkbox" name="recording_privacy_agreement" id="recording_privacy_agreement" required>
            <span class="required">
            <?= $_('Ich bin damit einverstanden, dass diese Sitzung aufgezeichnet wird.
                Die Aufzeichnung kann Sprach- und Videoaufnahmen von mir beinhalten.
                Bitte beachten Sie, dass die Aufnahme im Anschluss geteilt werden kann.') ?>
            </span>
        </label>
    <? endif; ?>
    <footer>
        <?= \Studip\Button::createAccept($controller->_('Meeting betreten'))?>
    </footer>
</form>
