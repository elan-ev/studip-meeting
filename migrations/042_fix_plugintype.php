<?php
class FixPluginType extends Migration
{

    function up()
    {
        DBManager::get()->query("UPDATE plugins
            SET plugintype = 'PortalPlugin,StandardPlugin,StudipModule,SystemPlugin'
            WHERE pluginclassname = 'MeetingPlugin'");

        SimpleOrMap::expireTableScheme();
    }

    function down()
    {
    }

}
