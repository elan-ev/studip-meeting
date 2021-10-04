<?php

namespace ElanEv\Model;


class QRCodeToken extends \SimpleORMap
{
    public static function configure($config = array())
    {
        $config['db_table'] = 'vc_qr_code_token';
        $config['belongs_to']['meeting'] = array(
            'class_name' => 'ElanEv\Model\Meeting',
            'foreign_key' => 'meeting_id',
        );
        parent::configure($config);
    }
}