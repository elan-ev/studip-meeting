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

class BBBPlugin extends StudipPlugin implements StandardPlugin {

    const SALT = 'cb5dbc361d4959e27f4bfa027adc559a';
    const BBB  = 'http://bbb.virtuos.uni-osnabrueck.de/bigbluebutton/'; 

    function show_action()
    {
        Navigation::activateItem('course/BBBPLugin');
        $factory = new Flexi_TemplateFactory(dirname(__FILE__) . '/templates/');
        echo $factory->render("index");
    }
    
    function createMeeting_action() {
        $meetingId = Request::option('cid');
        $modPw = md5($meetingID.'modPW');
        $attPw = md5($meetingID.'attPw');
        $ret = $_SERVER['HTTP_REFERER'];
        
        $bbb = new BigBlueButton();
        $url = $bbb->createMeetingAndGetJoinURL(get_username($GLOBALS['user']->id), $meetingId, 'MOTD', $modPw, $attPw, self::SALT, self::BBB, $ret);
        header('Location: '.$url);
    }

    function joinMeeting_action() {
        $meetingId = Request::option('cid');
        $PW = md5($meetingID.'attPw');
        $ret = $_SERVER['HTTP_REFERER'];
        
        $bbb = new BigBlueButton();
        $url = $bbb->joinURL($meetingID, get_username($GLOBALS['user']->id), $PW, $SALT, $ret );
        header('Location: '.$url);
    }

    function meetingInfo_action($meetingId, $moderatorPw) {
        return true;
        // get details about a currently running meeting
    }

    function getInfoTemplate($course_id) {
        return null;
    }

    function getIconNavigation($course_id, $last_visit) {
        return null;
    }

    public function getTabNavigation($course_id) {
        $main = new Navigation("BigBlueButton");
        $main->setURL(PluginEngine::getURL('BBBPLugin'));
        return array('BBBPLugin' => $main);
    }

}
