<?php

// require __DIR__.'/../vendor/autoload.php';

class AddGeneralConfigStudipDefaultSlides extends Migration
{

    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return "add an option to choose whether the deafult sildes should be handled by StudIP or server with default value of 'studip' to general config";
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
            if ($general_config && !isset($general_config['read_default_slides_from'])) {
                $general_config['read_default_slides_from'] = 'studip';
            }
        } else {
            $general_config['read_default_slides_from'] = 'studip';
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
            if ($current_general_config && isset($current_general_config['read_default_slides_from'])) {
                unset($current_general_config['read_default_slides_from']);
            }
            $encoded_json = json_encode($current_general_config);
            if ($encoded_json) {
                Config::get()->store('VC_GENERAL_CONFIG', $encoded_json);
            }
        }
    }
}