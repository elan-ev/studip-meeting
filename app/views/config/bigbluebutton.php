<form method="post" action="<?= PluginEngine::getLink($plugin, array(), 'index/saveConfig') ?>">
    URL des BBB-Servers:<br>
    <input type="text" name="config[BBB_URL]" size="255"><br><br>

    Api-Key (Salt):<br>
    <input type="text" name="config[BBB_SALT]" size="255"><br>

    <?= Studip\Button::createAccept(_('Konfiguration speichern')) ?>
</form>
