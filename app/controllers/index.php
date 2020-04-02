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

use ElanEv\Driver\DriverFactory;
use ElanEv\Driver\JoinParameters;
use ElanEv\Model\CourseConfig;
use ElanEv\Model\Join;
use ElanEv\Model\Meeting;
use ElanEv\Model\MeetingCourse;
use ElanEv\Model\Driver;
use ElanEv\Model\Helper;

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
class IndexController extends MeetingsController
{
    /**
     * @var ElanEv\Driver\DriverInterface
     */
    private $driver;

    public function __construct($dispatcher)
    {
        parent::__construct($dispatcher);

        $this->perm = $GLOBALS['perm'];
        $this->driver_config = Driver::getConfig();
        $this->driver_factory = new DriverFactory(Driver::getConfig());

        $this->configured = false;
        if (!empty($this->driver_config)) {
            foreach ($this->driver_config as $driver => $config) {
                if ($config['enable']) {
                    $this->configured = true;
                } else {
                    unset($this->driver_config[$driver]);
                }
            }
        }

        $this->plugin = $dispatcher->current_plugin;

        // Localization
        $this->_ = function ($string) use ($dispatcher) {
            return call_user_func_array(
                [$dispatcher->current_plugin, '_'],
                func_get_args()
            );
        };

        $this->_n = function ($string0, $tring1, $n) use ($dispatcher) {
            return call_user_func_array(
                [$dispatcher->current_plugin, '_n'],
                func_get_args()
            );
        };
    }

    /**
     * Intercepts all non-resolvable method calls in order to correctly handle
     * calls to _ and _n.
     *
     * @param string $method
     * @param array  $arguments
     * @return mixed
     * @throws RuntimeException when method is not found
     */
    public function __call($method, $arguments)
    {
        $variables = get_object_vars($this);
        if (isset($variables[$method]) && is_callable($variables[$method])) {
            return call_user_func_array($variables[$method], $arguments);
        }
        throw new RuntimeException("Method {$method} does not exist");
    }


    /**
     * {@inheritdoc}
     */
    function before_filter(&$action, &$args)
    {
        $this->validate_args($args, ['option', 'option']);

        parent::before_filter($action, $args);

        // set default layout
        $this->templateFactory = $GLOBALS['template_factory'];
        $layout = $this->templateFactory->open('layouts/base');
        $this->set_layout($layout);

        PageLayout::setHelpKeyword('Basis.Meetings');

        if ($action !== 'my' && $action != 'config' && Navigation::hasItem('course/'.MeetingPlugin::NAVIGATION_ITEM_NAME)) {
            Navigation::activateItem('course/'.MeetingPlugin::NAVIGATION_ITEM_NAME .'/meetings');
            /** @var Navigation $navItem */
            $navItem = Navigation::getItem('course/'.MeetingPlugin::NAVIGATION_ITEM_NAME);
            $navItem->setImage(MeetingPlugin::getIcon('chat', 'black'));
        } elseif ($action === 'my' && Navigation::hasItem('/meetings')) {
            Navigation::activateItem('/meetings');
        }

        $this->courseConfig = CourseConfig::findByCourseId(Context::getId());

        libxml_use_internal_errors(true);

        $this->flash = Trails_Flash::instance();
    }

