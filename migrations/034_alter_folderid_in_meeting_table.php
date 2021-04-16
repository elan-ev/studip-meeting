<?php

/**
 * Alter folder_id column in vc_meetings
 *
 * @author Farbod Zamani Broujeni (zamani@elan-ev.de)
 */

class AlterFolderidInMeetingTable extends Migration
{
    /**
     * {@inheritdoc}
     */
    function description()
    {
        return 'Altring folder_id column in vc_meetings.';
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        $db = DBManager::get();
        $db->exec(
            'ALTER TABLE
              vc_meetings
            CHANGE
                folder_id folder_id VARCHAR(32) COLLATE latin1_bin NULL DEFAULT NULL;'
        );
        SimpleORMap::expireTableScheme();
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        $db = DBManager::get();
        
        $db->exec(
            'ALTER TABLE
              vc_meetings
            CHANGE
                folder_id folder_id text COLLATE utf8mb4_bin DEFAULT NULL;'
        );

        SimpleORMap::expireTableScheme();
    }
}