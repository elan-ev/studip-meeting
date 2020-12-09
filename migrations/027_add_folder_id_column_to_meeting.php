<?php

/**
 * Adds a column to store folder_id in vc_meeting table.
 *
 * @author Farbod Zamani Broujeni (zamani@elan-ev.de)
 */
class AddFolderIdColumnToMeeting extends Migration
{
    /**
     * {@inheritdoc}
     */
    function description()
    {
        return 'Adds folder_id column to store folder id in vc_meeting table.';
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
              folder_id text DEFAULT NULL AFTER features'
        );

        SimpleORMap::expireTableScheme();
    }

    /**
     * {@inheritdoc}
     */
    function down()
    {
        $db = DBManager::get();
        $db->exec(sprintf('ALTER TABLE vc_meetings DROP COLUMN folder_id'));

        SimpleORMap::expireTableScheme();
    }
}