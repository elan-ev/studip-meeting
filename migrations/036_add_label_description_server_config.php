<?php

require __DIR__.'/../vendor/autoload.php';

class AddLabelDescriptionServerConfig extends Migration
{

    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return "add label and description params into server configs";
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        if (Config::get()->VC_CONFIG) {
            $current_config = Config::get()->getValue('VC_CONFIG');
            $current_config = json_decode($current_config, true);
            if ($current_config) {
                foreach ($current_config as $driver_name => $driver_config) {
                    if (isset($driver_config['servers']) && count($driver_config['servers'])) {
                        foreach ($driver_config['servers'] as $server_index => $server_config) {
                            if (!isset($server_config['label'])) {
                                $server_num = $server_index + 1;
                                $current_config[$driver_name]['servers'][$server_index]['label'] = "Server {$server_num}";
                            }
                            if (!isset($server_config['description'])) {
                                $current_config[$driver_name]['servers'][$server_index]['description'] = "";
                            }
                        }
                    }
                }
                $encoded_json = json_encode($current_config);
                if ($encoded_json) {
                    Config::get()->store('VC_CONFIG', json_encode($current_config));
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        if (Config::get()->VC_CONFIG) {
            $current_config = Config::get()->getValue('VC_CONFIG');
            $current_config = json_decode($current_config, true);
            if ($current_config) {
                foreach ($current_config as $driver_name => $driver_config) {
                    if (isset($driver_config['servers']) && count($driver_config['servers'])) {
                        foreach ($driver_config['servers'] as $server_index => $server_config) {
                            if (isset($server_config['label'])) {
                                unset($current_config[$driver_name]['servers'][$server_index]['label']);
                            }
                            if (isset($server_config['description'])) {
                                unset($current_config[$driver_name]['servers'][$server_index]['description']);
                            }
                        }
                    }
                }
                $encoded_json = json_encode($current_config);
                if ($encoded_json) {
                    Config::get()->store('VC_CONFIG', json_encode($current_config));
                }
            }
        }
    }
}