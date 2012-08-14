<?
$infobox_content[] = array(
    'kategorie' => _('Informationen'),
    'eintrag'   => array(
        array(
            'icon' => 'icons/16/black/info.png',
            'text' =>  'BigBlueButton ist ein freies Webkonferenz Tool.<br>'
                   .   'Eine Übersicht der Features und weitere Hilfen zum Tool erhalten Sie '
                   .   '<a href="http://www.bigbluebutton.org/overview/" target="_blank">bei BigBlueButton</a>.'
        )
    )
);

$infobox = array('picture' => '/../plugins_packages/elan-ev/BBBPlugin/images/bbb_overview.png', 'content' => $infobox_content);
?>
<div style="float: left">
    <h1>Konferenzen</h1>
    <? if( $param['meeting_running'] && $param['allow_join']): ?>
    <div>
        <?= Studip\LinkButton::create(_('Konferenz beitreten'), PluginEngine::getLink("BBBPlugin/index/joinMeeting"),
            array('target' => '_blank')) ?><br>
        <?= _('Es wurde eine Webkonferenz für dieses Seminar gestartet.') ?>
    </div>

    <? elseif(!$param['meeting_running']): ?>
    <div>
        

        <? if($param['perm'] == 'mod'): ?>
            <?= Studip\LinkButton::create(_('Neue Konferenz starten'), PluginEngine::getLink("BBBPlugin/index/createMeeting"),
                array('target' => '_blank')) ?>
            <br>
            <?= _('Bisher wurde noch keine Webkonferenz gestartet.') ?><br>
            <?= _('Wenn Sie einen neue Koferenz starten, können Teilnehmer/innen dieser Veranstaltung daran teilnehmen.') ?>
        <? else : ?>
            <?= _('Bisher wurde noch keine Webkonferenz gestartet.') ?>
        <? endif ?>
    </div>
    <? endif; ?>
</div>

<div style="float: right; display: none;">
    <a target="_blank" href="http://www.bigbluebutton.org/">
        <img width="300" src="<?= $picture_path ?>/bbb_overview.png" title="BigBlueButton Main Interface" class="aligncenter">
    </a>
</div>