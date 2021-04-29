<?php

use ElanEv\Driver\DriverFactory;
use ElanEv\Driver\JoinParameters;
use ElanEv\Model\Driver;
use ElanEv\Model\InvitationsLink;
use ElanEv\Model\MeetingCourse;
use ElanEv\Model\Meeting;
use MeetingPlugin;

class RoomController extends PluginController
{
    /**
     * Constructs the controller and provide translations methods.
     *
     * @param object $dispatcher
     * @see https://stackoverflow.com/a/12583603/982902 if you need to overwrite
     *      the constructor of the controller
     */
    public function __construct($dispatcher)
    {
        parent::__construct($dispatcher);

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
        $this->driver_factory = new DriverFactory(Driver::getConfig());
    }

    /**
     * Intercepts all non-resolvable method calls in order to correctly handle
     * calls to _ and _n.
     *
     * @param string $method
     * @param array $arguments
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

    public function index_action($link_hex, $cid)
    {
        PageLayout::setTitle($this->_('Stud.IP Meeting'));

        $this->invitations_link = InvitationsLink::findOneBySQL('hex = ?', [$link_hex]);
        if (!$this->invitations_link) {
            throw new Exception($this->_('Das gesuchte Meeting existiert nicht mehr!'));
        }

        $meeting = $this->invitations_link->meeting;

        // Checking Course Type
        $servers = Driver::getConfigValueByDriver($meeting->driver, 'servers');
        $allow_course_type = MeetingPlugin::checkCourseType($meeting->courses->find($cid), $servers[$meeting->server_index]['course_types']);
        // Checking Server Active
        $active_server = $servers[$meeting->server_index]['active'];
        if (!$allow_course_type || !$active_server) {
            throw new Exception($this->_('Das gesuchte Meeting ist nicht verfügbar!'));
        }

        $this->cid = $cid;

        $features = json_decode($meeting->features, true);
        if (isset($features['guestPolicy-ALWAYS_ACCEPT']) && $features['guestPolicy-ALWAYS_ACCEPT'] === 'false') {
            throw new Exception($this->_('Das gesuchte Meeting ist nicht verfügbar!'));
        }
        $driver = $this->driver_factory->getDriver($meeting->driver, $meeting->server_index);
        if (isset($features['room_anyone_can_start']) && $features['room_anyone_can_start'] === 'false') {
            $meetingCourse = new MeetingCourse([$meeting->id, $cid]);
            $status = $driver->isMeetingRunning($meetingCourse->meeting->getMeetingParameters()) === 'true' ? true : false;

            if (!$status) {
                $this->redirect('room/lobby/' . $meeting->id . '/' . $cid . '/#lobby');
                return;
            }
        }

        $widget = new SidebarWidget();
        $widget->setTitle($this->_('Meeting-Name'));
        $widget->addElement(
            new WidgetElement(htmlReady($this->invitations_link->meeting->name))
        );
        Sidebar::Get()->addWidget($widget);
    }

    public function lobby_action($room_id, $cid)
    {
        if ($GLOBALS['perm']->have_studip_perm('user', $cid)) {
            $meeting = Meeting::findOneBySql('id = ?', [$room_id]);
            $link = PluginEngine::getURL($this->dispatcher->current_plugin, [], 'api/rooms/join/'. $cid .'/'. $room_id);
        } else {
            $invitations_link = InvitationsLink::findOneBySQL('meeting_id = ?', [$room_id]);
            if (!$invitations_link) {
                throw new Exception($this->_('Das gesuchte Meeting existiert nicht mehr!'));
            }
            $meeting = $invitations_link->meeting;
            $link = 'room/index/' . $invitations_link->hex . '/' . $cid;
        }

        $features = json_decode($meeting->features, true);
        $driver = $this->driver_factory->getDriver($meeting->driver, $meeting->server_index);
        if (isset($features['room_anyone_can_start']) && $features['room_anyone_can_start'] === 'false') {
            $meetingCourse = new MeetingCourse([$meeting->id, $cid]);
            $status = $driver->isMeetingRunning($meetingCourse->meeting->getMeetingParameters()) === 'true' ? true : false;

            if ($status) {
                $this->redirect($link);
                return;
            }
        }

    }

    public function join_meeting_action($link_hex, $cid)
    {
        $invitations_link = InvitationsLink::findOneBySQL('hex = ?', [$link_hex]);
        if (!$invitations_link) {
            throw new Exception($this->_('Das gesuchte Meeting existiert nicht mehr!'));
        }
        $name = trim(Request::get('name'));
        if (!$name) {
            $name = $invitations_link->default_name;
        }
        $meeting = $invitations_link->meeting;

        // Checking Course Type
        $servers = Driver::getConfigValueByDriver($meeting->driver, 'servers');
        $allow_course_type = MeetingPlugin::checkCourseType($meeting->courses->find($cid), $servers[$meeting->server_index]['course_types']);
        // Checking Server Active
        $active_server = $servers[$meeting->server_index]['active'];
        if (!$allow_course_type || !$active_server) {
            throw new Exception($this->_('Das gesuchte Meeting ist nicht verfügbar!'));
        }

        $driver = $this->driver_factory->getDriver($meeting->driver, $meeting->server_index);
        $joinParameters = new JoinParameters();
        $joinParameters->setMeetingId($meeting->id);
        $joinParameters->setIdentifier($meeting->identifier);
        $joinParameters->setRemoteId($meeting->remote_id);
        $joinParameters->setPassword($meeting->attendee_password);
        $joinParameters->setHasModerationPermissions(false);
        $joinParameters->setUsername('guest');
        $joinParameters->setFirstName($name);
        $join_url = $driver->getJoinMeetingUrl($joinParameters);
        header('Status: 301 Moved Permanently', false, 301);
        header('Location:' . $join_url);
        die;
    }

    /**
    * Replaces the names with Umlauts
    *
    * @param string $string the string to replace the umlauts
    * @return string 
    */
    private function sonderzeichen($string) {
        $string = str_replace("ä", "ae", $string);
        $string = str_replace("ü", "ue", $string);
        $string = str_replace("ö", "oe", $string);
        $string = str_replace("Ä", "Ae", $string);
        $string = str_replace("Ü", "Ue", $string);
        $string = str_replace("Ö", "Oe", $string);
        $string = str_replace("ß", "ss", $string);
        $string = str_replace("´", "", $string);
        return $string;
    }
}
