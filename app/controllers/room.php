<?php

use ElanEv\Driver\DriverFactory;
use ElanEv\Driver\JoinParameters;
use ElanEv\Model\Driver;
use ElanEv\Model\InvitationsLink;
use ElanEv\Model\ModeratorInvitationsLink;
use ElanEv\Model\MeetingCourse;
use ElanEv\Model\Meeting;
use ElanEv\Model\QRCodeToken;
use Meetings\Helpers\MeetingsHelper;

class RoomController extends MeetingsController
{
    /**
     * Constructs the controller and provide translations methods.
     *
     * @param \Trails\Dispatcher $dispatcher
     *
     * @see https://stackoverflow.com/a/12583603/982902 if you need to overwrite
     *      the constructor of the controller
     */
    public function __construct(\Trails\Dispatcher $dispatcher)
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
        $variables = method_exists($this, 'get_assigned_variables') ? $this->get_assigned_variables() : get_object_vars($this);
        if (isset($variables[$method]) && is_callable($variables[$method])) {
            return call_user_func_array($variables[$method], $arguments);
        }
        return parent::__call($method, $arguments);
    }

    public function index_action($link_hex, $cid)
    {
        PageLayout::setTitle('Stud.IP Meeting');

        $this->invitations_link = InvitationsLink::findOneBySQL('hex = ?', [$link_hex]);
        if (!$this->invitations_link || !$this->invitations_link->meeting) {
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

        // Display Privacy Agreement.
        $showRecordingPrivacyText = Driver::getGeneralConfigValue('show_recording_privacy_text');
        if ($showRecordingPrivacyText && isset($features['record']) && $features['record'] == 'true') {
            $this->check_recording_privacy_agreement = true;
        }

        $widget = new SidebarWidget();
        $widget->setTitle($this->_('Meeting-Name'));
        $widget->addElement(
            new WidgetElement(htmlReady($this->invitations_link->meeting->name))
        );
        Sidebar::Get()->addWidget($widget);
    }

    public function moderator_action($link_hex, $cid)
    {
        PageLayout::setTitle('Stud.IP Meeting');

        $this->moderator_invitations_link = ModeratorInvitationsLink::findOneBySQL('hex = ?', [$link_hex]);
        if (!$this->moderator_invitations_link || !$this->moderator_invitations_link->meeting) {
            throw new Exception($this->_('Das gesuchte Meeting existiert nicht mehr!'));
        }

        $meeting = $this->moderator_invitations_link->meeting;

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
        if (isset($features['invite_moderator']) && $features['invite_moderator'] == "false") {
            throw new Exception($this->_('Das gesuchte Meeting ist nicht verfügbar!'));
        }

        // Display Privacy Agreement.
        $showRecordingPrivacyText = Driver::getGeneralConfigValue('show_recording_privacy_text');
        if ($showRecordingPrivacyText && isset($features['record']) && $features['record'] == 'true') {
            $this->check_recording_privacy_agreement = true;
        }

        if (Request::isPost() && Request::submitted('accept')) {
            $password = htmlspecialchars(trim(Request::get('password')));
            $moderator_name = htmlspecialchars(trim(Request::get('name')));

            if (empty($password) || $this->moderator_invitations_link->password != $password) {
                $this->last_password = $password;
                $this->last_moderator_name = $moderator_name;
                PageLayout::postError($this->_('Zugangscode ist ungültig!'));
            } else if (!$moderator_name) {
                PageLayout::postError($this->_('Es kann kein gültiger Name festgelegt werden.'));
            } else if ($this->check_recording_privacy_agreement && empty(Request::get('recording_privacy_agreement'))) {
                // Checking Privacy Agreement.
                PageLayout::postError($this->_('Um dem Meeting beizutreten, muss dem Datenschutz zugestimmt werden!'));
            } else {
                MeetingsHelper::performJoinWithoutUser($meeting, $cid, 'guest_moderator', $moderator_name, true);
            }
        }

        $widget = new SidebarWidget();
        $widget->setTitle($this->_('Meeting-Name'));
        $widget->addElement(
            new WidgetElement(htmlReady($this->moderator_invitations_link->meeting->name))
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

        if (!$meeting) {
            throw new Exception($this->_('Das gesuchte Meeting existiert nicht mehr!'));
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
        } else {
            $this->redirect($link);
            return;
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

        MeetingsHelper::performJoinWithoutUser($meeting, $cid, 'guest', $name, false);
    }

    public function qrcode_action($link_hex, $cid)
    {
        PageLayout::setTitle('Stud.IP Meeting');

        $this->qr_code_token = QRCodeToken::findOneBySQL('hex = ?', [$link_hex]);
        if (!$this->qr_code_token || !$this->qr_code_token->meeting) {
            throw new Exception($this->_('Der QR-Code ist ungültig, versuchen Sie es erneut mit einem neuen QR-Code!'));
        }

        $meeting = $this->qr_code_token->meeting;
        $meetingCourse = new MeetingCourse([$meeting->id, $cid]);

        // Checking Course Type
        $servers = Driver::getConfigValueByDriver($meeting->driver, 'servers');
        $allow_course_type = MeetingPlugin::checkCourseType($meeting->courses->find($cid), $servers[$meeting->server_index]['course_types']);
        // Checking Server Active
        $active_server = $servers[$meeting->server_index]['active'];
        if (!$allow_course_type || !$active_server) {
            throw new Exception($this->_('Das gesuchte Meeting ist nicht verfügbar!'));
        }

        $this->cid = $cid;

        // Display Privacy Agreement.
        $features = json_decode($meeting->features, true);
        $showRecordingPrivacyText = Driver::getGeneralConfigValue('show_recording_privacy_text');
        if ($showRecordingPrivacyText && isset($features['record']) && $features['record'] == 'true') {
            $this->check_recording_privacy_agreement = true;
        }

        $driver = $this->driver_factory->getDriver($meeting->driver, $meeting->server_index);
        if (isset($features['room_anyone_can_start']) && $features['room_anyone_can_start'] === 'false') {
            $meetingCourse = new MeetingCourse([$meeting->id, $cid]);
            $status = $driver->isMeetingRunning($meetingCourse->meeting->getMeetingParameters()) === 'true' ? true : false;

            if (!$status) {
                $qrcode_lobby_link = "room/qrcode_lobby/{$meeting->id}/$cid/$link_hex/#lobby";
                $this->redirect($qrcode_lobby_link);
                return;
            }
        }

        if (Request::isPost() && Request::submitted('accept')) {
            $token = htmlspecialchars(trim(Request::get('token')));
            if (empty($token) || $this->qr_code_token->token != $token) {
                $this->last_token = $token;
                PageLayout::postError($this->_('Zugangscode ist ungültig!'));
            } else if ($this->check_recording_privacy_agreement && empty(Request::get('recording_privacy_agreement'))) {
                // Checking Privacy Agreement.
                PageLayout::postError($this->_('Um dem Meeting beizutreten, muss dem Datenschutz zugestimmt werden!'));
            } else {
                $can_join = MeetingsHelper::performJoinWithQRCode($this->qr_code_token, $cid);
                if ($can_join == false) {
                    PageLayout::postError($this->_('Etwas ist schief gelaufen, versuche es noch einmal mit dem neuen QR-Code!'));
                }
            }
        }

        $widget = new SidebarWidget();
        $widget->setTitle($this->_('Meeting-Name'));
        $widget->addElement(
            new WidgetElement(htmlReady($this->qr_code_token->meeting->name))
        );
        Sidebar::Get()->addWidget($widget);
    }

    public function qrcode_lobby_action($room_id, $cid, $qrcode_hex)
    {
        $this->qr_code_token = QRCodeToken::findOneBySQL('hex = ?', [$qrcode_hex]);
        if (!$this->qr_code_token || $this->qr_code_token->meeting_id != $room_id) {
            PageLayout::postError($this->_('Ungültige QR-Code-Daten!'));
        }

        $meeting = $this->qr_code_token->meeting;
        if (!$meeting) {
            throw new Exception($this->_('Das gesuchte Meeting existiert nicht mehr!'));
        }

        $qrcode_link = "room/qrcode/$qrcode_hex/$cid";

        $features = json_decode($meeting->features, true);
        $driver = $this->driver_factory->getDriver($meeting->driver, $meeting->server_index);
        if (isset($features['room_anyone_can_start']) && $features['room_anyone_can_start'] === 'false') {
            $meetingCourse = new MeetingCourse([$meeting->id, $cid]);
            $status = $driver->isMeetingRunning($meetingCourse->meeting->getMeetingParameters()) === 'true' ? true : false;

            if ($status) {
                $this->redirect($qrcode_link);
                return;
            }
        } else {
            $this->redirect($qrcode_link);
            return;
        }
    }

    public function public_action($room_id, $cid, $qr = false)
    {
        $room_id = filter_var($room_id, FILTER_SANITIZE_NUMBER_INT);
        $cid = htmlspecialchars($cid);
        PageLayout::setTitle('Stud.IP Meeting');

        $is_public = MeetingPlugin::isCoursePublic($cid);

        $meeting = Meeting::find($room_id);
        if (!$is_public || !$meeting) {
            throw new Exception($this->_('Das gesuchte Meeting ist nicht verfügbar!'));
        }

        // Checking Course Type
        $servers = Driver::getConfigValueByDriver($meeting->driver, 'servers');
        $allow_course_type = MeetingPlugin::checkCourseType($meeting->courses->find($cid), $servers[$meeting->server_index]['course_types']);
        // Checking Server Active
        $active_server = $servers[$meeting->server_index]['active'];
        if (!$allow_course_type || !$active_server) {
            throw new Exception($this->_('Das gesuchte Meeting ist nicht verfügbar!'));
        }

        $this->cid = $cid;
        $this->room_id = $room_id;
        $this->qr = $qr;

        $features = json_decode($meeting->features, true);
        $driver = $this->driver_factory->getDriver($meeting->driver, $meeting->server_index);
        if (isset($features['room_anyone_can_start']) && $features['room_anyone_can_start'] === 'false') {
            $meetingCourse = new MeetingCourse([$meeting->id, $cid]);
            $status = $driver->isMeetingRunning($meetingCourse->meeting->getMeetingParameters()) === 'true' ? true : false;

            if (!$status) {
                $public_lobby_link = "room/public_lobby/{$meeting->id}/$cid" . ($qr ? '/1' : '') . '/#lobby';
                $this->redirect($public_lobby_link);
                return;
            }
        }

        // Display Privacy Agreement.
        $showRecordingPrivacyText = Driver::getGeneralConfigValue('show_recording_privacy_text');
        if ($qr && $showRecordingPrivacyText && isset($features['record']) && $features['record'] == 'true') {
            $this->check_recording_privacy_agreement = true;
        }

        if (Request::isPost() && Request::submitted('accept')) {
            $name = trim(Request::get('name'));
            if (!$name) {
                PageLayout::postError($this->_('Um dem Meeting beizutreten, ein Name ist erforderlich!'));
            } else if ($qr && $this->check_recording_privacy_agreement && empty(Request::get('recording_privacy_agreement'))) {
                // Checking Privacy Agreement.
                PageLayout::postError($this->_('Um dem Meeting beizutreten, muss dem Datenschutz zugestimmt werden!'));
            } else {
                MeetingsHelper::performJoinWithoutUser($meeting, $cid, 'guest', $name, false);
            }
        }

        $widget = new SidebarWidget();
        $widget->setTitle($this->_('Meeting-Name'));
        $widget->addElement(
            new WidgetElement(htmlReady($meeting->name))
        );
        Sidebar::Get()->addWidget($widget);
    }

    public function public_lobby_action($room_id, $cid, $qr = false)
    {
        $room_id = filter_var($room_id, FILTER_SANITIZE_NUMBER_INT);
        $cid = htmlspecialchars($cid);

        $this->is_public = MeetingPlugin::isCoursePublic($cid);

        $meeting = Meeting::find($room_id);
        $link = 'room/public/' . $room_id . '/' . $cid . ($qr ? '/1' : '');

        if (!$this->is_public || !$meeting) {
            throw new Exception($this->_('Das gesuchte Meeting existiert nicht mehr!'));
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
        } else {
            $this->redirect($link);
        }
    }

    public function display_message_action() {
        PageLayout::setTitle('Stud.IP Meeting');
        if ($err = Request::get('err')) {
            if ($err == 'server-inactive') {
                PageLayout::postError(_('Der ausgewählte Server ist deaktiviert.'));
            }
            if ($err == 'course-type') {
                PageLayout::postError(_('Der ausgewählte Server ist in diesem Veranstaltungstyp nicht verfügbar.'));
            }
            if ($err == 'accessdenied') {
                throw new AccessDeniedException();
            }
        }
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
