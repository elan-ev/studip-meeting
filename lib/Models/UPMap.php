<?php

namespace Meetings\Models;

class UPMap extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['registered_callbacks']['before_create'][] = 'cbSetMkdate';
        $config['registered_callbacks']['before_create'][] = 'cbSetChdate';

        $config['registered_callbacks']['before_update'][] = 'cbSetChdate';

        parent::configure($config);
    }

    function cbSetMkdate()  {
        $this->mkdate = date('Y-m-d H:i:s');
    }

    function cbSetChdate()  {
        $this->chdate = date('Y-m-d H:i:s');
    }
}
