<?php

/**
 * Adds a column to store the index of driver server.
 *
 * @author Farbod Zamani Broujeni (zamani@elan-ev.de)
 */
class AddServerIndexColumn extends Migration
{
    /**
     * {@inheritdoc}
     */
    function description()
    {
        return 'Adds a column to store the index of driver server.';
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
            ADD COLUMN
              server_index TINYINT(3) unsigned NOT NULL DEFAULT 0 AFTER driver'
        );

        SimpleORMap::expireTableScheme();
    }

    /**
     * {@inheritdoc}
     */
    function down()
    {
        $db = DBManager::get();
        $db->exec(sprintf('ALTER TABLE vc_meetings DROP COLUMN server_index'));

        SimpleORMap::expireTableScheme();
    }
}
