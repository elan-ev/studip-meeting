<form method="post" action="<?= $controller->url_for('room/join_public/' . $room_id . "/$cid")?>" class="default">
    <label>
        <?= $_('Geben Sie einen Namen ein') ?>
        <input type="text" name="name" value="" autofocus required>
    </label>
    <footer>
        <?= \Studip\Button::createAccept($controller->_('Meeting betreten'))?>
    </footer>
</form>