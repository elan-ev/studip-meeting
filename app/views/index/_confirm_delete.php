<?php
/** @var Flexi_TemplateFactory $templateFactory */
/** @var bool $confirmDeleteMeeting */
/** @var array $questionOptions */
?>

<?php if ($confirmDeleteMeeting): ?>
    <?php if (isset($questionOptions['deleteMeetings']) && count($questionOptions['deleteMeetings']) > 0): ?>
        <form action="<?=$questionOptions['destination']?>" method="post">
            <input type="hidden" name="action" value="multi-delete">
            <?php foreach ($questionOptions['deleteMeetings'] as $meetingCourse): ?>
                <input type="hidden" name="meeting_ids[]" value="<?=$meetingCourse->meeting->id?>-<?=$meetingCourse->course->id?>">
            <?php endforeach ?>
            <div class="modaloverlay">
                <div class="messagebox">
                    <div class="content">
                        <?= formatReady($questionOptions['question']) ?>
                    </div>
                    <ul>
                        <?php foreach ($questionOptions['deleteMeetings'] as $meetingCourse): ?>
                            <li><?=htmlReady($meetingCourse->meeting->name)?> (<?=htmlReady($meetingCourse->course->name)?>, <?=htmlReady($meetingCourse->course->start_semester->name)?>)</li>
                        <?php endforeach ?>
                    </ul>
                    <div class="buttons">
                        <?= Studip\Button::createAccept($_('JA!'), 'confirm') ?>
                        <?= Studip\Button::createCancel($_('NEIN!'), 'cancel') ?>
                    </div>
                </div>
            </div>
        </form>
    <?php elseif (!isset($questionOptions['deleteMeetings'])): ?>
        <div class="modaloverlay">
            <div class="messagebox">
                <div class="content">
                    <?= formatReady($questionOptions['question']) ?>
                </div>
                <div class="buttons">
                    <?= Studip\LinkButton::createAccept($_('JA!'), $questionOptions['approvalLink']) ?>
                    <?= Studip\LinkButton::createCancel($_('NEIN!'), $questionOptions['disapprovalLink']) ?>
                </div>
            </div>
        </div>
    <?php endif ?>
<?php endif ?>
