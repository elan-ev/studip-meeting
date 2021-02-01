<?php

namespace Meetings\Models;

class I18N
{
    public static function _($text)
    {
        return dgettext(\MeetingPlugin::GETTEXT_DOMAIN, $text);
    }
}
