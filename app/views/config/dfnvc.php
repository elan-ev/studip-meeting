<form method="post" action="<?= PluginEngine::getLink($plugin, array(), 'index/saveConfig') ?>">
    API-Endpoint:<br>
    <input type="text" name="config[DFN_VC_URL]" size="255"><br><br>

    Funktionskennung:<br>
    <input type="text" name="config[DFN_VC_LOGIN]" size="255"><br>

    Passwort:<br>
    <input type="text" name="config[DFN_VC_PASSWORD]" size="255"><br>

    <?= Studip\Button::createAccept(_('Konfiguration speichern')) ?>
</form>
