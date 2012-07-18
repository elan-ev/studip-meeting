<?php
$layout = $GLOBALS['template_factory']->open('layouts/base_without_infobox');
$this->set_layout($layout);
?>
<h3>BigBlueButton</h3>

<h4>Informationen</h4>
<div>
    BigBlueButton ist ein freies Webkonferenz Tool. <br />
    Eine Übersicht der Features 
    und weitere Hilfen zum Tool erhalten Sie 
    <a href="http://www.bigbluebutton.org/overview/" target="_blank">
        hier
    </a>.
</div>
<h4>Konferenzen</h4>
<?php if($params['meeting_running'] && $params['allow_join']): ?>
<div>
    Es wurde eine Webkonferenz für dieses Seminar gestrartet. <br />
    Um der Konferenz
    beizutreten klicken Sie 
    <a href="<?= PluginEngine::getLink("BBBPlugin/joinMeeting") ?>" target="_blank"> 
        hier
    </a>
    .
</div>

<?php elseif(!$params['meeting_running']): ?>
<div>
    Bisher wurde noch keine Webkonferenz gestartet.

    <?php if($params['perm'] == 'mod'): ?>
        <br />
        Klicken Sie 
        <a href="<?= PluginEngine::getLink("BBBPlugin/createMeeting") ?>" target="_blank">
            hier
        </a>
        um eine Konferenz zu starten.
    <?php endif; ?>
</div>
<?php endif; ?>
<a target="_blank" href="http://www.bigbluebutton.org/">
    <img width="952" height="544" src="<?= $params['img_path'] ?>bbb_overview.png" title="BigBlueButton Main Interface" class="aligncenter">
</a>

