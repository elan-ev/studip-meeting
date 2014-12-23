<?php

/**
 * Adds a table to store meeting configurations for a course.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class AddMeetingConfigTable extends Migration
{
    /**
     * {@inheritdoc}
     */
    function description()
    {
        return 'Adds a table to store meeting configurations for a course.';
    }

    /**
     * {@inheritdoc}
     */
    function up()
    {
        $db = DBManager::get();
        $db->exec(
            'CREATE TABLE IF NOT EXISTS vc_course_config (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT,
              course_id VARCHAR(32) NOT NULL,
              title VARCHAR(255) DEFAULT NULL,
              introduction TEXT,
              PRIMARY KEY(id),
              UNIQUE KEY(course_id)
            )'
        );

        SimpleORMap::expireTableScheme();
    }

    /**
     * {@inheritdoc}
     */
    function down()
    {
        $db = DBManager::get();
        $db->exec('DROP TABLE IF EXISTS vc_course_config');

        SimpleORMap::expireTableScheme();
    }
}
