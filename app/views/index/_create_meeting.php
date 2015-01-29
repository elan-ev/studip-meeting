<?php
/** @var bool $canModifyMeetings */
/** @var ElanEv\Model\MeetingCourse[] $userMeetings */
/** @var array $errors */
/** @var int $colspan */
?>

<?php if ($canModifyMeetings): ?>
    <tfoot>
    <tr>
        <td colspan="<?=$colspan?>">
            <form method="post" action="<?=PluginEngine::getURL($GLOBALS['plugin'], array(), 'index')?>" class="create-conference-meeting">
                <input type="hidden" name="action" value="create">
                <fieldset name="Meeting erstellen">
                    <?php if (count($errors) > 0): ?>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlReady($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <input type="text" name="name" placeholder="">
                    <input type="submit" value="<?= _('Meeting erstellen') ?>">
                </fieldset>
            </form>

            <p><?= _('oder') ?></p>

            <form method="post" action="<?=PluginEngine::getURL($GLOBALS['plugin'], array(), 'index')?>" class="create-conference-meeting">
                <input type="hidden" name="action" value="link">
                <fieldset name="Meeting erstellen">
                    <select name="meeting_id" size="1">
                        <option><?= _('zu verlinkendes Meeting auswählen') ?></option>
                        <?php foreach ($userMeetings as $meetingCourse): ?>
                            <option value="<?=$meetingCourse->meeting->id ?>">
                                <?=htmlReady($meetingCourse->meeting->name) ?> (<?=htmlReady($meetingCourse->course->name)?>, <?=htmlReady($meetingCourse->course->start_semester->name)?>)
                            </option>
                        <?php endforeach ?>
                    </select>
                    <input type="submit" value="<?= _('Meeting verlinken') ?>">
                </fieldset>
            </form>
        </td>
    </tr>
    </tfoot>
<?php endif ?>
