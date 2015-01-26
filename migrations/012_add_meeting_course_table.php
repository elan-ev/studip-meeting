<?php

/**
 * Creates a table that allows to map more than one course to a meeting.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class AddMeetingCourseTable extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return 'Creates a table that allows to map more than one course to a meeting.';
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        $db = DBManager::get();
        $db->exec(
            'CREATE TABLE IF NOT EXISTS vc_meeting_course (
              meeting_id INT UNSIGNED NOT NULL,
              course_id VARCHAR(32) NOT NULL,
              PRIMARY KEY(meeting_id, course_id)
            )'
        );
        $db->exec(
            'INSERT INTO vc_meeting_course (meeting_id, course_id)
            SELECT id, course_id FROM vc_meetings'
        );
        $db->exec('ALTER TABLE vc_meetings DROP COLUMN course_id');

        SimpleORMap::expireTableScheme();
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        $db = DBManager::get();
        $db->exec('ALTER TABLE vc_meetings ADD COLUMN course_id VARCHAR(32) NOT NULL AFTER remote_id');
        $db->exec(
            'UPDATE
              vc_meetings AS m,
              vc_meeting_course AS mc
            SET
              m.course_id = mc.course_id
            WHERE
              m.id = mc.meeting_id'
        );
        $db->exec('DROP TABLE IF EXISTS vc_meeting_course');

        SimpleORMap::expireTableScheme();
    }
}
