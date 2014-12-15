<?php

/**
 * Sets the default value of the active property to false.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class DisableNewMeetings extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return 'Sets the default value of the active property to false';
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
            MODIFY active TINYINT NOT NULL DEFAULT 0'
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
            MODIFY active TINYINT NOT NULL DEFAULT 1'
        );

        SimpleORMap::expireTableScheme();
    }
}
