<form method="post" action="<?= $controller->url_for('room/qrcode/' . $qr_code_token->hex . "/$cid")?>" class="default">
    <label>
        <span class="required"><?= $_('Bitte geben Sie den Zugangscode des Meetings')?></span>
        <input type="text" name="token" value="<?= ($last_token) ? $last_token : '' ?>" required>
    </label>
    <footer>
        <?= \Studip\Button::createAccept($controller->_('Meeting betreten'))?>
    </footer>
</form>