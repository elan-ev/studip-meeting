<?php

/**
 * Configure a meeting to allow all participants to join with moderation
 * permissions.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class AddJoinAsModeratorColumn extends Migration
{
    /**
     * {@inheritdoc}
     */
    function description()
    {
        return 'Configure a meeting to allow all participants to join with moderation permissions.';
    }

    /**
     * {@inheritdoc}
     */
    function up()
    {
        $db = DBManager::get();
        $db->exec(sprintf('ALTER TABLE vc_meetings ADD COLUMN join_as_moderator TINYINT NOT NULL DEFAULT 0'));

        SimpleORMap::expireTableScheme();
    }

    /**
     * {@inheritdoc}
     */
    function down()
    {
        $db = DBManager::get();
        $db->exec(sprintf('ALTER TABLE vc_meetings DROP COLUMN join_as_moderator'));

        SimpleORMap::expireTableScheme();
    }
}
