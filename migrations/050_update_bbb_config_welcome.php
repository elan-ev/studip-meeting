<?php
class UpdateBBBConfigWelcome extends Migration
{

    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return "Update BBB Welcome message (add German text)";
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        if (Config::get()->VC_CONFIG) {
            $current_config = Config::get()->getValue('VC_CONFIG');
            $current_config = json_decode($current_config, true);
            if ($current_config && isset($current_config['BigBlueButton']) && isset($current_config['BigBlueButton']['welcome'])) {
                $current_config['BigBlueButton']['welcome'] = 'Willkommen im Raum <b>%%CONFNAME%%</b>!<br><br>Hilfe zur Verwendung von BigBlueButton finden Sie in diesen (kurzen) <a target="_blank" href="https://www.bigbluebutton.org/html5"><u>Tutorial-Videos</u></a>.<br><br>Um der Audiobrücke beizutreten, klicken Sie auf die Telefonschaltfläche. Verwenden Sie ein Headset, um Hintergrundgeräusche für andere zu vermeiden.';
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
                $current_config['BigBlueButton']['welcome'] = "Welcome to <b>%%CONFNAME%%</b>!<br><br>For help on using BigBlueButton see these (short) <a href='event:http://www.bigbluebutton.org/html5'><u>tutorial videos</u></a>.<br><br>To join the audio bridge click the phone button.  Use a headset to avoid causing background noise for others.";
                $encoded_json = json_encode($current_config);
                if ($encoded_json) {
                    Config::get()->store('VC_CONFIG', json_encode($current_config));
                }
            }
            
        }
    }
}