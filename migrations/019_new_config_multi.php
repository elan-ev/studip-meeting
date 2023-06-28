<?php

require __DIR__.'/../bootstrap.php';

/**
 * Remove old config options and add a new one, holding all config data
 *
 * @author Till Glöggler <tgloeggl@uos.de>
 */

class NewConfigMulti extends Migration {

    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return "make the config options multi format";
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        try {
            //migrate current settings
            if (\Config::get()->VC_CONFIG) {
                //Get current config
                $current_driver = \Config::get()->getValue('VC_DRIVER');

                $current_config = \Config::get()->getValue('VC_CONFIG');
                $current_config = json_decode($current_config, true);


                $config_bigbluebutton_options = [];
                $config_dfnvc_options = [];

                if ($current_config) {
                    $config_bigbluebutton_options['enable'] = $current_config['BigBlueButton']['enable'];
                    $config_bigbluebutton_options['display_name'] = $current_config['BigBlueButton']['display_name'];
                    $config_bigbluebutton_options['servers'] = [
                        [
                            'url'          => $current_config['BigBlueButton']['url'],
                            'api-key'      => $current_config['BigBlueButton']['api-key']
                        ]
                    ];
                    $config_dfnvc_options['enable'] = $current_config['DfnVc']['enable'];
                    $config_dfnvc_options['display_name'] = $current_config['DfnVc']['display_name'];
                    $config_dfnvc_options['servers'] = [
                        [
                            'url'          => $current_config['DfnVc']['url'],
                            'login'        => $current_config['DfnVc']['login'],
                            'password'     => $current_config['DfnVc']['password']
                        ]
                    ];
                } else {
                    $config_bigbluebutton_options['enable'] = ($current_driver == 'bigbluebutton') ? 1 : 0;
                    $config_bigbluebutton_options['display_name'] = 'BigBlueButton';
                    $config_bigbluebutton_options['servers'] = [];
                    $config_dfnvc_options['enable'] = ($current_driver == 'dfnvc') ? 1 : 0;
                    $config_dfnvc_options['display_name'] = 'Adobe Connect VC';
                    $config_dfnvc_options['servers'] = [];
                }

                $config = [
                    'BigBlueButton' => $config_bigbluebutton_options,
                    'DfnVc' => $config_dfnvc_options
                ];

                \Config::get()->store('VC_CONFIG', json_encode($config));
            }
        } catch (InvalidArgumentException $ex) {

        }
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        $new_config = \Config::get()->getValue('VC_CONFIG');
        $new_config = json_decode($new_config, true);

        $old_config = [];
        foreach ($new_config as $driver_name => $value) {
            $cnf = [];
            $cnf['enable'] = $value['enable'];
            $cnf['display_name'] = $value['display_name'];
            if (count($value['servers']) > 0) {
                foreach ($value['servers'][0] as $key => $value) {
                    $cnf[$key] = $value;
                }
            }
            $old_config[$driver_name] = $cnf;
        }
        \Config::get()->store('VC_CONFIG', json_encode($old_config));
    }
}
