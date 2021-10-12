<?php

/**
 * Drop expiration primary key and index, change token collation from vc_meeting_token
 *
 * @author Farbod Zamani <zamani@elan-ev.de>
 */
class RemoveExpirationIndexChangeTokenCollationInMeetingToken extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return 'Drop expiration primary key and index, change token collation from vc_meeting_token.';
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        DBManager::get()->exec("ALTER TABLE `vc_meeting_token` DROP INDEX `expiration`;");
        DBManager::get()->exec("ALTER TABLE `vc_meeting_token` DROP PRIMARY KEY , ADD PRIMARY KEY (`meeting_id` , `token`);");

        // This is a performance fix, no need to revert it back!
        DBManager::get()->exec("ALTER TABLE `vc_meeting_token` CHANGE token token VARCHAR(32) COLLATE latin1_bin NOT NULL;");
        
        SimpleORMap::expireTableScheme();
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        DBManager::get()->exec("ALTER TABLE `vc_meeting_token` DROP PRIMARY KEY, ADD PRIMARY KEY (`meeting_id`, `token`, `expiration`), ADD CONSTRAINT `expiration` UNIQUE KEY(`expiration`);");

        SimpleORMap::expireTableScheme();
    }
}
