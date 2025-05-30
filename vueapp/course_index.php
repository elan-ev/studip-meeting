<div class="container" id="app">
    <h1 class="display-1 text-center">Starte Anwendung&hellip;</h1>
</div>

<script type="text/javascript">
    let API_URL  = '<?= PluginEngine::getURL('meetingplugin', [], $is_public ? 'public' : 'api', true) ?>';
    let CID      = '<?= $cid ?>';
    let ICON_URL = '<?= Assets::url('images/icons/') ?>';
    let PLUGIN_ASSET_URL =  '<?= $controller->plugin->getAssetsUrl() ?>';
    <?= isset($studip_version) ? "let STUDIP_VERSION = $studip_version" : '' ?>;
</script>

<? PageLayout::addScript($controller->plugin->getPluginUrl() . '/static<%= htmlWebpackPlugin.files.js[0] %>'); ?>