    public function index_action()
    {
        PageLayout::setTitle(self::getHeaderLine(Context::getId()));
        $this->getHelpbarContent('main');

        /** @var \Seminar_User $user */
        $user = $GLOBALS['user'];
        $course = new Course(Context::getId());

        $this->errors = $this->flash['errors'] ?: [];

        $this->deleteAction = PluginEngine::getURL($this->plugin, ['cid' => Context::getId()], 'index', true);
        $this->handleDeletion();

        if (Request::get('action') === 'link' && $this->userCanModifyCourse(Context::getId())) {
            $linkedMeetingId = Request::int('meeting_id');
            $meeting = new Meeting($linkedMeetingId);

            if (!$meeting->isNew() && $user->id === $meeting->user_id && !$meeting->isAssignedToCourse($course)) {
                $meeting->courses[] = new \Course(Context::getId());
                $meeting->store();
            }
        }

        $this->canModifyCourse = $this->userCanModifyCourse(Context::getId());

        if ($this->canModifyCourse) {
            $this->buildSidebar(
                [[
                    'label' => $this->courseConfig->title,
                    'url' => PluginEngine::getLink($this->plugin, [], 'index'),
                ]],
                [[
                    'label' => $this->_('Informationen anzeigen'),
                    'url' => '#',
                    'icon' => MeetingPlugin::getIcon('info-circle', 'blue'),
                    'attributes' => [
                        'class' => 'toggle-info show-info',
                        'data-show-text' => $this->_('Informationen anzeigen'),
                        'data-hide-text' => $this->_('Informationen ausblenden'),
                    ],
                ]]
            );
        } else {
            $this->buildSidebar([[
                    'label' => $this->courseConfig->title,
                    'url' => PluginEngine::getLink($this->plugin, [], 'index'),
            ]]);
        }

        if ($this->canModifyCourse) {
            $this->meetings = MeetingCourse::findByCourseId(Context::getId());
            $this->userMeetings = MeetingCourse::findLinkableByUser($user, $course);
        } else {
            $this->meetings = MeetingCourse::findActiveByCourseId(Context::getId());
            $this->userMeetings = [];
        }
    }

    public function create_action()
    {
        if ($this->userCanModifyCourse(Context::getId())) {
            if (!Request::get('name')) {
                $this->flash['errors'] = [$this->_('Bitte geben Sie dem Meeting einen Namen.')];
            } else {
                $this->createMeeting(\Request::get('name'), Request::get('driver'));
            }
        }

        $this->redirect('index/index');
    }

    public function my_action($type = null)
    {
        global $user;

        PageLayout::setTitle($this->_('Meine Meetings'));
        Navigation::activateItem('/profile/meetings');

        $this->getHelpbarContent('my');
        $this->deleteAction = PluginEngine::getURL($this->plugin, [], 'index/my', true);
        $this->handleDeletion();

        if ($type === 'name') {
            $this->type = 'name';
            $viewItem = [
                'label' => $this->_('Anzeige nach Semester'),
                'url' => PluginEngine::getLink($this->plugin, [], 'index/my'),
                'active' => $type !== 'name',
            ];
            $this->meetings = MeetingCourse::findByUser($user);
        } else {
            $viewItem = [
                'label' => $this->_('Anzeige nach Namen'),
                'url' => PluginEngine::getLink($this->plugin, [], 'index/my/name'),
                'active' => $type === 'name',
            ];
            $this->buildMeetingBlocks(MeetingCourse::findByUser($user));
        }

        $this->buildSidebar(
            [],
            [
                $viewItem,
                [
                    'label' => $this->_('Informationen anzeigen'),
                    'url' => '#',
                    'icon' => MeetingPlugin::getIcon('info-circle', 'blue'),
                    'attributes' => [
                        'class' => 'toggle-info show-info',
                        'data-show-text' => $this->_('Informationen anzeigen'),
                        'data-hide-text' => $this->_('Informationen ausblenden'),
                    ],
                ]
            ]
        );
    }

