<div class="container" id="app">
    <h1 class="display-1 text-center">Starte Anwendung&hellip;</h1>
</div>

<script type="text/javascript">
    let API_URL  = '<?= PluginEngine::getURL('meetingplugin', [], 'api') ?>';
    let ICON_URL = '<?= Assets::url('images/icons/') ?>';
</script>

<% for(var i = 0; i < htmlWebpackPlugin.tags.bodyTags.length; i++) { %>
<? PageLayout::addScript($this->plugin->getPluginUrl() . '/static<%= htmlWebpackPlugin.tags.bodyTags[i].attributes.src %>'); ?>
<% } %>
