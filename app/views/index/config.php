<form action="<?= PluginEngine::getLink($plugin, array(), 'index/config') ?>" method="post" class="default">
    <?= CSRFProtection::tokenTag() ?>
    <fieldset>
        <legend>
            <?= $_('Einstellungen') ?>
        </legend>
        <label>
            <?= $_('Reitername') ?>
            <input type="text" name="title" id="vc_config_title" value="<?= htmlReady($courseConfig->title) ?>" size="80" autofocus>
        </label>

    </fieldset>

    <footer data-dialog-button>
        <?= Studip\Button::createAccept($_('Speichern'), 'anlegen') ?>
        <?= Studip\LinkButton::createCancel($_('Abbrechen'), PluginEngine::getLink($plugin, array(), 'index')) ?>
    </footer>
</form>
