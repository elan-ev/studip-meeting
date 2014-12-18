<?php
/** @var VideoConferencePlugin $plugin */
/** @var Flexi_TemplateFactory $templateFactory */
/** @var bool $configured */
/** @var bool $confirmDeleteMeeting */
/** @var string[] $questionOptions */
/** @var bool $canModifyCourse */
/** @var ElanEv\Model\Meeting[] $meetings */
/** @var array $errors */
?>

<?php if (!$configured): ?>
    <?= MessageBox::info(_('Es wurden noch keine Konferenzverbindungen eingerichtet.')) ?>

    <? if ($GLOBALS['perm']->have_perm('root')) : ?>
        <form method="post" action="<?= PluginEngine::getLink($plugin, array(), 'index/saveConfig') ?>">
            URL des BBB-Servers:<br>
            <input type="text" name="bbb_url" size="50"><br><br>

            Api-Key (Salt):<br>
            <input type="text" name="bbb_salt" size="50"><br>

            <?= Studip\Button::createAccept(_('Konfiguration speichern')) ?>
        </form>
    <?php endif; ?>
<?php else: ?>
    <? if ($confirmDeleteMeeting): ?>
        <?= $templateFactory->render('shared/question', $questionOptions) ?>
    <? endif ?>

    <div>
        <table class="default collapsable tablesorter conference-meetings">
            <caption><?=_('Konferenzen')?></caption>
            <colgroup>
                <col>
                <col style="width: 100px;">
                <col style="width: 80px;">
            </colgroup>
            <thead>
            <tr>
                <th>Meeting</th>
                <?php if ($canModifyCourse): ?>
                    <th><?= _('Freigeben') ?></th>
                <?php endif; ?>
                <th><?=_('Aktion')?></th>
            </tr>
            </thead>

            <tbody>
                <?php foreach ($meetings as $meeting): ?>
                    <?php
                    $joinUrl = PluginEngine::getLink($plugin, array(), 'index/joinMeeting/'.$meeting->id);
                    $moderatorPermissionsUrl = PluginEngine::getLink($plugin, array(), 'index/moderator_permissions/'.$meeting->id);
                    $deleteUrl = PluginEngine::getLink($plugin, array('delete' => $meeting->id), 'index');
                    ?>
                    <tr>
                        <td class="meeting-name">
                            <img src="<?=$GLOBALS['ASSETS_URL']?>/images/icons/20/grey/info-circle.png" class="info">
                            <a href="<?=$joinUrl?>" title="<?=_('Meeting betreten')?>" target="_blank"><?=htmlReady($meeting->name)?></a>
                            <input type="text" name="name">
                            <img src="<?=$GLOBALS['ASSETS_URL']?>/images/icons/20/grey/accept.png" class="accept-button" title="<?=_('Änderungen speichern')?>">
                            <img src="<?=$GLOBALS['ASSETS_URL']?>/images/icons/20/grey/decline.png" class="decline-button" title="<?=_('Änderungen verwerfen')?>">
                            <img src="<?=$GLOBALS['ASSETS_URL']?>/images/ajax_indicator_small.gif" class="loading-indicator">

                            <ul class="info">
                                <?php if ($meeting->join_as_moderator): ?>
                                    <li><?=_('Alle Teilnehmenden haben Moderationsrechte.')?></li>
                                <?php else: ?>
                                    <li><?=_('Nur DozentInnen und TutorInnen haben Moderationsrechte.')?></li>
                                <?php endif; ?>

                                <?php if (count($meeting->getRecentJoins()) === 1): ?>
                                    <li><?=_('Eine Person hat das Meeting in den letzten 24 Stunden betreten')?>.</li>
                                <?php else: ?>
                                    <li><?=count($meeting->getRecentJoins()).' '._('Personen haben das Meeting in den letzten 24 Stunden betreten')?>.</li>
                                <?php endif; ?>

                                <?php if (count($meeting->getAllJoins()) === 1): ?>
                                    <li><?=_('Eine Person hat das Meeting insgesamt betreten')?>.</li>
                                <?php else: ?>
                                    <li><?=count($meeting->getAllJoins()).' '._('Personen haben das Meeting insgesamt betreten')?>.</li>
                                <?php endif; ?>
                            </ul>
                        </td>
                        <?php if ($canModifyCourse): ?>
                            <td><input type="checkbox"<?=$meeting->active ? ' checked="checked"' : ''?> data-meeting-enable-url="<?=PluginEngine::getLink($plugin, array(), 'index/enable/'.$meeting->id)?>" title="<?=$meeting->active ? _('Meeting für Studierende unsichtbar schalten') : _('Meeting für Studierende sichtbar schalten')?>"></td>
                        <?php endif; ?>
                        <td>
                            <?php if ($canModifyCourse): ?>
                                <a href="#" title="<?=_('Meeting umbenennen')?>" class="edit-meeting" data-meeting-rename-url="<?=PluginEngine::getLink($plugin, array(), 'index/rename/'.$meeting->id)?>"><img src="<?=$GLOBALS['ASSETS_URL']?>/images/icons/20/blue/edit.png"></a>
                                <?php if ($meeting->join_as_moderator): ?>
                                    <a href="<?=$moderatorPermissionsUrl?>" title="<?=_('Ändern zu: nur DozentInnen und TutorInnen haben Moderationsrechte')?>"><img src="<?=$plugin->getAssetsUrl()?>/images/moderator-enabled.png"></a>
                                <?php else: ?>
                                    <a href="<?=$moderatorPermissionsUrl?>" title="<?=_('Ändern zu: alle Teilnehmenden haben Moderationsrechte')?>"><img src="<?=$plugin->getAssetsUrl()?>/images/moderator-disabled.png"></a>
                                <?php endif; ?>
                                <a href="<?=$deleteUrl?>" title="<?=_('Meeting löschen')?>"><img src="<?=$GLOBALS['ASSETS_URL']?>/images/icons/20/blue/trash.png"></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>

            <?php if ($canModifyCourse): ?>
                <tfoot>
                    <tr>
                        <td colspan="<?=$canModifyCourse ? 3 : 2?>">
                            <form method="post" action="<?=PluginEngine::getURL($GLOBALS['plugin'], array(), 'index')?>" class="create-conference-meeting">
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
                        </td>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    </div>
<? endif ?>
