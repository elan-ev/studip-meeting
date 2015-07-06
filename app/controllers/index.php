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
use ElanEv\Model\CourseConfig;
use ElanEv\Model\Join;
use ElanEv\Model\Meeting;
use ElanEv\Model\MeetingCourse;

/**
 * @property \MeetingPlugin         $plugin
 * @property bool                   $configured
 * @property \Seminar_Perm          $perm
 * @property \Flexi_TemplateFactory $templateFactory
 * @property CourseConfig           $courseConfig
 * @property bool                   $confirmDeleteMeeting
 * @property bool                   $saved
 * @property string[]               $questionOptions
 * @property bool                   $canModifyCourse
 * @property array                  $errors
 * @property \Semester[]            $semesters
 * @property Meeting[]              $meetings
 * @property Meeting[]              $userMeetings
 * @property CourseConfig           $config
 * @property string                 $deleteAction
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

        PageLayout::addScript($this->plugin->getAssetsUrl().'/js/jquery.tablesorter.min.js');
        PageLayout::addScript($this->plugin->getAssetsUrl().'/js/meetings.js');
        PageLayout::addStylesheet($this->plugin->getAssetsUrl().'/css/meetings.css');

        if ($action !== 'my' && Navigation::hasItem('course/'.MeetingPlugin::NAVIGATION_ITEM_NAME)) {
            Navigation::activateItem('course/'.MeetingPlugin::NAVIGATION_ITEM_NAME);
            /** @var Navigation $navItem */
            $navItem = Navigation::getItem('course/'.MeetingPlugin::NAVIGATION_ITEM_NAME);
            $navItem->setImage('icons/16/black/chat.png');
        } elseif ($action === 'my' && Navigation::hasItem('/profile/meetings')) {
            Navigation::activateItem('/profile/meetings');
        }

        $this->courseConfig = CourseConfig::findByCourseId($this->getCourseId());
    }

    public function index_action()
    {
        PageLayout::setTitle(getHeaderLine($this->getCourseId()) .' - '. _('Meetings (Betatest)'));
        $this->getHelpbarContent('main');
        
        /** @var \Seminar_User $user */
        $user = $GLOBALS['user'];
        $course = new Course($this->getCourseId());
        $this->errors = array();
        $this->deleteAction = PluginEngine::getURL($this->plugin, array(), 'index', true);
        $this->handleDeletion();

        if (Request::get('action') === 'create' && $this->userCanModifyCourse($this->getCourseId())) {
            if (!Request::get('name')) {
                $this->errors[] = _('Bitte geben Sie dem Meeting einen Namen.');
            }

            if (count($this->errors) === 0) {
                $this->createMeeting(\Request::get('name'));
            }
        }

        if (Request::get('action') === 'link' && $this->userCanModifyCourse($this->getCourseId())) {
            $linkedMeetingId = Request::int('meeting_id');
            $meeting = new Meeting($linkedMeetingId);

            if (!$meeting->isNew() && $user->cfg->getUserId() === $meeting->user_id && !$meeting->isAssignedToCourse($course)) {
                $meeting->courses[] = new \Course($this->getCourseId());
                $meeting->store();
            }
        }

        $this->canModifyCourse = $this->userCanModifyCourse($this->getCourseId());

        if ($this->canModifyCourse) {
            $this->buildSidebar(
                array(array(
                    'label' => $this->courseConfig->title,
                    'url' => PluginEngine::getLink($this->plugin, array(), 'index'),
                )),
                array(array(
                    'label' => _('Informationen anzeigen'),
                    'url' => '#',
                    'icon' => 'icons/16/blue/info-circle.png',
                    'attributes' => array(
                        'class' => 'toggle-info show-info',
                        'data-show-text' => _('Informationen anzeigen'),
                        'data-hide-text' => _('Informationen ausblenden'),
                    ),
                )),
                array(array(
                    'label' => _('Anpassen'),
                    'url' => PluginEngine::getLink($this->plugin, array(), 'index/config'),
                    'icon' => 'icons/16/blue/admin.png',
                ))
            );
        } else {
            $this->buildSidebar(array(array(
                    'label' => $this->courseConfig->title,
                    'url' => PluginEngine::getLink($this->plugin, array(), 'index'),
            )));
        }

        if ($this->canModifyCourse) {
            $this->meetings = MeetingCourse::findByCourseId($this->getCourseId());
            $this->userMeetings = MeetingCourse::findLinkableByUser($user, $course);
        } else {
            $this->meetings = MeetingCourse::findActiveByCourseId($this->getCourseId());
            $this->userMeetings = array();
        }
    }

    public function my_action($type = null)
    {
        global $user;

        PageLayout::setTitle(_('Meine Meetings'));
        $this->getHelpbarContent('my');
        $this->deleteAction = PluginEngine::getURL($this->plugin, array(), 'index/my', true);
        $this->handleDeletion();

        if ($type === 'name') {
            $this->type = 'name';
            $viewItem = array(
                'label' => _('Anzeige nach Semester'),
                'url' => PluginEngine::getLink($this->plugin, array(), 'index/my'),
                'active' => $type !== 'name',
            );
            $this->meetings = MeetingCourse::findByUser($user);
        } else {
            $viewItem = array(
                'label' => _('Anzeige nach Namen'),
                'url' => PluginEngine::getLink($this->plugin, array(), 'index/my/name'),
                'active' => $type === 'name',
            );
            $this->buildMeetingBlocks(MeetingCourse::findByUser($user));
        }

        $this->buildSidebar(
            array(),
            array(
                $viewItem,
                array(
                    'label' => _('Informationen anzeigen'),
                    'url' => '#',
                    'icon' => 'icons/16/blue/info-circle.png',
                    'attributes' => array(
                        'class' => 'toggle-info show-info',
                        'data-show-text' => _('Informationen anzeigen'),
                        'data-hide-text' => _('Informationen ausblenden'),
                    ),
                )
            )
        );
    }

    public function all_action($type = null)
    {
        if (!$GLOBALS['perm']->have_perm('root')) {
            throw new AccessDeniedException(_('Sie brauchen Administrationsrechte.'));
        }
        if (Navigation::hasItem('/admin/locations/meetings')) {
            Navigation::activateItem('/admin/locations');
        } elseif (Navigation::hasItem('/meetings')) {
            Navigation::activateItem('/meetings');
        }

        PageLayout::setTitle(_('Alle Meetings'));

        $this->deleteAction = PluginEngine::getURL($this->plugin, array(), 'index/all', true);
        $this->handleDeletion();

        if ($type === 'name') {
            $this->type = 'name';
            $viewItem = array(
                'label' => _('Anzeige nach Semester'),
                'url' => PluginEngine::getLink($this->plugin, array(), 'index/all'),
                'active' => $type !== 'name',
            );
            $this->meetings = MeetingCourse::findAll();
        } else {
            $viewItem = array(
                'label' => _('Anzeige nach Namen'),
                'url' => PluginEngine::getLink($this->plugin, array(), 'index/all/name'),
                'active' => $type === 'name',
            );
            $this->buildMeetingBlocks(MeetingCourse::findAll());
        }

        $this->buildSidebar(
            array(),
            array(
                $viewItem,
                array(
                    'label' => _('Informationen anzeigen'),
                    'url' => '#',
                    'icon' => 'icons/16/blue/info-circle.png',
                    'attributes' => array(
                        'class' => 'toggle-info show-info',
                        'data-show-text' => _('Informationen anzeigen'),
                        'data-hide-text' => _('Informationen ausblenden'),
                    ),
                )
            )
        );
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

    public function enable_action($meetingId, $courseId)
    {
        $meeting = new MeetingCourse(array($meetingId, $courseId));

        if (!$meeting->isNew() && $this->userCanModifyCourse($meeting->course->id)) {
            $meeting->active = !$meeting->active;
            $meeting->store();
        }

        $this->redirect(PluginEngine::getURL($this->plugin, array(), Request::get('destination')));
    }

    public function edit_action($meetingId)
    {
        $meeting = new Meeting($meetingId);
        $name = utf8_decode(Request::get('name'));
        $recordingUrl = utf8_decode(Request::get('recording_url'));

        if (!$meeting->isNew() && $this->userCanModifyCourse($this->getCourseId()) && $name) {
            $meeting = new Meeting($meetingId);
            $meeting->name = $name;
            $meeting->recording_url = $recordingUrl;
            $meeting->store();
        }

        $this->redirect(PluginEngine::getURL($this->plugin, array(), 'index'));
    }

    public function moderator_permissions_action($meetingId)
    {
        $meeting = new Meeting($meetingId);

        if (!$meeting->isNew() && $this->userCanModifyCourse($this->getCourseId())) {
            $meeting->join_as_moderator = !$meeting->join_as_moderator;
            $meeting->store();
        }

        $this->redirect(PluginEngine::getURL($this->plugin, array(), Request::get('destination')));
    }

    public function delete_action($meetingId, $courseId)
    {
        $this->deleteMeeting($meetingId, $courseId);

        if (Request::get('cid') !== null) {
            $destination = 'index';
        } else {
            $destination = 'index/my';
        }

        $this->redirect(PluginEngine::getURL($this->plugin, array(), $destination));
    }

    /**
     *  redirects to active BBB meeting.
     */
    public function joinMeeting_action($meetingId)
    {
        if(!$this->hasActiveMeeting()) {
            $this->redirect(PluginEngine::getURL($this->plugin, array(), 'index'));
            return;
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

        if ($this->userCanModifyCourse($this->getCourseId()) || $meeting->join_as_moderator) {
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

    public function config_action()
    {
        PageLayout::setTitle(getHeaderLine($this->getCourseId()) .' - '. _('Meetings'));
        $this->getHelpbarContent('config');
        $courseId = $this->getCourseId();

        if (!$this->userCanModifyCourse($courseId)) {
            $this->redirect(PluginEngine::getURL($this->plugin, array(), 'index'));
        }

        if (Request::method() === 'POST') {
            $this->courseConfig->title = Request::get('title');
            $this->courseConfig->introduction = Request::get('introduction');
            $this->courseConfig->store();
            $this->saved = true;

            $this->redirect(PluginEngine::getURL($this->plugin, array(), 'index/config'));
        }

        $this->buildSidebar(
            array(array(
                'label' => $this->courseConfig->title,
                'url' => PluginEngine::getLink($this->plugin, array(), 'index'),
            )),
            array(),
            array(array(
                'label' => _('Anpassen'),
                'url' => PluginEngine::getLink($this->plugin, array(), 'index/config'),
                'icon' => 'icons/16/blue/admin.png',
            ))
        );
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
        $meeting->courses[] = new Course($this->getCourseId());
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
        $meetings = MeetingCourse::findByCourseId($this->getCourseId());

        return count($meetings) > 0 && $this->driver->isMeetingRunning($meetings[0]->meeting->getMeetingParameters());
    }

    private function userCanModifyCourse($courseId)
    {
        return $this->perm->have_studip_perm('tutor', $courseId);
    }

    private function generateModeratorPassword()
    {
        return md5($this->getCourseId().'modPw');
    }

    private function generateAttendeePassword()
    {
        return md5($this->getCourseId().'attPw');
    }

    private function buildSidebar($navigationItems = array(), $viewsItems = array(), $actionsItems = array())
    {
        $sidebar = Sidebar::Get();

        $sections = array(
            array(
                'label' => _('Navigation'),
                'class' => 'sidebar-meeting-navigation',
                'items' => $navigationItems,
            ),
            array(
                'label' => _('Ansichten'),
                'class' => 'sidebar-meeting-views',
                'items' => $viewsItems,
            ),
            array(
                'label' => _('Aktionen'),
                'class' => 'sidebar-meeting-actions',
                'items' => $actionsItems,
            ),
        );

        foreach ($sections as $section) {
            if (count($section['items']) > 0) {
                $navigation = new ActionsWidget();
                $navigation->addCSSClass($section['class']);
                $navigation->setTitle($section['label']);

                foreach ($section['items'] as $item) {
                    $link = $navigation->addLink(
                        $item['label'],
                        $item['url'],
                        isset($item['icon']) ? $item['icon'] : null,
                        isset($item['attributes']) && is_array($item['attributes']) ? $item['attributes'] : array()
                    );

                    if (isset($item['active']) && $item['active']) {
                        $link->setActive(true);
                    }
                }

                $sidebar->addWidget($navigation);
            }
        }
    }

    private function buildMeetingBlocks(array $meetingCourses)
    {
        $this->semesters = array();
        $this->meetings = array();

        foreach ($meetingCourses as $meetingCourse) {
            $semester = $meetingCourse->course->start_semester;

            if ($semester === null) {
                $now = new \DateTime();
                $semester = \Semester::findByTimestamp($now->getTimestamp());
            }

            if (!isset($this->semesters[$semester->id])) {
                $this->semesters[$semester->id] = $semester;
                $this->meetings[$semester->id] = array();
            }

            $this->meetings[$semester->id][] = $meetingCourse;
        }

        usort($this->semesters, function ($semester1, $semester2) {
            return $semester2->beginn - $semester1->beginn;
        });
    }

    private function handleDeletion()
    {
        if (Request::get('action') === 'multi-delete') {
            $this->handleMultiDeletion();
        } elseif (Request::get('delete') > 0 && Request::get('cid')) {
            $meeting = new Meeting(Request::get('delete'));

            if (!$meeting->isNew()) {
                $this->confirmDeleteMeeting = true;
                $this->questionOptions = array(
                    'question' => _('Wollen Sie wirklich das Meeting "').$meeting->name._('" löschen?'),
                    'approvalLink' => PluginEngine::getLink($this->plugin, array(), 'index/delete/'.$meeting->id.'/'.Request::get('cid'), true),
                    'disapprovalLink' => PluginEngine::getLink($this->plugin, array(),  Request::get('destination')),
                );
            }
        }
    }

    private function handleMultiDeletion()
    {
        $deleteMeetings = array();
        foreach (Request::getArray('meeting_ids') as $deleteMeetingsId) {
            list($meetingId, $courseId) = explode('-', $deleteMeetingsId);
            $meetingCourse = new MeetingCourse(array($meetingId, $courseId));
            if (!$meetingCourse->isNew()) {
                $deleteMeetings[] = $meetingCourse;
            }
        }

        if (Request::submitted('confirm')) {
            foreach ($deleteMeetings as $meetingCourse) {
                $this->deleteMeeting($meetingCourse->meeting->id, $meetingCourse->course->id);
            }
        } elseif (!Request::submitted('cancel')) {
            $this->confirmDeleteMeeting = true;
            $this->questionOptions = array(
                'question' => _('Wollen Sie folgende Meetings wirklich löschen?'),
                'approvalLink' => PluginEngine::getLink($this->plugin, array(), 'index/delete/'.$meeting->id.'/'.Request::get('cid')),
                'disapprovalLink' => PluginEngine::getLink($this->plugin, array(), Request::get('destination')),
                'deleteMeetings' => $deleteMeetings,
                'destination' => $this->deleteAction,
            );
        }
    }

    private function deleteMeeting($meetingId, $courseId)
    {
        $meetingCourse = new MeetingCourse(array($meetingId, $courseId));

        if (!$meetingCourse->isNew() && $this->userCanModifyCourse($meetingCourse->course->id)) {
            // don't associate the meeting and the course any more
            $meetingId = $meetingCourse->meeting->id;
            $meetingCourse->delete();

            $meeting = new Meeting($meetingId);

            // if the meeting isn't associated with at least one course at all,
            // it can be removed entirely
            if (count($meeting->courses) === 0) {
                $meeting->delete();
            }
        }
    }
    
    private function getHelpbarContent($id)
    {
    	/** @var \Helpbar $helpBar */
    
    	switch ($id) {
    
    		case 'main':
    			$helpText = _('Durchführung und Verwaltung von Live-Online-Treffen, Webinaren und Videokonferenzen. ').
    			_('Mit Hilfe der Face-to-Face-Kommunikation können Entfernungen überbrückt, externe Fachleute ').
    			_('einbezogen und Studierende in Projekten und Praktika begleitet werden.');
    			$helpBar = Helpbar::get();
    			$helpBar->addPlainText(_(''), $helpText);
    			break;
    
    		case 'config':
    			$helpText = _('Auf dieser Seite können Sie den Reiternamen von Meetings ändern und der Meeting-Liste ').
    			_('einen Text hinzufügen.');
    			$helpBar = Helpbar::get();
    			$helpBar->addPlainText(_(''), $helpText);
    			break;
    
    		case 'my':
    			$helpText = _('Die Seite zeigt eine Gesamtansicht aller von Ihnen erstellten Meetings nach Semestern ').
    			_('oder nach Namen sortiert.');
    			$helpBar = Helpbar::get();
    			$helpBar->addPlainText(_(''), $helpText);
    			break;
    	}
    }
}
