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
use Meetings\RouteMapPublic;
use Meetings\Helpers\WidgetHelper;
use Meetings\Errors\Error;

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
            $item->setImage($this->getIcon('meetings', 'white'));
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

    public function getIcon($name, $color, $attributes = [])
    {
        $meetingIconUrl = $this->getAssetsUrl() . "/images/icons/$color/meetings.svg";
        $role = Icon::ROLE_INFO;
        switch ($color) {
            case 'white':   $role = Icon::ROLE_INFO_ALT;         break;
            case 'gray':    $role = Icon::ROLE_INACTIVE;         break;
            case 'blue':    $role = Icon::ROLE_CLICKABLE;        break;
            case 'red':     $role = Icon::ROLE_NEW;              break;
            case 'green':   $role = Icon::ROLE_STATUS_GREEN;     break;
            case 'yellow':  $role = Icon::ROLE_STATUS_YELLOW;    break;
        }
        if ($name === 'meetings' || $name === 'meeting') {
            $name = $meetingIconUrl;
        }
        return Icon::create($name, $role, $attributes);
    }

    public function getPluginName()
    {
        return 'Meetings';
    }

    public function getInfoTemplate($course_id) {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getIconNavigation($courseId, $lastVisit, $userId = null)
    {
        /** @var Seminar_Perm $perm */
        $perm = $GLOBALS['perm'];

        if ($perm->have_studip_perm('tutor', $courseId)) {
            $courses = MeetingCourse::findBySQL(
                'INNER JOIN vc_meetings AS m ON meeting_id = m.id
                WHERE course_id = :course_id',
                array('course_id' => $courseId)
            );
        } else {
            $courses = MeetingCourse::findBySQL(
                'INNER JOIN vc_meetings AS m ON meeting_id = m.id
                WHERE active = 1 AND course_id = :course_id',
                array('course_id' => $courseId)
            );
        }

        $recentMeetings = 0;

        foreach ($courses as $meetingCourse) {
            if ($meetingCourse->meeting->mkdate >= $lastVisit) {
                $recentMeetings++;
            }
        }

        $courseConfig = CourseConfig::findByCourseId($courseId);
        $navigation = new Navigation($courseConfig->title, PluginEngine::getLink($this, array(), 'index'));

        if ($recentMeetings > 0) {
            $navigation->setImage($this->getIcon('meetings', 'red'), array(
                'title' => sprintf($this->_('%d Meeting(s), %d neue'), count($courses), $recentMeetings),
            ));
        } else {
            $navigation->setImage($this->getIcon('meetings', 'blue'), array(
                'title' => sprintf('%d Meeting(s)', count($courses)),
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
        $courseConfig = CourseConfig::findByCourseId($courseId);
        $main = new Navigation($courseConfig->title);
        $main->setURL(PluginEngine::getURL($this, [], 'index'));
        $main->setImage($this->getIcon('meetings', 'blue'));

        $main->addSubNavigation('meetings', new Navigation(
            $courseConfig->title,
            PluginEngine::getURL($this, [], 'index')
        ));

        if ($GLOBALS['perm']->have_studip_perm('dozent', $courseId)) {
            $main->addSubNavigation('config', new Navigation(
                $this->_('Seite Anpassen'),
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
            // make sure, slim knows if we are running https
            if (strpos($GLOBALS['ABSOLUTE_URI_STUDIP'], 'https') === 0) {
                $_SERVER['HTTPS'] = 'on';
            }
            $appFactory = new AppFactory();
            $app = $appFactory->makeApp($this);
            $app->group('/meetingplugin/api', RouteMap::class);
            $app->run();
        } else if (substr($unconsumed_path, 0, 6) == 'public') {
            $appFactory = new AppFactory();
            $app = $appFactory->makeApp($this);
            $app->group('/meetingplugin/public', RouteMapPublic::class);
            $app->run();
        } else {
            PageLayout::addStylesheet($this->getPluginUrl() . '/static/styles.css?v=' . self::getMeetingManifestInfo('version'));

            $trails_root = $this->getPluginPath() . '/app';
            $dispatcher  = new Trails_Dispatcher($trails_root,
                rtrim(PluginEngine::getURL($this, null, ''), '/'),
                'index'
            );

            $dispatcher->current_plugin = $this;
            $dispatcher->dispatch($unconsumed_path);
        }

        die;
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
        $plugin_manager = \PluginManager::getInstance();
        $opencast_plugin = $plugin_manager->getPluginInfo('OpenCast');
        if ($opencast_plugin && $opencast_plugin['enabled']) {
            if ($cid) {
                if ($plugin_manager->isPluginActivated($opencast_plugin['id'], $cid)) {
                    try {
                        return self::getOpencastSeriesId($cid);
                    } catch (Exception $ex) {
                        //Handle Error
                        throw new Error('Opencast-Serien-ID konnte nicht abgerufen werden.', 500);
                    }
                } else {
                    return ""; //because of checkers along the flow (empty string is a sign of Opencast not activated!)
                }
            }
            return true;
        }
        return false;
    }

    private static function getOpencastSeriesId($cid) {
        if (empty($cid)) {
            return false;
        }

        // Getting series id directly from database to make everything simpler.
        $series = DBManager::get()->fetchOne(
            'SELECT series_id FROM oc_seminar_series
                    WHERE seminar_id = :cid ORDER BY `mkdate` ASC',
            [':cid' => $cid]);
        if (empty($series)) {
            return false;
        }

        return $series['series_id'];
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

        $metadata['pluginname']  = $this->getPluginName();
        $metadata['displayname'] = $this->getPluginName();

        $metadata['descriptionlong'] = $this->_("Virtueller Raum, mit dem Live-Online-Treffen, Veranstaltungen "
            . "und Videokonferenzen durchgeführt werden können. Die Teilnehmenden können sich während "
            . "eines Meetings gegenseitig hören und über eine angeschlossene Webcam - wenn vorhanden - "
            . "sehen und miteinander arbeiten. Folien können präsentiert und Abfragen durchgeführt werden. "
            . "Ein Fenster in der Benutzungsoberfläche des eigenen Rechners kann für andere sichtbar "
            . "geschaltet werden, um zum Beispiel den Teilnehmenden bestimmte Webseiten oder Anwendungen "
            . "zu zeigen. Außerdem kann die Veranstaltung aufgezeichnet und Interessierten zur Verfügung gestellt werden."
        );

        $metadata['summary'] = $this->_("Meetings & Videokonferenzen");
        $metadata['description'] = $this->_('Virtueller Raum, mit dem Live-Online-Treffen, Veranstaltungen und Videokonferenzen durchgeführt werden können.');

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
     * @param string $item a sinlge manifest item extract.
     *
     * @return array|string|bool $metadata the manifest metadata of this plugin, or a single item string if found, or false otherwise.
     */
    public static function getMeetingManifestInfo($item = '')
    {
        $plugin_manager = \PluginManager::getInstance();
        $this_plugin = $plugin_manager->getPluginInfo(__CLASS__);
        $plugin_path = $GLOBALS['PLUGINS_PATH'] . '/' .$this_plugin['path'];
        $manifest = $plugin_manager->getPluginManifest($plugin_path);
        if (!empty($item)) {
            return (isset($manifest[$item]) ? $manifest[$item] : false);
        }
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
        if (!$user instanceof \Seminar_User) {
            $user = new \Seminar_User($user);
        }
        $meetingCourses = MeetingCourse::findByUser($user);

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
        if ($server_course_type == '' || is_array($server_course_type)) { // When it is empty or an array, it supports all course types.
            return true;
        }

        if (!$course) {
            return false;
        }

        $course_class_id = $course->getSemClass()->offsetGet('id');
        $course_type_id = $course->getSemType()->offsetGet('id');

        $server_course_type_arr = explode("_", $server_course_type);
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
        if (!$server_course_type || is_array($server_course_type)) { // When it is empty or an array, it supports all course types.
            return dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Alle Veranstaltungstypen');
        }

        $server_course_type_arr = explode("_", $server_course_type);

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
     * @return Flexi\PhpTemplate The template containing the widget contents
     */
    public function getPortalTemplate()
    {
        global $perm;
        // We need to use "nobody" rights for Upload Slides,
        // but in here we have to prevent that right,
        // in order to not to show the template in login page and so on.
        if ('nobody' === $GLOBALS['user']->id) {
            return;
        }

        $template_factory = new \Flexi\Factory(__DIR__ . "/templates");
        $template = $template_factory->open("index.php");

        $template->set_attribute('items', WidgetHelper::getMeetingsForWidget());
        $template->set_attribute('meeting_icons', [
            'black' =>          $this->getIcon('meetings', 'black'),
            'black-header' =>   $this->getIcon('meetings', 'black', ['style' => 'margin-top:0;']),
            'white' =>          $this->getIcon('meetings', 'white'),
            'red' =>            $this->getIcon('meetings', 'red'),
            'blue' =>           $this->getIcon('meetings', 'blue'),
            'gray' =>           $this->getIcon('meetings', 'gray'),
            'gray-header' =>    $this->getIcon('meetings', 'gray', ['style' => 'margin-top:0;']),
            'green' =>          $this->getIcon('meetings', 'green'),
            'yellow' =>         $this->getIcon('meetings', 'yellow'),
        ]);

        $empty_text = $this->_('Derzeit finden keine Meetings in den gebuchten Kursen statt.');
        if ($perm->have_perm('admin') || $perm->have_perm('root')) {
            $empty_text = $this->_('Um Leistungsprobleme zu vermeiden, ist diese Funktion für Administratoren dauerhaft deaktiviert.');
        }

        $texts = [
            'empty' => $empty_text,
            'current' => $this->_('Derzeitige Meetings'),
            'upcoming' => $this->_('Kommende Meetings'),
            'to_course' => $this->_('Zur Meeting-Liste'),
            'to_meeting' => $this->_('Direkt zum Meeting'),
            'privacy_onclick' => $this->renderPrivacyDialog()
        ];
        $template->set_attribute('texts', $texts);

        return $template;
    }

    private function renderPrivacyDialog() {
        $privacy_text = $this->_('Ich bin damit einverstanden, dass diese Sitzung aufgezeichnet wird. Die Aufzeichnung kann Sprach- und Videoaufnahmen von mir beinhalten.' .
        ' Bitte beachten Sie, dass die Aufnahme im Anschluss geteilt werden kann.' .
        ' Möchten Sie trotzdem teilnehmen?');

        $dialog_id = 'meeting-privacy-confirmation-dialog';
        $privacy_dialog_options = "{
            id: '{$dialog_id}',
            title: '" . $this->_('Datenschutzerklärung') . "',
            wikilink: false,
            dialogClass: 'studip-confirmation',
            width: 400,
            height: 230,
            buttons: {
                accept: {
                    text: '" . $this->_('Ja') . "',
                    click: () => {STUDIP.Dialog.close({ id: '{$dialog_id}' }); window.open('%s', '%s');},
                    class: 'accept'
                },
                cancel: {
                    text: '" . $this->_('Nein') . "',
                    click: () => STUDIP.Dialog.close({ id: '{$dialog_id}' }),
                    class: 'cancel'
                }
            }
        }";
        $privacy_dialog = "return STUDIP.Dialog.show('{$privacy_text}', {$privacy_dialog_options}); return false;";
        return $privacy_dialog;
    }

    public static function isCoursePublic($cid)
    {
        $course = \Course::find($cid);
        if (\Config::get()->ENABLE_FREE_ACCESS && $GLOBALS['user']->id == 'nobody'
        && $course && $course->lesezugriff == 0) {
            return true;
        }

        return false;
    }
}
