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
        return "migrate and remove old config options and add the new one";
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        try {
            $query = "REPLACE INTO `config`
               (`config_id`, `field`, `value`, `is_default`, `type`, `range`, `section`,
                `mkdate`, `chdate`, `description`)
             VALUES (MD5(:field), :field, :value, 1, :type, 'global', 'meetings',
                     UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :description)";

             $statement = DBManager::get()->prepare($query);

             $statement->execute(array(
                 ':field' => 'VC_CONFIG',
                 ':value' => '',
                 ':type'  => 'string',
                 ':description' => 'Konfiguration des Meetings-Plugins im JSON Format',
             ));


            //migrate current settings
            if (Config::get()->VC_DRIVER) {
                $current_driver = Config::get()->getValue('VC_DRIVER');

                $config = array(
                    'BigBlueButton' => array(
                        'enable'       => ($current_driver == 'bigbluebutton') ? 1 : 0,
                        'display_name' => 'BigBlueButton',
                        'url'          => Config::get()->getValue('BBB_URL'),
                        'api-key'      => Config::get()->getValue('BBB_SALT')
                    ),
                    'DfnVc' => array(
                        'enable' => ($current_driver == 'dfnvc') ? 1 : 0,
                        'display_name' => 'Adobe Connect VC',
                        'url'          => Config::get()->getValue('DFN_VC_URL'),
                        'login'        => Config::get()->getValue('DFN_VC_LOGIN'),
                        'password'     => Config::get()->getValue('DFN_VC_PASSWORD')
                    )
                );

                \Config::get()->store('VC_CONFIG', json_encode($config));

                Config::get()->delete('BBB_URL');
                Config::get()->delete('BBB_SALT');
                Config::get()->delete('DFN_VC_URL');
                Config::get()->delete('DFN_VC_LOGIN');
                Config::get()->delete('DFN_VC_PASSWORD');
                Config::get()->delete('VC_DRIVER');
                Config::get()->delete(\ElanEv\Driver\DriverFactory::DEFAULT_DRIVER_CONFIG_ID);
            }
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
