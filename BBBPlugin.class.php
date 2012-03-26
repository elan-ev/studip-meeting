<?php
/*
 * BigBlueButton.class.php - BigBlueButton Stud.IP Integration
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Till Glöggler <till.gloeggler@elan-ev.de>
 * @copyright   2011 ELAN e.V. <http://www.elan-ev.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

require_once dirname(__FILE__) . '/bbb_api.php';

class BBBPlugin extends StudipPlugin implements SystemPlugin
{

    const SALT = '';
    const BBB  = '';

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
