<?php

/**
 * Creates a table to log when users join meetings.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class CreateJoinTable extends Migration
{
    /**
     * {@inheritdoc}
     */
    function description()
    {
        return 'Creates a table to log when users join meetings';
    }

    /**
     * {@inheritdoc}
     */
    function up()
    {
        $db = DBManager::get();
        $db->exec(
            'CREATE TABLE IF NOT EXISTS vc_joins (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT,
              meeting_id INT UNSIGNED NOT NULL,
              user_id VARCHAR(32) NOT NULL,
              last_join INT UNSIGNED NOT NULL,
              PRIMARY KEY(id)
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
        $db->exec('DROP TABLE IF EXISTS vc_joins');

        SimpleORMap::expireTableScheme();
    }
}
