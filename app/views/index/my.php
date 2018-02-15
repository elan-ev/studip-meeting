<?php
/** @var string $type */
/** @var \Semester[] $semesters */
/** @var \ElanEv\Model\MeetingCourse[] $meetings */
?>

<?= $this->render_partial('index/_confirm_delete') ?>

<? if (empty($meetings)): ?>
    <?= MessageBox::info($_('Es sind keine Meetings vorhanden. Meetings können nur innerhalb einer Veranstaltung über das "+"-Icon aktiviert und anschließend über den Reiter "Meetings" verwaltet werden.')) ?>
<? endif; ?>

<? if ($type === 'name'): ?>
    <?=$this->render_partial('index/_meetings', array('title' => $_('Meine Meetings'), 'canModifyMeetings' => true, 'destination' => 'index/my/name', 'showCourse' => true)) ?>
<? else: ?>
    <? foreach ($semesters as $semester): ?>
        <?=$this->render_partial('index/_meetings', array('title' => $semester->name, 'canModifyMeetings' => true, 'meetings' => $meetings[$semester->id], 'destination' => 'index/my', 'showCourse' => true)) ?>
    <? endforeach ?>
<? endif ?>
