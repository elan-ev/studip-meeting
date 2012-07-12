<?php
$layout = $GLOBALS['template_factory']->open('layouts/base');
$this->set_layout($layout);
?>

<a href="<?= PluginEngine::getLink("BBBPlugin/createMeeting") ?>"> Meeting erstellen </a>

<a href="<?= PluginEngine::getLink("BBBPlugin/joinMeeting") ?>"> Meeting beitreten </a>
