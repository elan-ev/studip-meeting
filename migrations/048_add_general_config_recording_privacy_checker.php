<?php

// require __DIR__.'/../vendor/autoload.php';

class AddGeneralConfigRecordingPrivacyChecker extends Migration
{

    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return "add a general option to choose whether a privacy checker should be displayed before participating on any rooms that has recording capability.";
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        $general_config = null;
        if (Config::get()->VC_GENERAL_CONFIG) {
            $general_config = Config::get()->getValue('VC_GENERAL_CONFIG');
            $general_config = json_decode($general_config, true);
            if ($general_config && !isset($general_config['show_recording_privacy_text'])) {
                $general_config['show_recording_privacy_text'] = true;
            }
        } else {
            $general_config['show_recording_privacy_text'] = true;
        }

        $encoded_json = json_encode($general_config);
        if ($encoded_json) {
            Config::get()->store('VC_GENERAL_CONFIG', $encoded_json);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        if (Config::get()->VC_GENERAL_CONFIG) {
            $current_general_config = Config::get()->getValue('VC_GENERAL_CONFIG');
            $current_general_config = json_decode($current_general_config, true);
            if ($current_general_config && isset($current_general_config['show_recording_privacy_text'])) {
                unset($current_general_config['show_recording_privacy_text']);
            }
            $encoded_json = json_encode($current_general_config);
            if ($encoded_json) {
                Config::get()->store('VC_GENERAL_CONFIG', $encoded_json);
            }
        }
    }
}