<?php

namespace ElanEv\Model;


class ModeratorInvitationsLink extends \SimpleORMap
{
    public static function configure($config = array())
    {
        $config['db_table'] = 'vc_moderator_invitations_links';
        $config['belongs_to']['meeting'] = array(
            'class_name' => 'ElanEv\Model\Meeting',
            'foreign_key' => 'meeting_id',
        );
        parent::configure($config);
    }
}