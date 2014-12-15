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
        $db->exec(sprintf(
            'INSERT INTO
              config
            SET
              config_id = "%s",
              field = "VC_DRIVER",
              type = "string",
              mkdate = UNIX_TIMESTAMP(NOW()),
              chdate = UNIX_TIMESTAMP(NOW()),
              description = "bigbluebutton oder dfnvc"',
            \ElanEv\Driver\DriverFactory::DEFAULT_DRIVER_CONFIG_ID
        ));

        SimpleORMap::expireTableScheme();
    }

    /**
     * {@inheritdoc}
     */
    function down()
    {
        $db = DBManager::get();
        $db->exec(sprintf(
            'DELETE FROM
              config
            WHERE
              config_id = "%s"',
            \ElanEv\Driver\DriverFactory::DEFAULT_DRIVER_CONFIG_ID
        ));

        SimpleORMap::expireTableScheme();
    }
}
