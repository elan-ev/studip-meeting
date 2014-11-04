<?php

/*
 * Copyright (C) 2012 - Till Glöggler     <tgloeggl@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


/**
 * @author    tgloeggl@uos.de
 * @copyright (c) Authors
 */

require_once 'app/controllers/studip_controller.php';

use ElanEv\Driver\DriverFactory;
use ElanEv\Driver\JoinParameters;
use ElanEv\Model\Meeting;

/**
 * @property \BBBPlugin    $plugin
 * @property bool          $configured
 * @property \Seminar_Perm $perm
 * @property bool          $canModify
 * @property bool          $canJoin
 * @property string        $meetingId
 * @property array         $errors
 * @property Meeting[]     $meetings
 */
class IndexController extends StudipController
{
    private static $BBB_URL;
    private static $BBB_SALT;

    /**
     * @var ElanEv\Driver\DriverInterface
     */
    private $driver;

    public function __construct($dispatcher)
    {
        parent::__construct($dispatcher);

        $this->plugin = $GLOBALS['plugin'];
        $this->perm = $GLOBALS['perm'];
        $driverFactory = new DriverFactory(Config::get());

        try {
            $this->driver = $driverFactory->getDefaultDriver();
            $this->configured = true;
        } catch (\LogicException $e) {
            $this->configured = false;
        }
    }

    public function index_action()
    {
        $this->errors = array();

        if (\Request::method() == 'POST') {
            if (!\Request::get('name')) {
                $this->errors[] = _('Bitte geben Sie dem Meeting einen Namen.');
            }

            if (count($this->errors) === 0) {
                $this->createMeeting(\Request::get('name'));
            }
        }

        Navigation::activateItem('course/BBBPlugin');
        $nav = Navigation::getItem('course/BBBPlugin');
        $nav->setImage('icons/16/black/chat.png');

        $this->canModify = $this->canModify($this->getCourseId());

        if ($this->canModify) {
            $this->meetings = \ElanEv\Model\Meeting::findByCourseId($this->getCourseId());
        } else {
            $this->meetings = \ElanEv\Model\Meeting::findActiveByCourseId($this->getCourseId());
        }
    }

    /**
     * creates meeting and redirects to BBB meeting.
     */
    public function createMeeting_action()
    {
        if (!$this->canModify($this->getCourseId())) {
            $this->error();
        }

        $course = Course::find($this->meetingId);

        if ($this->createMeeting($course->name)) {
            // get the join url
            $joinParams = array(
                'meetingId' => $this->meetingId, // REQUIRED - We have to know which meeting to join.
                'username' => get_username($GLOBALS['user']->id),  // REQUIRED - The user display name that will show in the BBB meeting.
            );
            if ($this->canModify($this->meetingId)) {
                $joinParams['password'] = $this->modPw;
            } else {
                $joinParams['password'] = $this->attPw;
            }

            $joinParameters = new JoinParameters();
            $joinParameters->setMeetingId($this->meetingId);
            $joinParameters->setUsername(get_username($GLOBALS['user']->id));

            if ($this->canModify($this->meetingId)) {
                $joinParameters->setPassword($meetingParameters->getModeratorPassword());
            } else {
                $joinParameters->setPassword($meetingParameters->getAttendeePassword());
            }

            $this->redirect($this->driver->getJoinMeetingUrl($joinParameters));
        }
    }

    public function enable_action($meetingId)
    {
        if ($this->canModify($this->meetingId)) {
            $meeting = new Meeting($meetingId);
            $meeting->active = !$meeting->active;
            $meeting->store();
        }

        $this->redirect(PluginEngine::getURL($this->plugin, array(), 'index'));
    }

    public function delete_action($meetingId)
    {
        if ($this->canModify($this->meetingId)) {
            $meeting = new Meeting($meetingId);
            $meeting->delete();
        }

        $this->redirect(PluginEngine::getURL($this->plugin, array(), 'index'));
    }

