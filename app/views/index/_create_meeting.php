<?php
/** @var bool $canModifyMeetings */
/** @var ElanEv\Model\MeetingCourse[] $userMeetings */
/** @var array $errors */
/** @var int $colspan */
?>

<? if ($canModifyMeetings): ?>
<form method="post" action="<?=PluginEngine::getURL($GLOBALS['plugin'], array(), 'index')?>" class="default">
    <input type="hidden" name="action" value="create">

    <fieldset>
        <legend>
            <?= $_('Meeting erstellen') ?>
        </legend>

        <? if (count($errors) > 0): ?>
            <ul>
                <? foreach ($errors as $error): ?>
                    <li><? echo htmlReady($error); ?></li>
                <? endforeach; ?>
            </ul>
        <? endif; ?>

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
        <? endif ?>

        <label>
            <?= $_('Name') ?>
            <input type="text" name="name" placeholder="<?= $_('Name des Meetings') ?>">
        </label>
    </fieldset>

    <footer>
        <?= Studip\Button::createAccept($_('Meeting erstellen')) ?>
    </footer>
</form>
<br>
<form method="post" action="<?=PluginEngine::getURL($GLOBALS['plugin'], array(), 'index')?>" class="default">
    <input type="hidden" name="action" value="link">
    <fieldset>
        <legend>
            <?= $_('Meeting verlinken') ?>
        </legend>

        <label>
            <?= $_('Meeting auswählen') ?>
            <select name="meeting_id" size="1">
                <option><?= $_('zu verlinkendes Meeting auswählen') ?></option>
                <? foreach ($userMeetings as $meetingCourse): ?>
                    <option value="<?=$meetingCourse->meeting->id ?>">
                        <?=htmlReady($meetingCourse->meeting->name) ?> (<?=htmlReady($meetingCourse->course->name)?>, <?=htmlReady($meetingCourse->course->start_semester->name)?>)
                    </option>
                <? endforeach ?>
            </select>
        </label>
    </fieldset>

    <footer>
        <?= Studip\Button::createAccept($_('Meeting verlinken')) ?>
    </footer>
</form>
<? endif ?>
