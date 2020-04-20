<?php

/**
 * Adds a column to store the index of driver server.
 *
 * @author Farbod Zamani Broujeni (zamani@elan-ev.de)
 */
class AddFeatureColumnToMeeting extends Migration
{
    /**
     * {@inheritdoc}
     */
    function description()
    {
        return 'Adds a column to store extra feature for the room like guestPolicy and so on!.';
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
              features text DEFAULT NULL AFTER join_as_moderator'
        );

        SimpleORMap::expireTableScheme();
    }

    /**
     * {@inheritdoc}
     */
    function down()
    {
        $db = DBManager::get();
        $db->exec(sprintf('ALTER TABLE vc_meetings DROP COLUMN features'));

        SimpleORMap::expireTableScheme();
    }
}
