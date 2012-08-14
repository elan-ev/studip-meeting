<?php

/**
 * BBB.php - Wrapper for bbb_api.php for ease of use 
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 3 of
 * the License, or (at your option) any later version.
 *
 * @author      Till Glöggler <tgloeggl@uos.de>
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GPL version 3
 * @category    Stud.IP
 */

class BBB {
    function createMeeting_action($name, $meetingId, $attPw, $modPw)
    {
        $bbb = new BigBlueButton();
        echo $bbb->createMeetingAndGetJoinURL(get_username($GLOBALS['user']->id), $meetingId, 'MOTD', $modPw, $attPw, self::SALT, self::BBB, 'www.inspace.de');
    }

    function joinMeeting_action($username, $meetingId, $pw)
    {
    
    }

    function meetingInfo_action($meetingId, $moderatorPw)
    {
        // get details about a currently running meeting
    }

}
