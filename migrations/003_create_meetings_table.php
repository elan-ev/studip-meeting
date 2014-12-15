<?php

/**
 * Creates a table that maps meeting ids to courses.
 *
 * A meeting can be identified by both an integer identifier as well as as
 * string based unique id.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class CreateMeetingsTable extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return 'Creates a table that maps meeting ids to courses.';
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        DBManager::get()->exec(
            'CREATE TABLE IF NOT EXISTS vc_meetings (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
              identifier VARCHAR(32) NOT NULL,
              remote_id VARCHAR(32),
              course_id VARCHAR(32) NOT NULL,
              name VARCHAR(255) NOT NULL,
              driver VARCHAR(32) NOT NULL,
              attendee_password VARCHAR(32),
              moderator_password VARCHAR(32),
              UNIQUE KEY(identifier)
            )'
        );

        SimpleORMap::expireTableScheme();
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        DBManager::get()->exec('DROP TABLE IF EXISTS vc_meetings');

        SimpleORMap::expireTableScheme();
    }
}
