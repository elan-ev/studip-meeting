<form method="post" action="<?= $controller->url_for('room/moderator/' . $moderator_invitations_link->hex . "/$cid")?>" class="default">
    <h1><?= $_('Sie wurden eingeladen, als Moderator beizutreten')?></h1>
    <label>
        <span class="required"><?= $_('Geben Sie einen Namen ein')?></span>
        <input type="text" name="name" value="<?= ($last_moderator_name) ? $last_moderator_name : '' ?>" required>
    </label>
    <label>
        <span class="required"><?= $_('Zugangscode des Meetings')?></span>
        <input type="text" name="password" value="<?= ($last_password) ? $last_password : '' ?>" required>
    </label>
    <? if ($check_recording_privacy_agreement): ?>
        <label class="col-4" style="word-break: break-word !important;">
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