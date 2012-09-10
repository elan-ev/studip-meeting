<?php

class AddConfig extends Migration 
{
    function up()
    {
        Config::get()->create('BBB_URL');
        Config::get()->create('BBB_SALT');
    }
    
    function down()
    {
        Config::get()->delete('BBB_URL');
        Config::get()->delete('BBB_SALT');
    }
}
