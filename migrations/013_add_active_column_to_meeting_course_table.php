<?php

/**
 * Allows it to enable/disable meetings per course.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class AddActiveColumnToMeetingCourseTable extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return 'Allows it to enable/disable meetings per course.';
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
              active TINYINT NOT NULL DEFAULT 0 AFTER course_id'
        );
        $db->exec(
            'UPDATE
              vc_meeting_course AS mc,
              vc_meetings AS m
            SET
              mc.active = m.active
            WHERE
              mc.meeting_id = m.id'
        );
        $db->exec(
            'ALTER TABLE
              vc_meetings
            DROP COLUMN
              active'
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
            ADD COLUMN
              active TINYINT NOT NULL DEFAULT 0 AFTER driver'
        );
        $db->exec(
            'ALTER TABLE
              vc_meeting_course
            DROP COLUMN
              active'
        );

        SimpleORMap::expireTableScheme();
    }
}
