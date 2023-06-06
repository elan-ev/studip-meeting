<?php

/**
 * Adds a column to store description in vc_meeting table.
 *
 * @author Farbod Zamani Broujeni (zamani@elan-ev.de)
 */
class AddDescriptionColumnToMeeting extends Migration
{
    /**
     * {@inheritdoc}
     */
    function description()
    {
        return 'Adds description column to store a some extra info in vc_meeting table.';
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
                description text NULL DEFAULT NULL AFTER name'
        );

        SimpleORMap::expireTableScheme();
    }

    /**
     * {@inheritdoc}
     */
    function down()
    {
        $db = DBManager::get();
        $db->exec(sprintf('ALTER TABLE vc_meetings DROP COLUMN description'));

        SimpleORMap::expireTableScheme();
    }
}
