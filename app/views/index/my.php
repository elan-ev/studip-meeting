<?php
/** @var string $type */
/** @var \Semester[] $semesters */
/** @var \ElanEv\Model\MeetingCourse[] $meetings */
?>

<?= $this->render_partial('index/_confirm_delete') ?>

<?php if ($type === 'name'): ?>
    <?=$this->render_partial('index/_meetings', array('title' => _('Meine Meetings'), 'canModifyMeetings' => true, 'destination' => 'index/my/name', 'showCourse' => true)) ?>
<?php else: ?>
    <?php foreach ($semesters as $semester): ?>
        <?=$this->render_partial('index/_meetings', array('title' => $semester->name, 'canModifyMeetings' => true, 'meetings' => $meetings[$semester->id], 'destination' => 'index/my', 'showCourse' => true)) ?>
    <?php endforeach ?>
<?php endif ?>
