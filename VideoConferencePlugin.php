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

use ElanEv\Model\Meeting;

class VideoConferencePlugin extends StudipPlugin implements StandardPlugin
{
    const NAVIGATION_ITEM_NAME = 'video-conferences';

    private $assetsUrl;

    public function __construct() {
        parent::__construct();

        // do nothing if plugin is deactivated in this seminar/institute
        if (!$this->isActivated()) {
            return;
        }

        if (!version_compare($GLOBALS['SOFTWARE_VERSION'], '2.3', '>')) {
            $navigation = $this->getTabNavigation(Request::get('cid', $GLOBALS['SessSemName'][1]));
            Navigation::insertItem('/course/'.self::NAVIGATION_ITEM_NAME, $navigation['VideoConference'], null);
        }

        $this->assetsUrl = rtrim($this->getPluginURL(), '/').'/assets';
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
            $courses = Meeting::findByCourseId($courseId);
        } else {
            $courses = Meeting::findActiveByCourseId($courseId);
        }

        $recentMeetings = 0;

        foreach ($courses as $course) {
            if ($course->mkdate >= $lastVisit) {
                $recentMeetings++;
            }
        }

        $navigation = new Navigation(_('Konferenzen'), PluginEngine::getLink($this, array(), 'index'));

        if ($recentMeetings > 0) {
            $navigation->setImage('icons/20/red/chat.png', array(
                'title' => sprintf(_('%d Konferenz(en), %d neue'), count($courses), $recentMeetings),
            ));
        } else {
            $navigation->setImage('icons/20/grey/chat.png', array(
                'title' => sprintf(_('%d Konferenz(en)'), count($courses)),
            ));
        }

        return $navigation;
    }
    
    /* interface method */
    function getNotificationObjects($course_id, $since, $user_id)
    {
        return array();
    }

    public function getTabNavigation($course_id) {
        $main = new Navigation(_('Konferenzen'));
        $main->setURL(PluginEngine::getURL($this, array(), 'index'));
        $main->setImage('icons/16/white/chat.png', array('title', _('Konferenzen')));

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
