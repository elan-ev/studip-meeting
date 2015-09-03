<?php
/** @var MeetingPlugin $plugin */
/** @var ElanEv\Model\CourseConfig $courseConfig */
/** @var bool $configured */
/** @var bool $canModifyCourse */
?>

<?php if (!$configured): ?>
    <?= MessageBox::info(_('Es wurde noch kein Videokonferenzsystem konfiguriert. '
            . 'Bitte wenden Sie sich an eine/n Systemadministrator/in!')) ?>
<?php else: ?>
    <?= $this->render_partial('index/_confirm_delete') ?>

    <?= $this->render_partial('index/_messages', compact('messages')) ?>
    <?php if (trim(strip_tags($courseConfig->introduction))): ?>
        <div class="vc_introduction"><?= formatReady($courseConfig->introduction) ?></div>
    <?php endif ?>

    <div>
        <?= $this->render_partial('index/_meetings', array('title' => $courseConfig->title, 'canModifyMeetings' => $canModifyCourse, 'showUser' => $canModifyCourse, 'showCreateForm' => true)) ?>
    </div>
<?php endif ?>