    /**
     *  redirects to active BBB meeting.
     */
    public function joinMeeting_action($meetingId)
    {
        if(!$this->meeting_running) {
            $this->error();
        }

        /** @var Seminar_User $user */
        $user = $GLOBALS['user'];

        $meeting = new Meeting($meetingId);
        $joinParameters = new JoinParameters();
        $joinParameters->setMeetingId($meetingId);
        $joinParameters->setIdentifier($meeting->identifier);
        $joinParameters->setRemoteId($meeting->remote_id);
        $joinParameters->setUsername(get_username($user->id));
        $joinParameters->setEmail($user->Email);
        $joinParameters->setFirstName($user->Vorname);
        $joinParameters->setLastName($user->Nachname);

        if ($this->canModify($this->meetingId)) {
            $joinParameters->setPassword($this->modPw);
        } else {
            $joinParameters->setPassword($this->attPw);
        }

        $this->redirect($this->driver->getJoinMeetingUrl($joinParameters));
    }

    public function meetingInfo_action($meetingId, $moderatorPw)
    {
        return true;
        // get details about a currently running meeting
    }

    public function saveConfig_action()
    {
        if (!$this->perm->have_perm('root')) {
            die;
        }

        Config::get()->store('BBB_URL', Request::get('bbb_url'));
        Config::get()->store('BBB_SALT', Request::get('bbb_salt'));

        $this->redirect(PluginEngine::getLink('BBBPlugin/index/index'));
    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * */
    /* * * * * H E L P E R   F U N C T I O N S * * * * */
    /* * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Initiate plugin params:
     *
     * 'perm'           => 'mod' has BBB moderator permission
     *                     'att' has BBB attendee permission
     * 'allow_join      => true if user is allowed to join
     * 'meeting_running'=> true if meeting is running
     * 'path'           => relative path to plugin
     */

    private function getCourseId()
    {
        if (!Request::option('cid')) {
            if ($GLOBALS['SessionSeminar']) {
                URLHelper::bindLinkParam('cid', $GLOBALS['SessionSeminar']);
                return $GLOBALS['SessionSeminar'];
            }

            return false;
        }

        return Request::option('cid');
    }

    /**
     * Common code for all actions: set default layout and page title.
     *
     * @param type $action
     * @param type $args
     */
    function before_filter(&$action, &$args)
    {
        $this->validate_args($args, array('option', 'option'));

        parent::before_filter($action, $args);

        $this->flash = Trails_Flash::instance();

        // set default layout
        $layout = $GLOBALS['template_factory']->open('layouts/base');
        $this->set_layout($layout);

        PageLayout::setTitle(getHeaderLine($this->getCourseId()) .' - '. _('Big Blue Button'));
        PageLayout::addHeadElement(
            'script',
            array('src' => $this->plugin->getPluginURL().'/assets/js/meetings.js'),
            ''
        );

        if ($GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP'] && $GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP'] != '/') {
            $this->picturepath = $GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP'] .'/'. $this->dispatcher->trails_root . '/images';
        } else {
            $this->picturepath = '/'. $this->dispatcher->trails_root . '/images';
        }

        self::$BBB_URL  = Config::get()->getValue('BBB_URL');
        self::$BBB_SALT = Config::get()->getValue('BBB_SALT');

        if ($this->canJoin($this->getCourseId())) {
            $this->allow_join = true;
        }

        $this->meetingId = $this->getCourseId();
        $this->modPw = md5($this->meetingId . 'modPw');
        $this->attPw = md5($this->meetingId . 'attPw');

        $meetings = Meeting::findByCourseId($this->meetingId);
        $this->meeting_running = count($meetings) > 0 && $this->driver->isMeetingRunning($meetings[0]->getMeetingParameters());
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    private function createMeeting($name)
    {
        $meeting = new Meeting();
        $meeting->course_id = $this->meetingId;
        $meeting->name = $name;
        $meeting->driver = $this->driver->getName();
        $meeting->attendee_password = $this->attPw;
        $meeting->moderator_password = $this->modPw;
        $meeting->store();
        $meetingParameters = $meeting->getMeetingParameters();

        if (!$this->driver->createMeeting($meetingParameters)) {
            return false;
        }

        $meeting->remote_id = $meetingParameters->getRemoteId();
        $meeting->store();

        return true;
    }

    private function canModify($meetingId)
    {
        return $this->perm->have_studip_perm('tutor', $meetingId);
    }

    private function canJoin($meetingId)
    {
        return $this->canModify($meetingId) || $this->perm->have_studip_perm('autor', $meetingId);
    }
}