    public function all_action($type = null)
    {
        if (!$GLOBALS['perm']->have_perm('root')) {
            throw new AccessDeniedException($this->_('Sie brauchen Administrationsrechte.'));
        }

        if (Navigation::hasItem('/admin/locations/meetings')) {
            Navigation::activateItem('/admin/locations/meetings');
        } elseif (Navigation::hasItem('/meetings')) {
            Navigation::activateItem('/meetings');
        }

        PageLayout::setTitle($this->_('Alle Meetings'));

        $this->deleteAction = PluginEngine::getURL($this->plugin, [], 'index/all', true);
        $this->handleDeletion();

        if ($type === 'name') {
            $this->type = 'name';
            $viewItem = [
                'label' => $this->_('Anzeige nach Semester'),
                'url' => PluginEngine::getLink($this->plugin, [], 'index/all'),
                'active' => $type !== 'name',
            ];
            $this->meetings = MeetingCourse::findAll();
        } else {
            $viewItem = [
                'label' => $this->_('Anzeige nach Namen'),
                'url' => PluginEngine::getLink($this->plugin, [], 'index/all/name'),
                'active' => $type === 'name',
            ];
            $this->buildMeetingBlocks(MeetingCourse::findAll());
        }

        $this->buildSidebar(
            [],
            [
                $viewItem,
                [
                    'label' => $this->_('Informationen anzeigen'),
                    'url' => '#',
                    'icon' => MeetingPlugin::getIcon('info-circle', 'blue'),
                    'attributes' => [
                        'class' => 'toggle-info show-info',
                        'data-show-text' => $this->_('Informationen anzeigen'),
                        'data-hide-text' => $this->_('Informationen ausblenden'),
                    ],
                ]
            ]
        );
    }

    public function enable_action($meetingId, $courseId)
    {
        $meeting = new MeetingCourse([$meetingId, $courseId]);

        if (!$meeting->isNew() && $this->userCanModifyCourse($meeting->course->id)) {
            $meeting->active = !$meeting->active;
            $meeting->store();
        }

        $this->redirect(Request::get('destination'));
    }

    public function edit_action($meetingId)
    {
        $meeting = new Meeting($meetingId);
        $name = utf8_decode(Request::get('name'));
        $recordingUrl = utf8_decode(Request::get('recording_url'));

        if (!$meeting->isNew() && $this->userCanModifyCourse(Context::getId()) && $name) {
            $meeting = new Meeting($meetingId);
            $meeting->name = $name;
            $meeting->recording_url = $recordingUrl;
            $meeting->store();
        }

        if (!Request::isXhr()) {
            $this->redirect('index/index');
        } else {
            $this->render_nothing();
        }
    }

    public function moderator_permissions_action($meetingId)
    {
        $meeting = new Meeting($meetingId);

        if (!$meeting->isNew() && $this->userCanModifyCourse(Context::getId())) {
            $meeting->join_as_moderator = !$meeting->join_as_moderator;
            $meeting->store();
        }

        $this->redirect(Request::get('destination'));
    }

    public function delete_action($meetingId, $courseId)
    {
        $this->flash['messages'] = [
            'success' => ['Meeting wurde gelöscht.']
        ];

        $this->deleteMeeting($meetingId, $courseId);

        if (Request::get('destination') == 'index/my') {
            $destination = 'index/my';
        } else {
            $destination = 'index';
        }

        $this->redirect($destination);
    }

    /**
     *  redirects to active BBB meeting.
     */
    public function joinMeeting_action($meetingId)
    {
        /** @var Seminar_User $user */
        $user = $GLOBALS['user'];

        $meeting = new Meeting($meetingId);
        $driver = $this->driver_factory->getDriver($meeting->driver);

        // ugly hack for BBB
        if ($driver instanceof ElanEv\Driver\BigBlueButton) {
            // TODO: check if recreation is necessary
            $meetingParameters = $meeting->getMeetingParameters();
            $driver->createMeeting($meetingParameters);
        }

        $joinParameters = new JoinParameters();
        $joinParameters->setMeetingId($meetingId);
        $joinParameters->setIdentifier($meeting->identifier);
        $joinParameters->setRemoteId($meeting->remote_id);
        $joinParameters->setUsername(get_username($user->id));
        $joinParameters->setEmail($user->Email);
        $joinParameters->setFirstName($user->Vorname);
        $joinParameters->setLastName($user->Nachname);



        if ($this->userCanModifyCourse(Context::getId()) || $meeting->join_as_moderator) {
            $joinParameters->setPassword($meeting->moderator_password);
            $joinParameters->setHasModerationPermissions(true);
        } else {
            $joinParameters->setPassword($meeting->attendee_password);
            $joinParameters->setHasModerationPermissions(false);
        }

        $lastJoin = new Join();
        $lastJoin->meeting_id = $meetingId;
        $lastJoin->user_id = $user->id;
        $lastJoin->last_join = time();
        $lastJoin->store();


        try {
            if ($join_url = $driver->getJoinMeetingUrl($joinParameters)) {
                $this->redirect($driver->getJoinMeetingUrl($joinParameters));
            } else {
                $this->flash['messages'] = ['error' => ['Konnte dem Meeting nicht beitreten, Kommunikation mit dem Meeting-Server fehlgeschlagen.']];
                $this->redirect('index/index');
            }
        } catch (Exception $e) {
            $this->flash['messages']= ['error' => ['Konnte dem Meeting nicht beitreten, Kommunikation mit dem Meeting-Server fehlgeschlagen. ('. $e->getMessage() .')']];
            $this->redirect('index/index');
        }
    }

