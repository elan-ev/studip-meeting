<form method="post" action="<?= $controller->url_for('room/moderator/' . ($step + 1) . '/' . $moderator_invitations_link->hex . "/$cid")?>" class="default">
    <h1><?= $_('Sie wurden eingeladen, als Moderator beizutreten')?></h1>
    <? if ($step == 1): ?>
    <label>
        <?= $_('Geben Sie den Zugangscode des Meetings ein')?>
        <input type="text" name="password" value="<?= ($last_password) ? $last_password : '' ?>">
    </label>
    <? elseif ($step == 2): ?>
        <label>
            <?= $_('Geben Sie einen Namen ein')?>
            <input type="text" name="name" value="" placeholder="<?= htmlReady($moderator_invitations_link->default_name)?>">
        </label>
    <? endif; ?>
    <footer>
        <?= \Studip\Button::createAccept($controller->_(($step != 2) ? 'Weiter' : 'Meeting betreten'))?>
    </footer>
</form>