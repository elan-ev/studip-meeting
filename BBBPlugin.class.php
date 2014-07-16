<?php

/*
 * BigBlueButton.class.php - BigBlueButton Stud.IP Integration
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Till Glöggler <till.gloeggler@elan-ev.de>
 * @copyright   2011 ELAN e.V. <http://www.elan-ev.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

require_once dirname(__FILE__) . '/bbb-api.php';
require_once __DIR__.'/vendor/autoload.php';

class BBBPlugin extends StudipPlugin implements StandardPlugin {

    public function __construct() {
        parent::__construct();

        // do nothing if plugin is deactivated in this seminar/institute
        if (!$this->isActivated()) {
            return;
        }

        if (!version_compare($GLOBALS['SOFTWARE_VERSION'], '2.3', '>')) {
            $navigation = $this->getTabNavigation(Request::get('cid', $GLOBALS['SessSemName'][1]));
            Navigation::insertItem('/course/BBBPlugin', $navigation['BBBPlugin'], null); 
        } 
    }

    public function getInfoTemplate($course_id) {
        return null;
    }

    public function getIconNavigation($course_id, $last_visit, $user_id = null) {
        return null;
    }
    
    /* interface method */
    function getNotificationObjects($course_id, $since, $user_id)
    {
        return array();
    }

    public function getTabNavigation($course_id) {
        $main = new Navigation("Konferenz");
        $main->setURL(PluginEngine::getURL('BBBPlugin/index'));
        $main->setImage('icons/16/white/chat.png', array('title', 'Big Blue Button'));
        return array('BBBPlugin' => $main);
    }

    //TODO: show error message
    public function error(){
        return null;
    }
    
    /**
     * This method dispatches all actions.
     *
     * @param string   part of the dispatch path that was not consumed
     */
    function perform($unconsumed_path)
    {
        $trails_root = $this->getPluginPath() .'/app';
        $dispatcher = new Trails_Dispatcher($trails_root, PluginEngine::getUrl('BBBPlugin/index'), 'index');
        $dispatcher->dispatch($unconsumed_path);

    }

}
