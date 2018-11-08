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
            <form method="post" action="<?=PluginEngine::getURL($GLOBALS['plugin'], array(), 'index/create')?>" class="create-conference-meeting">
                <input type="hidden" name="action" value="create">
                <fieldset name="Meeting erstellen">
                    <?php if (count($errors) > 0): ?>
                        <?= MessageBox::error(implode('<br>', $errors)); ?>
                    <?php endif; ?>
                    <? if (sizeof($driver_config) == 1) : ?>
                        <input type="hidden" name="driver" value="<?= key($driver_config) ?>">
                    <? else : ?>
                        <? $first = key($driver_config); ?>
                        <? foreach ($driver_config as $driver => $config) : ?>
                            <label>
                                <input type="radio" name="driver" value="<?= $driver ?>" <?= ($driver === $first) ? 'checked="checked"' : '' ?>>
                                <?= htmlReady($config['display_name']) ?>
                            </label>
                        <? endforeach ?>
                        <br>
                    <? endif ?>

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
