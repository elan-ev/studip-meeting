<?php
/** @var BBBPlugin $plugin */
/** @var bool $configured */
/** @var bool $canModifyCourse */
/** @var ElanEv\Model\Meeting[] $meetings */
/** @var array $errors */
?>

<?php if (!$configured): ?>
    <?= MessageBox::info(_('Es wurden noch keine Konferenzverbindungen eingerichtet.')) ?>

    <? if ($GLOBALS['perm']->have_perm('root')) : ?>
        <form method="post" action="<?= PluginEngine::getLink("BBBPlugin/index/saveConfig") ?>">
            URL des BBB-Servers:<br>
            <input type="text" name="bbb_url" size="50"><br><br>

            Api-Key (Salt):<br>
            <input type="text" name="bbb_salt" size="50"><br>

            <?= Studip\Button::createAccept(_('Konfiguration speichern')) ?>
        </form>
    <?php endif; ?>
<?php else: ?>
    <div>
        <h1>Konferenzen</h1>

        <table class="default collapsable tablesorter conference-meetings">
            <thead>
            <tr>
                <th>Meeting</th>
                <?php if ($canModifyCourse): ?>
                    <th><?= _('Freigeben') ?></th>
                <?php endif; ?>
                <th><?=_('Aktion')?></th>
            </tr>
            </thead>
        <?php foreach ($meetings as $meeting): ?>
            <?php
            $joinUrl = PluginEngine::getLink($plugin, array(), 'index/joinMeeting/'.$meeting->id);
            $deleteUrl = PluginEngine::getLink($plugin, array(), 'index/delete/'.$meeting->id);
            ?>
            <tr>
                <td class="meeting-name">
                    <img src="<?=$GLOBALS['ASSETS_URL']?>/images/icons/16/black/info-circle.png" class="info">
                    <a href="<?=$joinUrl?>" title="<?=_('Meeting betreten')?>" target="_blank"><?=htmlReady($meeting->name)?></a>
                    <input type="text" name="name">
                    <img src="<?=$GLOBALS['ASSETS_URL']?>/images/icons/16/grey/accept.png" class="accept-button" title="<?=_('Änderungen speichern')?>">
                    <img src="<?=$GLOBALS['ASSETS_URL']?>/images/icons/16/grey/decline.png" class="decline-button" title="<?=_('Änderungen verwerfen')?>">
                    <img src="<?=$GLOBALS['ASSETS_URL']?>/images/ajax_indicator_small.gif" class="loading-indicator">

                    <div class="info">
                        <?php if (count($meeting->getRecentJoins()) === 1): ?>
                            <?=_('Eine Person hat das Meeting in den letzten 24 Stunden betreten')?>.
                        <?php else: ?>
                            <?=count($meeting->getRecentJoins()).' '._('Personen haben das Meeting in den letzten 24 Stunden betreten')?>.
                        <?php endif; ?>
                        <br>
                        <?php if (count($meeting->getAllJoins()) === 1): ?>
                            <?=_('Eine Person hat das Meeting insgesamt betreten')?>.
                        <?php else: ?>
                            <?=count($meeting->getAllJoins()).' '._('Personen haben das Meeting insgesamt betreten')?>.
                        <?php endif; ?>
                    </div>
                </td>
                <?php if ($canModifyCourse): ?>
                    <td><input type="checkbox"<?=$meeting->active ? ' checked="checked"' : ''?> data-meeting-enable-url="<?=PluginEngine::getLink("BBBPlugin/index/enable/".$meeting->id)?>"></td>
                <?php endif; ?>
                <td>
                    <?php if ($canModifyCourse): ?>
                        <a href="#" title="<?=_('Meeting umbenennen')?>" class="edit-meeting" data-meeting-rename-url="<?=PluginEngine::getLink("BBBPlugin/index/rename/".$meeting->id)?>"><img src="<?=$GLOBALS['ASSETS_URL']?>/images/icons/16/blue/edit.png"></a>
                        <a href="<?=$deleteUrl?>" title="<?=_('Meeting löschen')?>"><img src="<?=$GLOBALS['ASSETS_URL']?>/images/icons/16/blue/trash.png"></a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <?php if ($canModifyCourse): ?>
        <form method="post" action="<?=PluginEngine::getURL($GLOBALS['plugin'], array(), 'index')?>">
            <fieldset name="Meeting erstellen">
                <?php if (count($errors) > 0): ?>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlReady($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <input type="text" name="name" placeholder="">
                <input type="submit" value="Meeting erstellen">
            </fieldset>
        </form>
        <?php endif; ?>
    </div>
<? endif ?>