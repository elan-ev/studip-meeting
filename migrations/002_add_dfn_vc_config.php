<?php

/**
 * Migration that adds configuration options for the DFN LMSAPI driver.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class AddDfnVcConfig extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return 'Adds config options for the DFN LMSAPI driver';
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        try {
            Config::get()->create('DFN_VC_URL');
        } catch (InvalidArgumentException $e) {}

        try {
            Config::get()->create('DFN_VC_LOGIN');
        } catch (InvalidArgumentException $e) {}

        try {
            Config::get()->create('DFN_VC_PASSWORD');
        } catch (InvalidArgumentException $e) {}
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        Config::get()->delete('DFN_VC_URL');
        Config::get()->delete('DFN_VC_LOGIN');
        Config::get()->delete('DFN_VC_PASSWORD');
    }
}
