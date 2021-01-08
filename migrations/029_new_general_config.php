<?php

require __DIR__.'/../vendor/autoload.php';

/**
 * Adding new config for general values used in Meeting Plugin
 *
 * @author Farbod Zamani <zamani@elan-ev.de>
 */

class NewGeneralConfig extends Migration {

    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return "Add new Config for general settings";
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        Config::get()->create('VC_GENERAL_CONFIG', array(
            'value' => '', 
            'type' => 'string',
            'range' => 'global',
            'section' => 'meetings',
            'description' => _('Allgemeine Konfiguration des Meetings-Plugins im JSON Format')
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        Config::get()->delete('VC_GENERAL_CONFIG');
    }
}
