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
use Meetings\Helpers\RoomManager;
use Meetings\Models\I18N;

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
        $variables = method_exists($this, 'get_assigned_variables') ? $this->get_assigned_variables() : get_object_vars($this);
        if (isset($variables[$method]) && is_callable($variables[$method])) {
            return call_user_func_array($variables[$method], $arguments);
        }
        return parent::__call($method, $arguments);
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

        if ($action !== 'my' && $action != 'config' && $action != 'intros' && Navigation::hasItem('course/'.MeetingPlugin::NAVIGATION_ITEM_NAME)) {
            Navigation::activateItem('course/'.MeetingPlugin::NAVIGATION_ITEM_NAME .'/meetings');
            /** @var Navigation $navItem */
            $navItem = Navigation::getItem('course/'.MeetingPlugin::NAVIGATION_ITEM_NAME);
            $navItem->setImage(MeetingPlugin::getIcon('chat', 'black'));
        } elseif ($action === 'my' && Navigation::hasItem('/meetings')) {
            Navigation::activateItem('/meetings');
        }

        $this->courseConfig = CourseConfig::findByCourseId(Context::getId());
        $this->introductions = (array) json_decode($this->courseConfig->introductions);

        libxml_use_internal_errors(true);

        $this->flash = Trails_Flash::instance();
    }

    /**
     * Main action to display meetings
     */
    public function index_action()
    {
        PageLayout::addScript($this->plugin->getAssetsUrl() . '/js/meetings_return_helper.js?v=' . MeetingPlugin::getMeetingManifestInfo('version'));
        PageLayout::setTitle(self::getHeaderLine(Context::getId()));
        $this->getHelpbarContent('main');
        $this->cid = Context::getId();
        if ($err = Request::get('err')) {
            if ($err == 'server-inactive') {
                PageLayout::postError(I18N::_('Der ausgewählte Server ist deaktiviert.'));
            }
            if ($err == 'course-type') {
                PageLayout::postError(I18N::_('Der ausgewählte Server ist in diesem Veranstaltungstyp nicht verfügbar.'));
            }
            if ($err == 'accessdenied') {
                throw new AccessDeniedException();
            }
        }

        $this->setSidebar();

        // Here we handle the path to be routed when the plugin is used for public courses.
        if (MeetingPlugin::isCoursePublic($this->cid)) {
            $this->is_public = true;
        }

        $studip_version = \StudipVersion::getStudipVersion();
        if (empty($studip_version)) {
            $manifest = MeetingPlugin::getMeetingManifestInfo();
            $studip_version = isset($manifest["studipMinVersion"]) ? $manifest["studipMinVersion"] : 4;
        }

        $this->studip_version = floatval($studip_version);
    }

    /**
     * Page customization - main action for the plugin course config setting.
     */
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
            CSRFProtection::verifyRequest();
            $this->courseConfig->title = Request::get('title');
            $this->courseConfig->store();

            PageLayout::postSuccess(I18N::_('Die Änderungen wurden gespeichert.'));
            $this->redirect('index/config');
        }

        $this->setSidebar('course-config', 'config');
    }

    /**
     * Page customization - main action for the plugin course config introductions table.
     */
    public function intros_action()
    {
        Navigation::activateItem('course/'.MeetingPlugin::NAVIGATION_ITEM_NAME .'/config');
        PageLayout::setTitle(self::getHeaderLine(Context::getId()));
        $this->getHelpbarContent('config');
        $cid = Context::getId();

        if (!$this->perm->have_studip_perm('tutor', $cid)) {
            $this->redirect('index/index');
        }

        $this->setSidebar('course-config', 'intros');
    }

    /**
     * Page customization - action for adding new course config introduction.
     * This action is meant to be called by dialog.
     */
    public function add_intro_action()
    {
        PageLayout::setTitle(I18N::_('Einleitung hinzufügen'));
        $cid = Context::getId();
        $this->is_new = true;
        if (Request::submitted('store')) {
            CSRFProtection::verifySecurityToken();
            if (!$this->perm->have_studip_perm('tutor', $cid)) {
                PageLayout::postError(I18N::_('Unzureichende Berechtigungen zum Ausführen dieser Aktion'));
                $this->redirect('index/config');
                return;
            }
            $title = trim(Request::get('title') ?? '');
            $text = trim(Request::get('text') ?? '');
            if (empty($text)) {
                PageLayout::postError(I18N::_('Einleitungstext muss nicht leer sein!'));
                $this->redirect('index/intros');
                return;
            }
            $newIntro = new \stdClass();
            $newIntro->title = $title;
            $newIntro->text = $text;

            $this->introductions[] = $newIntro;
            $this->courseConfig->introductions = json_encode($this->introductions);
            $this->courseConfig->store();
            PageLayout::postSuccess(I18N::_('Die Einleitung wurde erfolgreich hinzugefügt.'));
            $this->redirect('index/intros');
            return;
        }

        $this->render_template('index/intro_edit');
    }

    /**
     * Page customization - action for editting course config introduction.
     * This action is meant to be called by dialog.
     */
    public function edit_intro_action($index)
    {
        PageLayout::setTitle(I18N::_('Einleitung bearbeiten'));
        $cid = Context::getId();
        $this->is_new = false;
        $this->index = $index;
        if (!$this->perm->have_studip_perm('tutor', $cid)) {
            throw new AccessDeniedException(I18N::_('Unzureichende Berechtigungen zum Ausführen dieser Aktion'));
        }
        if (!isset($this->introductions[intval($index)])) {
            PageLayout::postError(I18N::_('Die Einleitung konnte nicht gefunden werden.'));
            $this->redirect('index/intros');
            return;
        }
        $intro = $this->introductions[intval($index)];
        $this->title = $intro->title;
        $this->text = $intro->text;
        if (Request::submitted('edit')) {
            CSRFProtection::verifySecurityToken();
            $title = trim(Request::get('title') ?? '');
            $text = trim(Request::get('text') ?? '');
            if (empty($text)) {
                PageLayout::postError(I18N::_('Einleitungstext muss nicht leer sein!'));
                $this->redirect('index/intros');
                return;
            }
            $intro->title = $title;
            $intro->text = $text;
            $this->introductions[intval($index)] = $intro;
            $this->courseConfig->introductions = json_encode($this->introductions);
            $this->courseConfig->store();
            PageLayout::postSuccess(I18N::_('Die Änderungen wurden erfolgreich gespeichert.'));
            $this->redirect('index/intros');
            return;
        }

        $this->render_template('index/intro_edit');
    }

    /**
     * Page customization - action for deleting a course config introduction.
     */
    public function delete_intro_action($index)
    {
        CSRFProtection::verifySecurityToken();
        $cid = Context::getId();
        if (!$this->perm->have_studip_perm('tutor', $cid)) {
            throw new AccessDeniedException(I18N::_('Unzureichende Berechtigungen zum Ausführen dieser Aktion'));
        }
        if (!is_int(intval($index))) {
            PageLayout::postError(I18N::_('Keinen Eintrag wurde ausgewählt.'));
            $this->redirect('index/intros');
            return;
        }

        if (isset($this->introductions[intval($index)])) {
            unset($this->introductions[intval($index)]);
            $this->courseConfig->introductions = json_encode($this->introductions);
            $this->courseConfig->store();
            PageLayout::postSuccess(I18N::_('Die Einleitung wurde gelöscht.'));
        } else {
            PageLayout::postError(I18N::_('Es trat ein Fehler beim Löschen der Einleitung auf!'));
        }

        $this->redirect('index/intros');
    }

    /**
     * Page customization - action for deleting course config introduction in bulk.
     */
    public function bulk_delete_intro_action()
    {
        $cid = Context::getId();
        if (!$this->perm->have_studip_perm('tutor', $cid)) {
            throw new AccessDeniedException(I18N::_('Unzureichende Berechtigungen zum Ausführen dieser Aktion'));
        }

        if (Request::method() === 'POST' && Request::submitted('bulk_delete')) {
            CSRFProtection::verifySecurityToken();
            $indices = Request::optionArray('indices');
            if (empty($indices)) {
                PageLayout::postError(I18N::_('Keine Einträge wurden ausgewählt.'));
                $this->redirect('index/intros');
                return;
            }
            $num_errors = 0;
            foreach ($indices as $index) {
                if (!isset($this->introductions[intval($index)])) {
                    $num_errors++;
                } else {
                    unset($this->introductions[intval($index)]);
                }
            }

            $this->courseConfig->introductions = json_encode($this->introductions);
            $this->courseConfig->store();

            if ($num_errors > 0) {
                $message = I18N::_('Es trat ein Fehler beim Löschen der Einleitungen auf!');
                if ($num_errors < count($indices)) {
                    $message = sprintf(I18N::_('%d Einleitung(en) konnte(n) nicht gelöscht werden.'), $num_errors);
                }
                PageLayout::postError($message);
            } else {
                PageLayout::postSuccess(I18N::_('Einleitung(en) wurde(n) gelöscht.'));
            }
        }
        $this->redirect('index/intros');
    }

    public function return_action()
    {
        PageLayout::addScript($this->plugin->getAssetsUrl() . '/js/meetings_return_helper.js?v=' . MeetingPlugin::getMeetingManifestInfo('version'));
        PageLayout::setTitle(self::getHeaderLine(Context::getId()));

        $cid = Request::get('cid');
        $return_url = RoomManager::generateMeetingBaseURL('index', ['cid' => $cid]);
        $this->return_url = $return_url;
    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * */
    /* * * * * H E L P E R   F U N C T I O N S * * * * */
    /* * * * * * * * * * * * * * * * * * * * * * * * * */

    private function getHelpbarContent($id)
    {
        /** @var \Helpbar $helpBar */

        switch ($id) {

            case 'main':
                $helpText = I18N::_('Durchführung und Verwaltung von Live-Online-Treffen, Veranstaltungen und Videokonferenzen. '
                                . 'Mit Hilfe der Face-to-Face-Kommunikation können Entfernungen überbrückt, externe Fachleute '
                                . 'einbezogen und Studierende in Projekten und Praktika begleitet werden.');
                $helpBar = Helpbar::get();
                $helpBar->addPlainText('', $helpText);
                break;

            case 'config':
                $helpText = I18N::_('Arbeitsbereich zum Anpassen der Gesamtansicht der Meetings einer Veranstaltung.');
                $helpBar = Helpbar::get();
                $helpBar->addPlainText('', $helpText);
                break;

            case 'my':
                $helpText = I18N::_('Gesamtansicht aller von Ihnen erstellten Meetings nach '
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
            return getHeaderLine($course_id) .' - '. I18N::_('Meetings');
        } else {
            return Context::getHeaderLine() .' - '. I18N::_('Meetings');
        }
    }

    /**
     * Adds the content to sidebar based on the domain requested.
     * @param string $domain where the sidebar should be set for.
     */
    protected function setSidebar($domain = 'course-index', $current_view = '')
    {
        switch ($domain) {
            case 'course-config':
                $this->buildCourseConfigSidebar($current_view);
                break;
            default:
                $this->buildCourseIndexSidebar();
                break;
        }
    }

    /**
     * Builds Sidebar contents for the index page.
     */
    private function buildCourseIndexSidebar()
    {
        $sidebar = Sidebar::get();

        if ($this->perm->have_studip_perm('tutor', Context::getId())) {
            $actions = new TemplateWidget(
                I18N::_('Aktionen'),
                $this->get_template_factory()->open('index/action_widget')
            );
            $sidebar->addWidget($actions)->addLayoutCSSClass('meeting-action-widget');

            // Folder widget
            $actions = new TemplateWidget(
                I18N::_('Ordner'),
                $this->get_template_factory()->open('index/folder_widget')
            );
            $sidebar->addWidget($actions)->addLayoutCSSClass('meeting-folder-widget');
        }

        $search = new \TemplateWidget(
            I18N::_('Suche'),
            $this->get_template_factory()->open('index/search_widget')
        );
        $sidebar->addWidget($search)->addLayoutCSSClass('meeting-search-widget');
    }

    /**
     * Builds Sidebar contents for the course config pages/views.
     * @param string $current_view the current view name.
     */
    private function buildCourseConfigSidebar($current_view = 'config')
    {
        $sidebar = Sidebar::get();

        // Views.
        $views = new ViewsWidget();
        $views->addLink(
            I18N::_('Einstellungen'),
            $this->url_for('index/config', ['view' => 'config']),
            null,
            [],
            'config'
        )->setActive($current_view == 'config');
        $views->addLink(
            I18N::_('Einleitungen'),
            $this->url_for('index/intros', ['view' => 'intros']),
            null,
            [],
            'intros'
        )->setActive($current_view == 'intros');

        $sidebar->addWidget($views);

        // Actions.
        $actions = new ActionsWidget();
        if ($current_view == 'intros') {
            $actions->addLink(
                I18N::_('Neue Einleitung hinzufügen'),
                $this->url_for('index/add_intro'),
                Icon::create('add')
            )->asDialog('size=auto');
        }

        $sidebar->addWidget($actions);
    }
}
