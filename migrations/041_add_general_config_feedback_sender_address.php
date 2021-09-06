<?php

// require __DIR__.'/../vendor/autoload.php';

class AddGeneralConfigFeedbackSenderAddress extends Migration
{

    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return "add feedback sender address param to general config";
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        if (Config::get()->VC_GENERAL_CONFIG) {
            $current_general_config = Config::get()->getValue('VC_GENERAL_CONFIG');
            $current_general_config = json_decode($current_general_config, true);
            if ($current_general_config && !isset($current_general_config['feedback_sender_address'])) {
                $current_general_config['feedback_sender_address'] = 'standard_mail';
            }
            $encoded_json = json_encode($current_general_config);
            if ($encoded_json) {
                Config::get()->store('VC_GENERAL_CONFIG', json_encode($current_general_config));
            }
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
                Config::get()->store('VC_GENERAL_CONFIG', json_encode($current_general_config));
            }
        }
    }
}