<?php

/**
 * Adds a column to store a reference to a recording of a meeting.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class AddRecordingUrlColumn extends Migration
{
    /**
     * {@inheritdoc}
     */
    function description()
    {
        return 'Adds a column to store a reference to a recording of a meeting.';
    }

    /**
     * {@inheritdoc}
     */
    function up()
    {
        $db = DBManager::get();
        $db->exec(sprintf('ALTER TABLE vc_meetings ADD COLUMN recording_url VARCHAR(255) DEFAULT NULL AFTER name'));

        SimpleORMap::expireTableScheme();
    }

    /**
     * {@inheritdoc}
     */
    function down()
    {
        $db = DBManager::get();
        $db->exec(sprintf('ALTER TABLE vc_meetings DROP COLUMN recording_url'));

        SimpleORMap::expireTableScheme();
    }
}
