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

    const SALT = '';
    const BBB  = ''; 
    
    private $params = array(
        'perm' => '',
        'allow_join' => false,
        'meeting_running' => false,
        'path' => ''
    );

    /**
     * Initiate plugin params:
     * 
     * 'perm'           => 'mod' has BBB moderator permission
     *                     'att' has BBB attendee permission 
     * 'allow_join      => true if user is allowed to join
     * 'meeting_running'=> true if meeting is running
     * 'path'           => relative path to plugin
     */
    private function get_params()
    {
        if ($GLOBALS['perm']->have_studip_perm("dozent", $this->getContext())) {
            $this->params['perm'] = 'mod';
        } elseif ($GLOBALS['perm']->have_studip_perm("autor", $this->getContext())) {
            $this->params['perm'] = 'att';
        }

        if ($this->params['perm'] !== '') {
            $this->params['allow_join'] = true;
        }
        
        $bbb = new BigBlueButton();
        $meetingId = Request::option('cid');
        $this->params['meeting_running'] = 
        $bbb->isMeetingRunning($meetingId, self::BBB, self::SALT);

        //TODO: path doesnt point to image, find StudIP function for absolute path
        $this->params['img_path'] = $this->getPluginPath().'/img/';
        
        
    }

    private function getContext()
    {
        return $GLOBALS['SessSemName'][1];
    }

    public function show_action()
    {
        $this->get_params();
        Navigation::activateItem('course/BBBPLugin');
        $factory = new Flexi_TemplateFactory(dirname(__FILE__) . '/templates/');
        echo $factory->render("index", array('params' => $this->params));
    }

    /**
     * creates meeting and redirects to BBB meeting. 
     */
    public function createMeeting_action() {
        $this->get_params();
        if (!$this->params['perm'] == 'dozent') {
            $this->error();
        }

        $meetingId = Request::option('cid');
        $modPw = md5($meetingID.'modPW');
        $attPw = md5($meetingID.'attPw');
        $ret = $_SERVER['HTTP_REFERER'];
        
        $bbb = new BigBlueButton();
        $url = $bbb->createMeetingAndGetJoinURL(
                get_username($GLOBALS['user']->id), $meetingId, 'MOTD', $modPw, 
                $attPw, self::SALT, self::BBB, $ret);
        header('Location: '.$url);
    }

    /**
     *  redirects to active BBB meeting. 
     */
    public function joinMeeting_action() {
        $this->get_params();
        $meetingId = Request::option('cid');

        if ($this->params['perm'] == 'att') {
            $PW = md5($meetingID.'attPw');
        } elseif ($this->params['perm'] == 'mod') {
            $PW = md5($meetingID.'modPw');
        } else {
            $this->error();
        }

        if(!$this->params['meeting_running']) {
            $this->error();
        }
        
        $bbb = new BigBlueButton();
        $url = $bbb->joinURL($meetingId, get_username($GLOBALS['user']->id),
                $PW, self::SALT, self::BBB);
        header('Location: '.$url);
    }

    public function meetingInfo_action($meetingId, $moderatorPw) {
        return true;
        // get details about a currently running meeting
    }

    public function getInfoTemplate($course_id) {
        return null;
    }

    public function getIconNavigation($course_id, $last_visit) {
        return null;
    }

    public function getTabNavigation($course_id) {
        $main = new Navigation("BigBlueButton");
        $main->setURL(PluginEngine::getURL('BBBPLugin'));
        return array('BBBPLugin' => $main);
    }

    //TODO: show error message
    public function error(){
        return null;
    }

}
