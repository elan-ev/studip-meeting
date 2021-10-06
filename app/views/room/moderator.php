<form method="post" action="<?= $controller->url_for('room/moderator/' . $moderator_invitations_link->hex . "/$cid")?>" class="default">
    <h1><?= $_('Sie wurden eingeladen, als Moderator beizutreten')?></h1>
    <label>
        <span class="required"><?= $_('Geben Sie einen Namen ein')?></span>
        <input type="text" name="name" value="" required>
    </label>
    <label>
        <span class="required"><?= $_('Zugangscode des Meetings')?></span>
        <input type="text" name="password" value="<?= ($last_password) ? $last_password : '' ?>" required>
    </label>
    <footer>
        <?= \Studip\Button::createAccept($controller->_('Meeting betreten'))?>
    </footer>
</form>