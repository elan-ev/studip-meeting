<?php

require __DIR__.'/../bootstrap.php';

class NewConfigBBBWelcome extends Migration
{

    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return "add new bbb config option";
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
                $welcome_array = ["welcome" => "Welcome to <b>%%CONFNAME%%</b>!<br><br>For help on using BigBlueButton see these (short) <a href='event:http://www.bigbluebutton.org/html5'><u>tutorial videos</u></a>.<br><br>To join the audio bridge click the phone button.  Use a headset to avoid causing background noise for others."];
                $current_config['BigBlueButton'] = array_merge($current_config['BigBlueButton'], $welcome_array);
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
            if ($current_config && isset($current_config['BigBlueButton']) && isset($current_config['BigBlueButton']['welcome'])) {
                unset($current_config['BigBlueButton']['welcome']);
                $encoded_json = json_encode($current_config);
                if ($encoded_json) {
                    Config::get()->store('VC_CONFIG', json_encode($current_config));
                }
            }

        }
    }
}
