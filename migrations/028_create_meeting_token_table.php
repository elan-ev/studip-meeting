<?php

/**
 * Creates a table for meeting token in order to grant access via extrnal calls.
 *
 * @author Farbod Zamani <zamani@elan-ev.de>
 */
class CreateMeetingTokenTable extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return 'Creates a table for meeting token in order to grant access via extrnal calls.';
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        DBManager::get()->exec(
            'CREATE TABLE IF NOT EXISTS vc_meeting_token  (
              meeting_id INT UNSIGNED NOT NULL,
              token VARCHAR(32) NOT NULL,
              expiration INT NOT NULL,
              PRIMARY KEY (meeting_id , token , expiration ),
              UNIQUE KEY (expiration),
              UNIQUE KEY (token),
              UNIQUE KEY (meeting_id)
            )'
        );

        SimpleORMap::expireTableScheme();
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        DBManager::get()->exec('DROP TABLE IF EXISTS vc_meeting_token');

        SimpleORMap::expireTableScheme();
    }
}
