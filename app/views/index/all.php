<?php
/** @var string $type */
/** @var \Semester[] $semesters */
/** @var \ElanEv\Model\MeetingCourse[] $meetings */
?>

<?= $this->render_partial('index/_confirm_delete') ?>

<? if ($type === 'name'): ?>
    <?=$this->render_partial('index/_meetings', array('title' => $_('Alle Meetings'), 'canModifyMeetings' => true, 'destination' => 'index/all', 'showCourse' => true)) ?>
<? else: ?>
    <? foreach ($semesters as $semester): ?>
        <?=$this->render_partial('index/_meetings', array('title' => $semester->name, 'canModifyMeetings' => true, 'meetings' => $meetings[$semester->id], 'destination' => 'index/all', 'showInstitute' => true, 'showCourse' => true, 'showUser' => true)) ?>
    <? endforeach ?>
<? endif ?>
