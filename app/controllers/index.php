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
use ElanEv\Model\Join;
use ElanEv\Model\Meeting;

/**
 * @property \VideoConferencePlugin $plugin
 * @property bool                   $configured
 * @property \Seminar_Perm          $perm
 * @property \Flexi_TemplateFactory $templateFactory
 * @property bool                   $confirmDeleteMeeting
 * @property string[]               $questionOptions
 * @property bool                   $canModifyCourse
 * @property array                  $errors
 * @property Meeting[]              $meetings
 */
class IndexController extends StudipController
{
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

    /**
     * {@inheritdoc}
     */
    function before_filter(&$action, &$args)
    {
        $this->validate_args($args, array('option', 'option'));

        parent::before_filter($action, $args);

        // set default layout
        $this->templateFactory = $GLOBALS['template_factory'];
        $layout = $this->templateFactory->open('layouts/base');
        $this->set_layout($layout);

        PageLayout::setTitle(getHeaderLine($this->getCourseId()) .' - '. _('Konferenzen'));
        PageLayout::addScript($this->plugin->getAssetsUrl().'/js/meetings.js');
        PageLayout::addStylesheet($this->plugin->getAssetsUrl().'/css/meetings.css');
    }

    public function index_action()
    {
        $this->errors = array();

        if (Request::method() == 'POST') {
            if (!Request::get('name')) {
                $this->errors[] = _('Bitte geben Sie dem Meeting einen Namen.');
            }

            if (count($this->errors) === 0) {
                $this->createMeeting(\Request::get('name'));
            }
        }

        if (Request::get('delete') > 0) {
            $meeting = new Meeting(Request::get('delete'));

            if (!$meeting->isNew()) {
                $this->confirmDeleteMeeting = true;
                $this->questionOptions = array(
                    'question' => _('Wollen Sie wirklich das Meeting "'.$meeting->name.'" löschen?'),
                    'approvalLink' => PluginEngine::getLink($this->plugin, array(), 'index/delete/'.$meeting->id),
                    'disapprovalLink' => PluginEngine::getLink($this->plugin, array(), 'index'),
                );
            }
        }

        if (Navigation::hasItem('course/'.VideoConferencePlugin::NAVIGATION_ITEM_NAME)) {
            Navigation::activateItem('course/'.VideoConferencePlugin::NAVIGATION_ITEM_NAME);
            /** @var Navigation $navItem */
            $navItem = Navigation::getItem('course/'.VideoConferencePlugin::NAVIGATION_ITEM_NAME);
            $navItem->setImage('icons/16/black/chat.png');
        }

        $this->canModifyCourse = $this->userCanModifyCourse($this->getCourseId());

        if ($this->canModifyCourse) {
            $this->meetings = \ElanEv\Model\Meeting::findByCourseId($this->getCourseId());
        } else {
            $this->meetings = \ElanEv\Model\Meeting::findActiveByCourseId($this->getCourseId());
        }

        $sidebar = Sidebar::Get();
        $settings = new ActionsWidget();
        $settings->addCSSClass('sidebar-meeting-info');
        $settings->setTitle('Informationen');
        $settings->addLink(_('Alle Informationen anzeigen'), '#', null, array(
            'class' => 'toggle-info show-info',
            'data-show-text' => _('Alle Informationen anzeigen'),
            'data-hide-text' => _('Alle Informationen ausblenden'),
        ));
        $sidebar->addWidget($settings);
    }

    /**
     * creates meeting and redirects to BBB meeting.
     */
    public function createMeeting_action()
    {
        if (!$this->userCanModifyCourse($this->getCourseId())) {
            $this->redirect(PluginEngine::getURL($this->plugin, array(), 'index'));
        }

        $course = Course::find($this->getCourseId());

        if ($this->createMeeting($course->name)) {
            // get the join url
            $joinParameters = new JoinParameters();
            $joinParameters->setMeetingId($this->getCourseId());
            $joinParameters->setUsername(get_username($GLOBALS['user']->id));
            $joinParameters->setPassword($this->generateModeratorPassword());
            $joinParameters->setHasModerationPermissions(true);

            $this->redirect($this->driver->getJoinMeetingUrl($joinParameters));
        }
    }

