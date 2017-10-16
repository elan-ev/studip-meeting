<?php
/** @var MeetingPlugin $plugin */
/** @var Flexi_TemplateFactory $templateFactory */
/** @var ElanEv\Model\CourseConfig $courseConfig */
/** @var bool $saved */
?>

<?php if ($saved): ?>
    <?= $templateFactory->render('shared/message_box', array('class' => 'success', 'message' => _('Die Änderungen wurden gespeichert.'))) ?>
<?php endif ?>

<form action="<?= PluginEngine::getLink($plugin, array(), 'index/config') ?>" method="post" class="studip_form default">
    <?= CSRFProtection::tokenTag() ?>
    <fieldset style="padding-top: 0;">
        <label for="vc_config_title"><?= _('Reitername:') ?></label>
        <input type="text" name="title" id="vc_config_title" value="<?= htmlReady($courseConfig->title) ?>" size="80" autofocus>

        <label for="vc_config_introduction"><?= _('Einleitungstext:') ?></label>
        <textarea name="introduction" id="vc_config_introduction" cols="80" rows="10" class="add_toolbar"><?= htmlReady($courseConfig->introduction) ?></textarea>
    </fieldset>
    <div data-dialog-button>
        <?= Studip\Button::createAccept(_('Speichern'), 'anlegen') ?>
        <?= Studip\LinkButton::createCancel(_('Abbrechen'), PluginEngine::getLink($plugin, array(), 'index')) ?>
    </div>
</form>
