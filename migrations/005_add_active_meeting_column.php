<?php

/**
 * Adds a column to mark meetings as active/inactive.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class AddActiveMeetingColumn extends Migration
{
    /**
     * {@inheritdoc}
     */
    function description()
    {
        return 'Adds a column to mark meetings as active/inactive';
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
              active TINYINT NOT NULL DEFAULT 1 AFTER driver'
        );
    }

    /**
     * {@inheritdoc}
     */
    function down()
    {
        $db = DBManager::get();
        $db->exec(
            'ALTER TABLE
              vc_meetings
            DROP COLUMN
              active'
        );
    }
}
