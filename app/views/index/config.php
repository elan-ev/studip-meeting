<?php
/** @var MeetingPlugin $plugin */
/** @var Flexi_TemplateFactory $templateFactory */
/** @var ElanEv\Model\CourseConfig $courseConfig */
/** @var bool $saved */
?>

<? if ($saved): ?>
    <?= $templateFactory->render('shared/message_box', array('class' => 'success', 'message' => $_('Die Änderungen wurden gespeichert.'))) ?>
<? endif ?>

<form action="<?= PluginEngine::getLink($plugin, array(), 'index/config') ?>" method="post" class="studip_form default">
    <?= CSRFProtection::tokenTag() ?>
    <fieldset style="padding-top: 0;">
        <legend>
            Einstellungen
        </legend>
        <label>
            <?= $_('Reitername') ?>
            <input type="text" name="title" id="vc_config_title" value="<?= htmlReady($courseConfig->title) ?>" size="80" autofocus>
        </label>


        <label>
            <?= $_('Einleitungstext') ?>
            <textarea name="introduction" id="vc_config_introduction" cols="80" rows="10" class="add_toolbar"><?= htmlReady($courseConfig->introduction) ?></textarea>
        </label>
    </fieldset>

    <footer data-dialog-button>
        <?= Studip\Button::createAccept($_('Speichern'), 'anlegen') ?>
        <?= Studip\LinkButton::createCancel($_('Abbrechen'), PluginEngine::getLink($plugin, array(), 'index')) ?>
    </footer>
</form>
