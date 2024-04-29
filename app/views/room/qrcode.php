<form method="post" action="<?= $controller->url_for('room/qrcode/' . $qr_code_token->hex . "/$cid")?>" class="default">
    <h1><?= $_('Teilnahme mit QR-Code') ?></h1>
    <label>
        <span class="required"><?= $_('Zugangscode')?></span>
        <input type="text" name="token" value="<?= !empty($last_token) ? $last_token : '' ?>" required>
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
