<?php

/**
 * 
 *
 * @author Farbod Zamani <zamani@elan-ev.de>
 */
class DropDefaultNameFromModeratorInvitaionLinks extends Migration
{
    /**
     * {@inheritdoc}
     */
    function description()
    {
        return 'Drop default_name column from moderator invitation links.';
    }

    /**
     * {@inheritdoc}
     */
    function up()
    {
        $db = DBManager::get();
        $db->exec(sprintf('ALTER TABLE vc_moderator_invitations_links DROP COLUMN default_name'));

        SimpleORMap::expireTableScheme();
    }

    /**
     * {@inheritdoc}
     */
    function down()
    {
        $db = DBManager::get();
        $db->exec(sprintf("ALTER TABLE vc_moderator_invitations_links ADD COLUMN default_name varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' after id"));

        SimpleORMap::expireTableScheme();
    }
}
