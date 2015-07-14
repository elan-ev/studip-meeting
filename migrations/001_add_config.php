<?php

class AddConfig extends Migration 
{
    function up()
    {
        try {
            Config::get()->create('BBB_URL');
            Config::get()->create('BBB_SALT');
        } catch (InvalidArgumentException $ex) {

        }
    }
    
    function down()
    {
        Config::get()->delete('BBB_URL');
        Config::get()->delete('BBB_SALT');
    }
}
