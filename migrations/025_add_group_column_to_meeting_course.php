<?php

/**
 * Adds a column to store the index of driver server.
 *
 * @author Farbod Zamani Broujeni (zamani@elan-ev.de)
 */
class AddGroupColumnToMeetingCourse extends Migration
{
    /**
     * {@inheritdoc}
     */
    function description()
    {
        return 'Adds a column into MeetingCourse table in order to manage Group Access!';
    }

    /**
     * {@inheritdoc}
     */
    function up()
    {
        $db = DBManager::get();
        $db->exec(
            'ALTER TABLE
              vc_meeting_course
            ADD COLUMN
              group_id VARCHAR(32) DEFAULT NULL AFTER course_id'
        );

        SimpleORMap::expireTableScheme();
    }

    /**
     * {@inheritdoc}
     */
    function down()
    {
        $db = DBManager::get();
        $db->exec(sprintf('ALTER TABLE vc_meeting_course DROP COLUMN group_id'));

        SimpleORMap::expireTableScheme();
    }
}
