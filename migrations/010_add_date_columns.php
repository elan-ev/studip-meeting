<?php

/**
 * Adds columns to track when records are created and modified.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class AddDateColumns extends Migration
{
    /**
     * {@inheritdoc}
     */
    function description()
    {
        return 'Adds columns to track when records are created and modified';
    }

    /**
     * {@inheritdoc}
     */
    function up()
    {
        $db = DBManager::get();
        $db->exec(
            'ALTER TABLE
               vc_meetings
             ADD COLUMN mkdate INT UNSIGNED NOT NULL,
             ADD COLUMN chdate INT UNSIGNED NOT NULL'
        );
        $db->exec(
            'UPDATE
              vc_meetings
            SET
              mkdate = UNIX_TIMESTAMP(NOW()),
              chdate = UNIX_TIMESTAMP(NOW())'
        );

        SimpleORMap::expireTableScheme();
    }

    /**
     * {@inheritdoc}
     */
    function down()
    {
        $db = DBManager::get();
        $db->exec(
            'ALTER TABLE
               vc_meetings
             DROP COLUMN mkdate,
             DROP COLUMN chdate'
        );

        SimpleORMap::expireTableScheme();
    }
}
