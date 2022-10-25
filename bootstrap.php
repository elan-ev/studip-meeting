<?php

require_once __DIR__ . '/app/controllers/meetings_controller.php';

StudipAutoloader::addAutoloadPath(__DIR__ . '/app/models', 'ElanEv\\Model');
StudipAutoloader::addAutoloadPath(__DIR__, 'ElanEv');
StudipAutoloader::addAutoloadPath(__DIR__ . '/lib', 'Meetings');
