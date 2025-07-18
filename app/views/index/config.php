<form action="<?= PluginEngine::getLink($controller->plugin, array(), 'index/config') ?>" method="post" class="default">
    <?= CSRFProtection::tokenTag() ?>
    <fieldset>
        <legend>
            <?= $_('Einstellungen') ?>
        </legend>
        <label>
            <?= $_('Name des Werkzeugs') ?>
            <input type="text" name="title" id="vc_config_title" value="<?= htmlReady($courseConfig->title) ?>" size="80" autofocus>
        </label>

    </fieldset>

    <footer data-dialog-button>
        <?= Studip\Button::createAccept($_('Speichern'), 'anlegen') ?>
        <?= Studip\LinkButton::createCancel($_('Abbrechen'), PluginEngine::getLink($controller->plugin, array(), 'index')) ?>
    </footer>
</form>