    public function config_action()
    {
        Navigation::activateItem('course/'.MeetingPlugin::NAVIGATION_ITEM_NAME .'/config');
        PageLayout::setTitle(self::getHeaderLine(Context::getId()));
        $this->getHelpbarContent('config');
        $courseId = Context::getId();

        if (!$this->userCanModifyCourse($courseId)) {
            $this->redirect('index/index');
        }

        if (Request::method() === 'POST') {
            $this->courseConfig->title = Request::get('title');
            $this->courseConfig->introduction = Request::get('introduction');
            $this->courseConfig->store();
            $this->saved = true;

            $this->redirect('index/config');
        }

        $this->buildSidebar(
            [[
                'label' => $this->courseConfig->title,
                'url' => $this->url_for('index/index'),
            ]],
            []
        );
    }

    public function saveConfig_action()
    {
        if ($GLOBALS['perm']->have_perm('root')) {
            foreach (Request::getArray('config') as $option => $data) {
                Config::get()->store($option, $data);
            }
        } else {
            throw new AccessDeniedException('You need to be root to perform this action!');
        }

        $this->redirect('index/index');
    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * */
    /* * * * * H E L P E R   F U N C T I O N S * * * * */
    /* * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * @param string $name
     * @param string $driver_name
     *
     * @return bool
     */
    private function createMeeting($name, $driver_name)
    {
        /** @var \Seminar_User $user */
        global $user;

        $meeting = new Meeting();
        $meeting->courses[] = new Course(Context::getId());
        $meeting->user_id = $user->id;
        $meeting->name = $name;
        $meeting->driver = $driver_name;
        $meeting->attendee_password = $this->generateAttendeePassword();
        $meeting->moderator_password = $this->generateModeratorPassword();
        $meeting->remote_id = md5(uniqid());
        $meeting->store();
        $meetingParameters = $meeting->getMeetingParameters();

        $driver = $this->driver_factory->getDriver($driver_name);

        try {
            if (!$driver->createMeeting($meetingParameters)) {
                return false;
            }
        } catch (Exception $e) {
            $this->flash['messages'] = ['error' => [$e->getMessage()]];
            return false;
        }

        $meeting->remote_id = $meetingParameters->getRemoteId();
        $meeting->store();

        return true;
    }

    private function userCanModifyCourse($courseId)
    {
        return $this->perm->have_studip_perm('tutor', $courseId);
    }

    private function generateModeratorPassword()
    {
        return Helper::createPassword();
    }

    private function generateAttendeePassword()
    {
        return Helper::createPassword();
    }

    private function buildSidebar($navigationItems = [], $actionsItems = [])
    {
        $sidebar = Sidebar::Get();

        $sections = [
            [
                'label' => $this->_('Aktionen'),
                'class' => 'sidebar-meeting-actions',
                'items' => $actionsItems,
            ],
        ];

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
                        isset($item['attributes']) && is_array($item['attributes']) ? $item['attributes'] : []
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
        $this->semesters = [];
        $this->meetings  = [];

        foreach ($meetingCourses as $meetingCourse) {
            $semester = $meetingCourse->course->start_semester;

            if ($semester === null) {
                $now = new \DateTime();
                $semester = \Semester::findByTimestamp($now->getTimestamp());
            }

            if (!isset($this->semesters[$semester->id])) {
                $this->semesters[$semester->id] = $semester;
                $this->meetings[$semester->id] = [];
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
                $this->questionOptions = [
                    'question' => sprintf(
                        $this->_('Wollen Sie wirklich das Meeting "%s" löschen?'),
                        $meeting->name
                    ),
                    'approvalLink' => PluginEngine::getLink($this->plugin, [
                        'destination' => Request::get('destination')
                    ], 'index/delete/'.$meeting->id .'/'. Request::get('cid') .'?cid='. Request::get('cid'), true),
                    'disapprovalLink' => PluginEngine::getLink($this->plugin, [],  Request::get('destination')),
                ];
            }
        }
    }

    private function handleMultiDeletion()
    {
        $deleteMeetings = [];
        foreach (Request::getArray('meeting_ids') as $deleteMeetingsId) {
            list($meetingId, $courseId) = explode('-', $deleteMeetingsId);
            $meetingCourse = new MeetingCourse([$meetingId, $courseId]);
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
            $this->questionOptions = [
                'question'        => $this->_('Wollen Sie folgende Meetings wirklich löschen?'),
                'approvalLink'    => PluginEngine::getLink($this->plugin, [], 'index/delete/'.$meeting->id.'/'.Request::get('cid')),
                'disapprovalLink' => PluginEngine::getLink($this->plugin, [], Request::get('destination')),
                'deleteMeetings'  => $deleteMeetings,
                'destination'     => $this->deleteAction,
            ];
        }
    }

    private function deleteMeeting($meetingId, $courseId)
    {
        $meetingCourse = new MeetingCourse([$meetingId, $courseId]);

        if (!$meetingCourse->isNew() && $this->userCanModifyCourse($meetingCourse->course->id)) {
            // don't associate the meeting and the course any more
            $meetingId = $meetingCourse->meeting->id;
            $meetingCourse->delete();

            $meeting = new Meeting($meetingId);

            // if the meeting isn't associated with at least one course at all,
            // it can be removed entirely
            if (count($meeting->courses) === 0) {
                // inform the driver to delete the meeting as well
                $driver = $this->driver_factory->getDriver($meeting->driver);
                try {
                    $driver->deleteMeeting($meeting->getMeetingParameters());
                } catch (Exception $e) {
                    $this->flash['messages'] = ['error' => [$e->getMessage()]];
                }

                $meeting->delete();
            }
        }
    }

    private function getHelpbarContent($id)
    {
        /** @var \Helpbar $helpBar */

        switch ($id) {

            case 'main':
                $helpText = $this->_('Durchführung und Verwaltung von Live-Online-Treffen, Webinaren und Videokonferenzen. '
                          . 'Mit Hilfe der Face-to-Face-Kommunikation können Entfernungen überbrückt, externe Fachleute '
                          . 'einbezogen und Studierende in Projekten und Praktika begleitet werden.');
                $helpBar = Helpbar::get();
                $helpBar->addPlainText('', $helpText);
                break;

            case 'config':
                $helpText = $this->_('Arbeitsbereich zum Anpassen der Gesamtansicht der Meetings einer Veranstaltung.');
                $helpBar = Helpbar::get();
                $helpBar->addPlainText('', $helpText);
                break;

            case 'my':
                $helpText = $this->_('Gesamtansicht aller von Ihnen erstellten Meetings nach '
                          . 'Semestern oder nach Namen sortiert.');
                $helpBar = Helpbar::get();
                $helpBar->addPlainText('', $helpText);
                break;
        }
    }

    /**
     * Get pagetitle taking care of different Stud.IP versions
     *
     * @return string  pagetitle
     */
    private static function getHeaderLine($course_id)
    {
        if (function_exists('getHeaderLine')) {
            return getHeaderLine($course_id) .' - '. dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Meetings');
        } else {
            return Context::getHeaderLine() .' - '. dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Meetings');
        }
    }
}
