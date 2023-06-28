<?php

require __DIR__.'/../bootstrap.php';

class AddPreuploadRemoveFoldersParamConfig extends Migration
{

    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return "remove and replace folder param with preupload param in the config";
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        if (Config::get()->VC_CONFIG) {
            $current_config = Config::get()->getValue('VC_CONFIG');
            $current_config = json_decode($current_config, true);
            if ($current_config && isset($current_config['BigBlueButton'])) {
                $value = "1";
                //remove folders from features
                if (isset($current_config['BigBlueButton']['features']) && isset($current_config['BigBlueButton']['features']['folders'])) {
                    $value = $current_config['BigBlueButton']['features']['folders'] ? "1" : "0";
                    unset($current_config['BigBlueButton']['features']['folders']);
                }
                //add preupload param
                $preupload_array = ["preupload" => $value];
                $current_config['BigBlueButton'] = array_merge($current_config['BigBlueButton'], $preupload_array);
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
            $current_config = \Config::get()->getValue('VC_CONFIG');
            $current_config = json_decode($current_config, true);
            if ($current_config && isset($current_config['BigBlueButton']) && isset($current_config['BigBlueButton']['preupload'])) {
                //remove preupload
                unset($current_config['BigBlueButton']['preupload']);
                //add folders into features
                if (isset($current_config['BigBlueButton']['features']) && !isset($current_config['BigBlueButton']['features']['folders'])) {
                    $folders_array = ["folders" => true];
                    $current_config['BigBlueButton']['features'] = array_merge($current_config['BigBlueButton']['features']['folders'], $folders_array);
                }
                $encoded_json = json_encode($current_config);
                if ($encoded_json) {
                    Config::get()->store('VC_CONFIG', json_encode($current_config));
                }
            }
        }
    }
}
