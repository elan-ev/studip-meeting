<?php

/**
 * Adds a field to store a reference to the author of a meeting.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class AddMeetingAuthor extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return 'Adds a field to store a reference to the author of a meeting';
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
            ADD COLUMN user_id VARCHAR(32) NOT NULL AFTER course_id'
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
            DROP COLUMN user_id'
        );

        SimpleORMap::expireTableScheme();
    }
}
