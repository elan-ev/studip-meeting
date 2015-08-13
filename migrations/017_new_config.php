<?php

require __DIR__.'/../vendor/autoload.php';

/**
 * Remove old config options and add a new one, holding all config data
 *
 * @author Till Glöggler <tgloeggl@uos.de>
 */

class NewConfig extends Migration {

    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return "remove old config options and add the new one";
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        try {
            Config::get()->create('VC_CONFIG');

            // TODO: migrate current settings

            Config::get()->delete('BBB_URL');
            Config::get()->delete('BBB_SALT');
            Config::get()->delete('DFN_VC_URL');
            Config::get()->delete('DFN_VC_LOGIN');
            Config::get()->delete('DFN_VC_PASSWORD');
            Config::get()->delete(\ElanEv\Driver\DriverFactory::DEFAULT_DRIVER_CONFIG_ID);
        } catch (InvalidArgumentException $ex) {

        }
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
    }
}