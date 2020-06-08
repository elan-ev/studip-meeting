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

use Meetings\AppFactory;
use Meetings\RouteMap;

require_once 'compat/StudipVersion.php';

class MeetingPlugin extends StudIPPlugin implements StandardPlugin, SystemPlugin
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
            $item = new Navigation($this->_('Meetings konfigurieren'), PluginEngine::getLink($this, array(), 'admin'));
            $item->setImage(self::getIcon('chat', 'white'));
            if (Navigation::hasItem('/admin/config') && !Navigation::hasItem('/admin/config/meetings')) {
                Navigation::addItem('/admin/config/meetings', $item);
            }
        }

        // do nothing if plugin is deactivated in this seminar/institute
        if (!$this->isActivated()) {
            return;
        }
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
    function perform($unconsumed_path)
    {
        require_once __DIR__ . '/vendor/autoload.php';

        if (substr($unconsumed_path, 0, 3) == 'api') {
            $appFactory = new AppFactory();
            $app = $appFactory->makeApp($this);
            $app->group('/meetingplugin/api', new RouteMap($app));
            $app->run();
        } else {
            PageLayout::addScript($this->getPluginUrl() . '/static/bundle.js');
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
     * @return bool | array | string
    */
    function checkOpenCast($cid = null) {
        $opencast_plugin = PluginEngine::getPlugin("OpenCast");
        if ($opencast_plugin) {
            if ($cid) {
                if ($opencast_plugin->isActivated($cid)) {
                    try {
                        return \Opencast\Models\OCSeminarSeries::getSeries($cid);
                    } catch (Exception $ex) {
                        //Handle Error
                        return false;
                    }
                } else {
                    return "not active";
                }
            }
            return true;
        }
        return false;
    }
}
