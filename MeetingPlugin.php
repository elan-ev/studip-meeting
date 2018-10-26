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

require_once __DIR__.'/vendor/autoload.php';

use ElanEv\Model\CourseConfig;
use ElanEv\Model\MeetingCourse;

require_once 'compat/StudipVersion.php';

class MeetingPlugin extends StudipPlugin implements StandardPlugin, SystemPlugin
{
    const NAVIGATION_ITEM_NAME = 'video-conferences';

    private $assetsUrl;

    public function __construct() {
        parent::__construct();

        $this->assetsUrl = rtrim($this->getPluginURL(), '/').'/assets';

        /** @var \Seminar_Perm $perm */
        $perm = $GLOBALS['perm'];

        if ($perm->have_perm('root')) {
            $item = new Navigation(_('Meetings'), PluginEngine::getLink($this, array(), 'index/all'));
            $item->setImage(self::getIcon('chat', 'white'));

            if (Navigation::hasItem('/admin/locations')) {
                Navigation::addItem('/admin/locations/meetings', $item);
            } else {
                Navigation::addItem('/meetings', $item);
            }

            $item = new Navigation(_('Meetings konfigurieren'), PluginEngine::getLink($this, array(), 'admin/index'));
            $item->setImage(self::getIcon('chat', 'white'));
            Navigation::addItem('/admin/config/meetings', $item);
        } elseif ($perm->have_perm('dozent')) {
            if (Navigation::hasItem('/profile') && !Navigation::hasItem('/profile/meetings')) {
                $current_user = User::findByUsername(Request::username('username', $GLOBALS['user']->username));

                if ($current_user->id == $GLOBALS['user']->id) {
                    $item = new Navigation($this->_('Meine Meetings'), PluginEngine::getLink($this, array(), 'index/my'));
                    Navigation::addItem('/profile/meetings', $item);
                }
            }
        }

        // do nothing if plugin is deactivated in this seminar/institute
        if (!$this->isActivated()) {
            return;
        }

        if (!version_compare($GLOBALS['SOFTWARE_VERSION'], '2.3', '>')) {
            $navigation = $this->getTabNavigation(Request::get('cid', $GLOBALS['SessSemName'][1]));
            Navigation::insertItem('/course/'.self::NAVIGATION_ITEM_NAME, $navigation['VideoConference'], null);
        }
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
        return _('Meetings');
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
                'title' => sprintf(_('%d Meeting(s), %d neue'), count($courses), $recentMeetings),
            ));
        } else {
            $navigation->setImage(self::getIcon('chat', 'gray'), array(
                'title' => sprintf(_('%d Meeting(s)'), count($courses)),
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
        $main->setURL(PluginEngine::getURL($this, array(), 'index'));
        $main->setImage(self::getIcon('chat', 'white'), array('title', $courseConfig->title));

        return array(self::NAVIGATION_ITEM_NAME => $main);
    }

    //TODO: show error message
    public function error(){
        return null;
    }

    /**
     * {@inheritdoc}
     */
    function perform($unconsumed_path)
    {
        $trails_root = $this->getPluginPath().'/app';
        $dispatcher = new Trails_Dispatcher($trails_root, PluginEngine::getUrl($this, array(), 'index'), 'index');
        $dispatcher->dispatch($unconsumed_path);

    }

    public function getAssetsUrl()
    {
        return $this->assetsUrl;
    }
}
