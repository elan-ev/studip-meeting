<form class="default" method="post" action="<?= $controller->link_for($is_new ? 'index/add_intro' : "index/edit_intro/{$index}") ?>">
    <?= CSRFProtection::tokenTag() ?>
    <fieldset>
        <label>
            <?= $_('Titel') ?>
            <?= tooltipIcon($_('Wenn leer, wird der standardmäßige Einleitung als Titel angezeigt.')) ?>
            <input type="text" name="title" value="<?= isset($title) ? htmlReady($title) : null ?>" placeholder="<?= !isset($title) ? $_('Einleitung') : '' ?>" maxlength="254" size="80" autofocus>
        </label>

        <label>
            <span class="required"><?= $_('Text') ?></span>
            <textarea name="text" cols="80" rows="10" class="studip_wysiwyg wysiwyg"><?= isset($text) ? wysiwygReady($text) : null ?></textarea>
        </label>
    </fieldset>
    <footer data-dialog-button>
        <? if ($is_new): ?>
            <?= Studip\Button::createAccept($_('Hinzufügen'), 'store') ?>
        <? else: ?>
            <?= Studip\Button::createAccept($_('Speichern'), 'edit') ?>
        <? endif; ?>
        <?= Studip\LinkButton::createCancel($_('Abbrechen'), $controller->link_for('index/intros')) ?>
    </footer>
</form>
