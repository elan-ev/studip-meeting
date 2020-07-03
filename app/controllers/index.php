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
        $this->cid = Context::getId();
    }

    public function config_action()
    {
        Navigation::activateItem('course/'.MeetingPlugin::NAVIGATION_ITEM_NAME .'/config');
        PageLayout::setTitle(self::getHeaderLine(Context::getId()));
        $this->getHelpbarContent('config');
        $cid = Context::getId();

        if (!$this->perm->have_studip_perm('tutor', $cid)) {
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

    private function getHelpbarContent($id)
    {
        /** @var \Helpbar $helpBar */

        switch ($id) {

            case 'main':
                $helpText = $this->_('Durchführung und Verwaltung von Live-Online-Treffen, Veranstaltungen und Videokonferenzen. '
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
