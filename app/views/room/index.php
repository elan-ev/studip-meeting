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
    <footer>
        <?= \Studip\Button::createAccept($controller->_('Meeting betreten'))?>
    </footer>
</form>