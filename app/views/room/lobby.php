<div class="container" id="app">
    <h1 class="display-1 text-center">Starte Anwendung&hellip;</h1>
</div>

<script type="text/javascript">
    let API_URL  = '<?= PluginEngine::getURL('meetingplugin', [], 'api') ?>';
    let ICON_URL = '<?= Assets::url('images/icons/') ?>';
    let LOADING_ICON_URL = '<?= Assets::url('images/ajax-indicator-black.svg') ?>';
</script>


<? PageLayout::addScript($this->plugin->getPluginUrl() . '/static/main.a7c2bf2900ec0ba62334.js'); ?>

