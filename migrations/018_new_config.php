<?php

require __DIR__.'/../bootstrap.php';

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
        return "migrate and remove old config options and add the new one";
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        try {
            Config::get()->create('VC_CONFIG', array(
                'value' => '',
                'type' => 'string',
                'range' => 'global',
                'section' => 'meetings',
                'description' => _('Konfiguration des Meetings-Plugins im JSON Format')
            ));

            $config = array(
                'BigBlueButton' => array(
                    'enable'       => 0,
                    'display_name' => 'BigBlueButton',
                    'url'          => '',
                    'api-key'      => ''
                ),
                'DfnVc' => array(
                    'enable'       => 0,
                    'display_name' => 'Adobe Connect VC',
                    'url'          => '',
                    'login'        => '',
                    'password'     => ''
                )
            );
            //migrate current settings
            if (Config::get()->VC_DRIVER) {
                $current_driver = Config::get()->getValue('VC_DRIVER');

                $config['BigBlueButton']['enable'] = (trim(strtolower($current_driver)) == 'bigbluebutton') ? 1 : 0;
                $config['BigBlueButton']['url'] = (Config::get()->BBB_URL) ? Config::get()->getValue('BBB_URL') : '';
                $config['BigBlueButton']['api-key'] = (Config::get()->BBB_SALT) ? Config::get()->getValue('BBB_SALT') : '';

                $config['DfnVc']['enable'] = (trim(strtolower($current_driver)) == 'dfnvc') ? 1 : 0;
                $config['DfnVc']['url'] = (Config::get()->DFN_VC_URL) ? Config::get()->getValue('DFN_VC_URL') : '';
                $config['DfnVc']['login'] = (Config::get()->DFN_VC_LOGIN) ? Config::get()->getValue('DFN_VC_LOGIN') : '';
                $config['DfnVc']['password'] = (Config::get()->DFN_VC_PASSWORD) ? Config::get()->getValue('DFN_VC_PASSWORD') : '';

                Config::get()->delete('BBB_URL');
                Config::get()->delete('BBB_SALT');
                Config::get()->delete('DFN_VC_URL');
                Config::get()->delete('DFN_VC_LOGIN');
                Config::get()->delete('DFN_VC_PASSWORD');
                Config::get()->delete('VC_DRIVER');
                Config::get()->delete(\ElanEv\Driver\DriverFactory::DEFAULT_DRIVER_CONFIG_ID);
            }

            \Config::get()->store('VC_CONFIG', json_encode($config));

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