    public function enable_action($meetingId)
    {
        $meeting = new Meeting($meetingId);

        if (!$meeting->isNew() && $this->userCanModifyMeeting($meeting)) {
            $meeting->active = !$meeting->active;
            $meeting->store();
        }

        $this->redirect(PluginEngine::getURL($this->plugin, array(), 'index'));
    }

    public function rename_action($meetingId)
    {
        $meeting = new Meeting($meetingId);
        $name = Request::get('name');

        if (!$meeting->isNew() && $this->userCanModifyMeeting($meeting) && $name) {
            $meeting = new Meeting($meetingId);
            $meeting->name = $name;
            $meeting->store();
        }

        $this->redirect(PluginEngine::getURL($this->plugin, array(), 'index'));
    }

    public function moderator_permissions_action($meetingId)
    {
        $meeting = new Meeting($meetingId);

        if (!$meeting->isNew() && $this->userCanModifyMeeting($meeting)) {
            $meeting->join_as_moderator = !$meeting->join_as_moderator;
            $meeting->store();
        }

        $this->redirect(PluginEngine::getURL($this->plugin, array(), 'index'));
    }

    public function delete_action($meetingId)
    {
        $meeting = new Meeting($meetingId);

        if (!$meeting->isNew() && $this->userCanModifyMeeting($meeting)) {
            $parameters = $meeting->getMeetingParameters();
            $this->driver->deleteMeeting($parameters);
            $meeting->delete();
        }

        $this->redirect(PluginEngine::getURL($this->plugin, array(), 'index'));
    }

    /**
     *  redirects to active BBB meeting.
     */
    public function joinMeeting_action($meetingId)
    {
        if(!$this->hasActiveMeeting()) {
            $this->redirect(PluginEngine::getURL($this->plugin, array(), 'index'));
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

        if ($this->userCanModifyMeeting($meeting) || $meeting->join_as_moderator) {
            $joinParameters->setPassword($this->generateModeratorPassword());
            $joinParameters->setHasModerationPermissions(true);
        } else {
            $joinParameters->setPassword($this->generateAttendeePassword());
            $joinParameters->setHasModerationPermissions(false);
        }

        $lastJoin = new Join();
        $lastJoin->meeting_id = $meetingId;
        $lastJoin->user_id = $user->cfg->getUserId();
        $lastJoin->store();

        $this->redirect($this->driver->getJoinMeetingUrl($joinParameters));
    }

    public function saveConfig_action()
    {
        if (!$this->perm->have_perm('root')) {
            die;
        }

        Config::get()->store('BBB_URL', Request::get('bbb_url'));
        Config::get()->store('BBB_SALT', Request::get('bbb_salt'));

        $this->redirect(PluginEngine::getLink($this->plugin, array(), 'index'));
    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * */
    /* * * * * H E L P E R   F U N C T I O N S * * * * */
    /* * * * * * * * * * * * * * * * * * * * * * * * * */

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
     * @param string $name
     *
     * @return bool
     */
    private function createMeeting($name)
    {
        /** @var \Seminar_User $user */
        global $user;

        $meeting = new Meeting();
        $meeting->course_id = $this->getCourseId();
        $meeting->user_id = $user->cfg->getUserId();
        $meeting->name = $name;
        $meeting->driver = $this->driver->getName();
        $meeting->attendee_password = $this->generateAttendeePassword();
        $meeting->moderator_password = $this->generateModeratorPassword();
        $meeting->store();
        $meetingParameters = $meeting->getMeetingParameters();

        if (!$this->driver->createMeeting($meetingParameters)) {
            return false;
        }

        $meeting->remote_id = $meetingParameters->getRemoteId();
        $meeting->store();

        return true;
    }

    private function hasActiveMeeting()
    {
        $meetings = Meeting::findByCourseId($this->getCourseId());

        return count($meetings) > 0 && $this->driver->isMeetingRunning($meetings[0]->getMeetingParameters());
    }

    private function userCanModifyCourse($courseId)
    {
        return $this->perm->have_studip_perm('tutor', $courseId);
    }

    private function userCanModifyMeeting(Meeting $meeting)
    {
        return $this->userCanModifyCourse($meeting->course_id);
    }

    private function generateModeratorPassword()
    {
        return md5($this->getCourseId().'modPw');
    }

    private function generateAttendeePassword()
    {
        return md5($this->getCourseId().'attPw');
    }
}
