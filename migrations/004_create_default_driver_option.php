<?php

require __DIR__.'/../vendor/autoload.php';

/**
 * Adds a config option to select the default driver.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class CreateDefaultDriverOption extends Migration
{
    /**
     * {@inheritdoc}
     */
    function description()
    {
        return 'Adds a config option to select the default driver';
    }

    /**
     * {@inheritdoc}
     */
    function up()
    {
        $db = DBManager::get();
        $db->exec('INSERT INTO
              config
            SET
              field = "VC_DRIVER",
              type = "string",
              value = "",
              mkdate = UNIX_TIMESTAMP(NOW()),
              chdate = UNIX_TIMESTAMP(NOW()),
              description = "bigbluebutton oder dfnvc",
              comment = ""'
        );

        SimpleORMap::expireTableScheme();
    }

    /**
     * {@inheritdoc}
     */
    function down()
    {
        $db = DBManager::get();
        $db->exec('DELETE FROM config WHERE field = "VC_DRIVER"');

        SimpleORMap::expireTableScheme();
    }
}
