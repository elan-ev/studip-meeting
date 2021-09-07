<?php

// require __DIR__.'/../vendor/autoload.php';

class AddGeneralConfigFeedbackSenderAddress extends Migration
{

    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return "add feedback sender address param with default value of 'standard_mail' to general config";
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
            if ($general_config && !isset($general_config['feedback_sender_address'])) {
                $general_config['feedback_sender_address'] = 'standard_mail';
            }
        } else {
            $general_config['feedback_sender_address'] = 'standard_mail';
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
            if ($current_general_config && isset($current_general_config['feedback_sender_address'])) {
                unset($current_general_config['feedback_sender_address']);
            }
            $encoded_json = json_encode($current_general_config);
            if ($encoded_json) {
                Config::get()->store('VC_GENERAL_CONFIG', $encoded_json);
            }
        }
    }
}