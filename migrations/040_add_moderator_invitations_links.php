<?php

class AddModeratorInvitationsLinks extends Migration
{
    public function up()
    {
        $db = DBManager::get();
        $db->exec(
            "CREATE TABLE IF NOT EXISTS `vc_moderator_invitations_links` (
             `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
             `meeting_id` int(10) unsigned NOT NULL,
             `default_name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
             `password` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
             `hex` varchar(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
             PRIMARY KEY (`id`),
             KEY `hex` (`hex`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC"
        );
        SimpleORMap::expireTableScheme();
    }

    public function down()
    {
        $db = DBManager::get();
        $db->exec('DROP TABLE IF EXISTS vc_moderator_invitations_links');

        SimpleORMap::expireTableScheme();
    }
}