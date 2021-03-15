<?php

require __DIR__.'/../vendor/autoload.php';

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
        $config = \Config::get()->getValue('VC_CONFIG');
        $config = json_decode($config, true);

        $config['BigBlueButton']['welcome']='Welcome to <b>%%CONFNAME%%</b>!<br><br>For help on using BigBlueButton see these (short) <a href="event:http://www.bigbluebutton.org/html5"><u>tutorial videos</u></a>.<br><br>To join the audio bridge click the phone button.  Use a headset to avoid causing background noise for others.';

        \Config::get()->store('VC_CONFIG', json_encode($config));
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        $config = \Config::get()->getValue('VC_CONFIG');
        $config = json_decode($config, true);

        unset($config['BigBlueButton']['welcome']);

        \Config::get()->store('VC_CONFIG', json_encode($config));
    }
}