<?php
/** @var VideoConferencePlugin $plugin */
/** @var ElanEv\Model\CourseConfig $courseConfig */
/** @var bool $configured */
/** @var bool $canModifyCourse */
?>

<?php if (!$configured): ?>
    <?= MessageBox::info(_('Es wurden noch keine Meetings eingerichtet.')) ?>

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
    <?= $this->render_partial('index/_confirm_delete') ?>

    <?php if (trim(strip_tags($courseConfig->introduction))): ?>
        <div class="vc_introduction"><?= formatReady($courseConfig->introduction) ?></div>
    <?php endif ?>

    <div>
        <?= $this->render_partial('index/_meetings', array('title' => $courseConfig->title, 'canModifyMeetings' => $canModifyCourse, 'showUser' => $canModifyCourse, 'showCreateForm' => true)) ?>
    </div>
<?php endif ?>
