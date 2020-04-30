<?php

/**
 * Sets the default value of the active property to false.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class EnableNewCourseMeetings extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return 'Sets the default value of the active property to 1';
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
            MODIFY active TINYINT NOT NULL DEFAULT 1'
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
            MODIFY active TINYINT NOT NULL DEFAULT 0'
        );

        SimpleORMap::expireTableScheme();
    }
}
