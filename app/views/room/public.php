<form method="post" action="<?= $controller->url_for("room/public/$room_id/$cid" . ($qr ? '/1' : ''))?>" class="default">
    <label>
        <?= $_('Geben Sie einen Namen ein') ?>
        <input type="text" name="name" value="" autofocus required>
    </label>
    <? if ($check_recording_privacy_agreement): ?>
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