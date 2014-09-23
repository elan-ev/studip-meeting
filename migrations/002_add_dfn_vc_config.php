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
        Config::get()->create('DFN_VC_URL');
        Config::get()->create('DFN_VC_LOGIN');
        Config::get()->create('DFN_VC_PASSWORD');
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
