<?php

/**
 * Adding default column to the meeting course table to be used it in widget and appoinments.
 *
 * @author Farbod Zamani Broujeni (zamani@elan-ev.de)
 */
class AddDefaultColumnToMeetingCourseTable extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return 'Adding is_default column to the meeting course table to be used it in widget and appoinments.';
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        $db = DBManager::get();
        $db->exec(
            'ALTER TABLE
              vc_meeting_course
            ADD COLUMN
            is_default TINYINT NOT NULL DEFAULT 0 AFTER active'
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
              vc_meeting_course
            DROP COLUMN
              is_default'
        );

        SimpleORMap::expireTableScheme();
    }
}
