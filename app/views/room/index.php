<form method="post" action="<?= $controller->url_for('room/join_meeting/' . $invitations_link->hex)?>" class="default">
  <h1><?= $_('Sie wurden zur Teilnahme eingeladen')?></h1>
    <label>
        <?= $_('Geben Sie einen Namen ein')?>
        <input type="text" name="name" value="" placeholder="<?= htmlReady($invitations_link->default_name)?>">
    </label>
    <footer>
        <?= \Studip\Button::createAccept($controller->_('Meeting betreten'))?>
    </footer>
</form>