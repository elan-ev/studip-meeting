<?php

/*
 * Stud.IP Video Conference Services Integration
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Till Glöggler <till.gloeggler@elan-ev.de>
 * @author      Christian Flothmann <christian.flothmann@uos.de>
 * @copyright   2011-2014 ELAN e.V. <http://www.elan-ev.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

require_once __DIR__.'/bootstrap.php';

use ElanEv\Model\CourseConfig;
use ElanEv\Model\MeetingCourse;
use ElanEv\Model\Meeting;

use Meetings\AppFactory;
use Meetings\RouteMap;
use Meetings\WidgetHandler;

require_once 'compat/StudipVersion.php';

class MeetingPlugin extends StudIPPlugin implements PortalPlugin, StandardPlugin, SystemPlugin
{
    const GETTEXT_DOMAIN = 'meetings';
    const NAVIGATION_ITEM_NAME = 'video-conferences';

    private $assetsUrl;

    public function __construct()
    {
        parent::__construct();

        bindtextdomain(static::GETTEXT_DOMAIN, $this->getPluginPath() . '/locale');
        bind_textdomain_codeset(static::GETTEXT_DOMAIN, 'UTF-8');

        $this->assetsUrl = rtrim($this->getPluginURL(), '/').'/assets';

        /** @var \Seminar_Perm $perm */
        $perm = $GLOBALS['perm'];

        if ($perm->have_perm('root')) {
            $item = new Navigation($this->_('Meetings konfigurieren'), PluginEngine::getLink($this, array(), 'admin#/admin'));
            $item->setImage(self::getIcon('chat', 'white'));
            if (Navigation::hasItem('/admin/config') && !Navigation::hasItem('/admin/config/meetings')) {
                Navigation::addItem('/admin/config/meetings', $item);
            }
        }

        NotificationCenter::addObserver($this, 'DeleteMeetingOnUserDelete', 'UserDidDelete');
        NotificationCenter::addObserver($this, 'UpdateMeetingOnUserMigrate', 'UserDidMigrate');

        // do nothing if plugin is deactivated in this seminar/institute
        if (!$this->isActivated()) {
            return;
        }
    }

    /**
     * Checks if the context in which the plugin is activated is of Course type.
     *
     * @param Range $context
     * @return bool
     */
    public function isActivatableForContext(Range $context)
    {
        return get_class($context) === \Course::class;
    }

    /**
     * Plugin localization for a single string.
     * This method supports sprintf()-like execution if you pass additional
     * parameters.
     *
     * @param String $string String to translate
     * @return translated string
     */
    public function _($string)
    {
        $result = static::GETTEXT_DOMAIN === null
                ? $string
                : dcgettext(static::GETTEXT_DOMAIN, $string, LC_MESSAGES);
        if ($result === $string) {
            $result = _($string);
        }

        if (func_num_args() > 1) {
            $arguments = array_slice(func_get_args(), 1);
            $result = vsprintf($result, $arguments);
        }

        return $result;
    }

    /**
     * Plugin localization for plural strings.
     * This method supports sprintf()-like execution if you pass additional
     * parameters.
     *
     * @param String $string0 String to translate (singular)
     * @param String $string1 String to translate (plural)
     * @param mixed  $n       Quantity factor (may be an array or array-like)
     * @return translated string
     */
    public function _n($string0, $string1, $n)
    {
        if (is_array($n)) {
            $n = count($n);
        }

        $result = static::GETTEXT_DOMAIN === null
                ? $string0
                : dngettext(static::GETTEXT_DOMAIN, $string0, $string1, $n);
        if ($result === $string0 || $result === $string1) {
            $result = ngettext($string0, $string1, $n);
        }

        if (func_num_args() > 3) {
            $arguments = array_slice(func_get_args(), 3);
            $result = vsprintf($result, $arguments);
        }

        return $result;
    }

    public static function getIcon($name, $color)
    {
        if (StudipVersion::newerThan('3.3')) {
            $type = 'info';
            switch ($color) {
                case 'white': $type = 'info_alt'; break;
                case 'grey':  $type = 'inactive'; break;
                case 'blue':  $type = 'clickable';break;
                case 'red':   $type = 'new';      break;
                case 'gray':  $type = 'inactive'; break;
            }
            return Icon::create($name, $type);
        } else {
            return 'icons/16/' . $color .'/' . $name;
        }
    }

    public function getPluginName()
    {
        return $this->_('Meetings');
    }

    public function getInfoTemplate($course_id) {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getIconNavigation($courseId, $lastVisit, $userId = null)
    {
        require_once __DIR__ . '/vendor/autoload.php';

        /** @var Seminar_Perm $perm */
        $perm = $GLOBALS['perm'];

        if ($perm->have_studip_perm('tutor', $courseId)) {
            $courses = MeetingCourse::findByCourseId($courseId);
        } else {
            $courses = MeetingCourse::findActiveByCourseId($courseId);
        }

        $recentMeetings = 0;

        foreach ($courses as $meetingCourse) {
            if ($meetingCourse->course->mkdate >= $lastVisit) {
                $recentMeetings++;
            }
        }

        $courseConfig = CourseConfig::findByCourseId($courseId);
        $navigation = new Navigation($courseConfig->title, PluginEngine::getLink($this, array(), 'index'));

        if ($recentMeetings > 0) {
            $navigation->setImage(self::getIcon('chat', 'red'), array(
                'title' => sprintf($this->_('%d Meeting(s), %d neue'), count($courses), $recentMeetings),
            ));
        } else {
            $navigation->setImage(self::getIcon('chat', 'gray'), array(
                'title' => sprintf($this->_('%d Meeting(s)'), count($courses)),
            ));
        }

        return $navigation;
    }

    /* interface method */
    function getNotificationObjects($course_id, $since, $user_id)
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getTabNavigation($courseId)
    {
        require_once __DIR__ . '/vendor/autoload.php';

        $courseConfig = CourseConfig::findByCourseId($courseId);
        $main = new Navigation($courseConfig->title);
        $main->setURL(PluginEngine::getURL($this, [], 'index'));

        $main->addSubNavigation('meetings', new Navigation(
            $courseConfig->title,
            PluginEngine::getURL($this, [], 'index')
        ));

        if ($GLOBALS['perm']->have_studip_perm('dozent', $courseId)) {
            $main->addSubNavigation('config', new Navigation(
                _('Anpassen'),
                PluginEngine::getLink($this, [], 'index/config')
            ));
        }

        return array(self::NAVIGATION_ITEM_NAME => $main);
    }

    /**
     * {@inheritdoc}
     */
    public function perform($unconsumed_path)
    {
        require_once __DIR__ . '/vendor/autoload.php';

        if (substr($unconsumed_path, 0, 3) == 'api') {
            $appFactory = new AppFactory();
            $app = $appFactory->makeApp($this);
            $app->group('/meetingplugin/api', new RouteMap($app));
            $app->run();
        } else {
            PageLayout::addStylesheet($this->getPluginUrl() . '/static/styles.css');

            $trails_root = $this->getPluginPath() . '/app';
            $dispatcher  = new Trails_Dispatcher($trails_root,
                rtrim(PluginEngine::getURL($this, null, ''), '/'),
                'index'
            );

            $dispatcher->current_plugin = $this;
            $dispatcher->dispatch($unconsumed_path);
        }
    }

    public function getAssetsUrl()
    {
        return $this->assetsUrl;
    }

    /**
     * Checks if opencast is loaded, and if course id is passed,
     * returns the series id of the course if opencast has been set for the course
     *
     * @param  string  $cid course ID with default null
     * @return bool | array | string(empty - in case opencast is not activated for this course)
    */
    public static function checkOpenCast($cid = null) {
        $opencast_plugin = PluginEngine::getPlugin("OpenCast");
        if ($opencast_plugin) {
            if ($cid) {
                if ($opencast_plugin->isActivated($cid)) {
                    try {
                        $OCSeries = \Opencast\Models\OCSeminarSeries::getSeries($cid);
                        if (!empty($OCSeries)) {
                            return $OCSeries[0]['series_id'];
                        }
                        return false;
                    } catch (Exception $ex) {
                        //Handle Error
                        return false;
                    }
                } else {
                    return ""; //because of checkers along the flow (empty string is a sign of Opencast not activated!)
                }
            }
            return true;
        }
        return false;
    }

    /**
     * @inherits
     *
     * Overwrite default metadata-function to translate the descriptions
     *
     * @return Array the plugins metadata as an array
     */
    public function getMetadata()
    {
        $metadata = parent::getMetadata();

        $metadata['pluginname']  = $this->_("Meetings");
        $metadata['displayname'] = $this->_("Meetings");

        $metadata['descriptionlong'] = $this->_("Virtueller Raum, mit dem Live-Online-Treffen, Veranstaltungen "
            . "und Videokonferenzen durchgeführt werden können. Die Teilnehmenden können sich während "
            . "eines Meetings gegenseitig hören und über eine angeschlossene Webcam - wenn vorhanden - "
            . "sehen und miteinander arbeiten. Folien können präsentiert und Abfragen durchgeführt werden. "
            . "Ein Fenster in der Benutzungsoberfläche des eigenen Rechners kann für andere sichtbar "
            . "geschaltet werden, um zum Beispiel den Teilnehmenden bestimmte Webseiten oder Anwendungen "
            . "zu zeigen. Außerdem kann die Veranstaltung aufgezeichnet und Interessierten zur Verfügung gestellt werden."
        );

        $metadata['descriptionshort'] = $this->_("Face-to-face-Kommunikation mit Adobe Connect oder BigBlueButton");

        $metadata['keywords'] = $this->_("Videokonferenz- und Veranstaltungsmöglichkeit; "
            . "Live im Netz präsentieren sowie gemeinsam zeichnen und arbeiten;Kommunikation über Mikrofon und Kamera; "
            . "Ideal für dezentrale Lern- und Arbeitsgruppen; "
            . "Verlinkung zu bereits bestehenden eigenen Räumen"
        );

        return $metadata;
    }

    /**
     * getMeetingManifestInfo
     *
     * get the plugin manifest from PluginManager getPluginManifest method
     *
     * @return Array $metadata the manifest metadata of this plugin
     */
    public static function getMeetingManifestInfo()
    {
        $plugin_manager = \PluginManager::getInstance();
        $this_plugin = $plugin_manager->getPluginInfo(__CLASS__);
        $plugin_path = \get_config('PLUGINS_PATH') . '/' .$this_plugin['path'];
        $manifest = $plugin_manager->getPluginManifest($plugin_path);
        return $manifest;
    }

    /**
    * DeleteMeetingOnUserDelete: handler for UserDidDelete event
    *
    * @param object event
    * @param user $user
    *
    */
    public function DeleteMeetingOnUserDelete($event, $user)
    {
        require_once __DIR__ . '/vendor/autoload.php';

        if (!$user instanceof \Seminar_User) {
            $seminar_user = new \Seminar_User($user);
        }
        $meetingCourses = MeetingCourse::findByUser($seminar_user);

        if ($meetingCourses) {
            foreach ($meetingCourses as $meetingCourse) {
                $meetingCourse->meeting->delete();
                $meetingCourse->delete();
            }
        }
    }

    /**
    * UpdateMeetingOnUserMigrate: handler for UserDidMigrate event
    *
    * @param string $old_id old user id
    * @param string $new_id new user id
    *
    */
    public function UpdateMeetingOnUserMigrate($event, $old_id, $new_id)
    {
        require_once __DIR__ . '/vendor/autoload.php';

        if ($old_id && $new_id) {
            $user_meetings = Meeting::findBySQL('user_id = ?', [$old_id]);
            if ($user_meetings) {
                foreach ($user_meetings as $meeting) {
                    $meeting->user_id = $new_id;
                    $meeting->store();
                }
            }
        }
    }

    /**
     * Get seminar types for the course_types (dropdown).
     * The course_types dropdown applies to each server of a given server
     *
     * @return array with key => value pairs like: array('class_id' => array('name' => 'class_name', 'subs' => [array of sub cats]))
     */
    public static function getSemClasses()
    {
        $sem_classes = [];
        foreach ($GLOBALS['SEM_CLASS'] as $class_id => $class) {
            $class_obj = [];
            $class_obj['name'] = _($class['name']);
            $class_obj['subs'][$class_id] = "{$class_obj['name']} (" . _('Alle') . ")";
            if (!$class['studygroup_mode']) {
                foreach ($class->getSemTypes() as $type_id => $type) {
                    $class_obj['subs']["{$class_id}_{$type_id}"] = _($type['name']);
                }
            }
            $sem_classes[$class_id] = $class_obj;
        }
        ksort($sem_classes);
        return $sem_classes;
    }

    /**
     * Checks whether the server course type against the current course
     *
     * @param  Course $course The current course which the server is going to be used
     * @param  String $server_course_type The server course type
     * @return boolean
     */
    public static function checkCourseType(Course $course, $server_course_type)
    {
        if ($server_course_type == '') { // When empty, it supports all course types
            return true;
        }

        if (!$course) {
            return false;
        }

        $course_class_id = $course->getSemClass()->offsetGet('id');
        $course_type_id = $course->getSemType()->offsetGet('id');

        $server_course_type_arr = explode("_", $server_course_type);
        $server_course_class_id = '';
        $server_course_type_id = '';

        if (count($server_course_type_arr) == 1) {
            $server_course_class_id = $server_course_type_arr[0];
            if ($server_course_class_id == $course_class_id) {
                return true;
            } else {
                return false;
            }
        } elseif (count($server_course_type_arr) == 2) {
            $server_course_class_id = $server_course_type_arr[0];
            $server_course_type_id = $server_course_type_arr[1];
            if ($server_course_class_id == $course_class_id && $server_course_type_id == $course_type_id) {
                return true;
            } else {
                return false;
            }
        }

        return false;
    }

    /**
    * Finds corresponding course type name
    *
    * @param String $server_course_type The server course type
    * @return String $course_type_name The name of the semClass
    */
    public static function getCourseTypeName($server_course_type)
    {
        if (!$server_course_type ) {
            return _('Alle Veranstaltungstypen');
        }

        $server_course_type_arr = explode("_", $server_course_type);
        $server_course_class_id = '';
        $server_course_type_id = '';

        if (count($server_course_type_arr) == 1) {
            $server_course_class_id = $server_course_type_arr[0];
            return _($GLOBALS['SEM_CLASS'][$server_course_class_id]['name']);
        } elseif (count($server_course_type_arr) == 2) {
            $server_course_class_id = $server_course_type_arr[0];
            $server_course_type_id = $server_course_type_arr[1];
            return _($GLOBALS['SEM_CLASS'][$server_course_class_id]->getSemTypes()[$server_course_type_id]['name']);
        }

        return '';
    }

    /**
     * Return the template for the widget.
     *
     * @return Flexi_PhpTemplate The template containing the widget contents
     */
    public function getPortalTemplate()
    {
        require_once __DIR__ . '/vendor/autoload.php';

        // We need to use "nobody" rights for Upload Slides,
        // but in here we have to prevent that right,
        // in order to not to show the template in login page and so on.
        if ('nobody' === $GLOBALS['user']->id) {
            return;
        }

        $template_factory = new Flexi_TemplateFactory(__DIR__ . "/templates");
        $template = $template_factory->open("index.php");
        
        $template->set_attribute('items', WidgetHandler::getMeetingsForWidget());

        $texts = [
            'empty' => $this->_('Derzeit finden keine Meetings in den gebuchten Kursen statt.'),
            'current' => $this->_('Derzeitige Meetings'),
            'upcoming' => $this->_('Kommende Meetings'),
            'to_course' => $this->_('Zur Meeting-Liste'),
            'to_meeting' => $this->_('Direkt zum Meeting')
        ];
        $template->set_attribute('texts', $texts);

        return $template;
    }
}
