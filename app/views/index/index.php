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

if ($noconfig) : ?>
    <?= MessageBox::info(_('Der Verbindung zum BigBlueButton-Server wurde noch nicht konfiguriert.')) ?>
    <? if ($GLOBALS['perm']->have_perm('root')) : ?>
    <form method="post" action="<?= PluginEngine::getLink("BBBPlugin/index/saveConfig") ?>">
        URL des BBB-Servers:<br>
        <input type="text" name="bbb_url" size="50"><br><br>
        
        Api-Key (Salt):<br>
        <input type="text" name="bbb_salt" size="50"><br>
        
        <?= Studip\Button::createAccept(_('Konfiguration speichern')) ?>
    </form>
<? endif ?>
<? else : ?>
<div>
    <h1>Konferenzen</h1>

    <table width="100%">
        <thead>
        <tr>
            <th>Meeting</th>
            <th>Server
            </th>
            <th></th>
        </tr>
        </thead>
    <?php
    $meetings = \ElanEv\Model\Meeting::findByCourseId($meetingId);

    foreach ($meetings as $meeting):
        ?>
        <tr>
            <td><?=htmlReady($meeting->name)?></td>
            <td>DFN Adobe Connect</td>
            <td>
                <?php
                echo Studip\LinkButton::create(
                    _('Konferenz beitreten'),
                    PluginEngine::getLink("BBBPlugin/index/joinMeeting/".$meeting->id),
                    array('target' => '_blank')
                );
                ?>
            </td>
        </tr>
        <?php
    endforeach;
    ?>
    </table>

    <? if( $meeting_running && $allow_join): ?>
    <div>
        <?= Studip\LinkButton::create(_('Konferenz beitreten'), PluginEngine::getLink("BBBPlugin/index/joinMeeting"),
            array('target' => '_blank')) ?><br>
        <?= _('Es wurde eine Webkonferenz für dieses Seminar gestartet.') ?>
    </div>

    <? elseif(!$meeting_running): ?>
    <div>
        

        <? if($perm == 'mod'): ?>
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
<? endif ?>