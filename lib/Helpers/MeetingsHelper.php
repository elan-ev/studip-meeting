<?php

namespace Meetings\Helpers;

use Meetings\Helpers\RoomManager;
use Meetings\Errors\Error;
use ElanEv\Model\Driver;
use ElanEv\Model\Join;
use ElanEv\Model\MeetingCourse;
use ElanEv\Model\Meeting;
use ElanEv\Model\QRCodeToken;
use Meetings\Models\I18N;
use ElanEv\Driver\DriverFactory;
use ElanEv\Driver\JoinParameters;

use Context;
use MeetingPlugin;
use URLHelper;
use Seminar_User;
use Avatar;
/**
 * MeetingsHelper.php - contains CRUD functions to controll room requests.
 *
 * @author Farbod Zamani Broujeni (zamani@elan-ev.de)
 */
class MeetingsHelper
{
    /**
    * Evaluates and performs the join request.
    * Redirect may occure upon failure/success!
    *
    * @param int $room_id meeting id
    * @param string $cid course id
    * @param array $lobby_redirect if defined, it redirects users to the given lobby when needed.
    * @param array $error_redirect if defined, it redirects users to the given error url when error happens.
    *
    * @throws Error
    */
    public static function performJoin($room_id, $cid, $lobby_redirect = [], $error_redirect = []) {
        global $perm, $user;

        $driver_factory = new DriverFactory(Driver::getConfig());

        $meetingCourse = new MeetingCourse([$room_id, $cid]);
        $meeting = $meetingCourse->meeting;

        if (!($meeting && $meeting->courses->find($cid)) || $meetingCourse->isNew()) {
            throw new Error(I18N::_('Dieser Raum in diesem Kurs kann nicht gefunden werden!'), 404);
        }

        Context::set($cid);

        // Checking folder existence
        RoomManager::checkAssignedFolder($meeting);

        // Check Assigned Group
        $meetingCourse = RoomManager::checkAssignedGroup($meetingCourse);

        // Pre-define error redirect variables
        $error_redirect_url = 'plugins.php/meetingplugin/index';
        $error_redirect_params['cid'] = $cid;
        if (!empty($error_redirect)) {
            if (!empty($error_redirect['url'])) {
                $error_redirect_url = $error_redirect['url'];
            }
            if (!empty($error_redirect['params'])) {
                $error_redirect_params = array_merge($error_redirect_params, $error_redirect['params']);
            }
        }

        // Check group access permission
        if (!$perm->have_studip_perm('user', $cid) || ($meetingCourse->group_id && !RoomManager::checkGroupPermission($meetingCourse->group_id, $cid))) {
            $error_redirect_params['err'] = 'accessdenied';
            header('Location:' .
                URLHelper::getURL(
                    $error_redirect_url,
                    $error_redirect_params
                )
            );
            exit;
        }

        // Checking Course Type
        $servers = Driver::getConfigValueByDriver($meeting->driver, 'servers');
        $allow_course_type = MeetingPlugin::checkCourseType($meeting->courses->find($cid), $servers[$meeting->server_index]['course_types']);
        //Checking Server Active
        $active_server = $servers[$meeting->server_index]['active'];

        if (!$allow_course_type || !$active_server) {
            $err = ($allow_course_type == false) ? 'course-type' : 'server-inactive';
            $error_redirect_params['err'] = $err;
            header('Location:' .
                URLHelper::getURL(
                    $error_redirect_url,
                    $error_redirect_params
                )
            );
            exit;            
        }

        self::adjustFeaturesBeforeJoin($meeting, $cid);

        $features = json_decode($meeting->features, true);
        $driver = $driver_factory->getDriver($meeting->driver, $meeting->server_index);

        if (isset($features['room_anyone_can_start'])
            && $features['room_anyone_can_start'] === 'false'
            && !$perm->have_studip_perm('tutor', $cid)
        ) {
            $status = $driver->isMeetingRunning($meetingCourse->meeting->getMeetingParameters()) === 'true' ? true : false;

            if (!$status) {
                $lobby_url = 'plugins.php/meetingplugin/room/lobby/' . $room_id . '/' . $cid . '/#lobby';
                $lobby_params['cancel_login'] = 1;
                if (!empty($lobby_redirect)) {
                    if (!empty($lobby_redirect['url'])) {
                        $lobby_url = $lobby_redirect['url'];
                    }
                    if (!empty($lobby_redirect['params'])) {
                        $lobby_params = array_merge($lobby_params, $lobby_redirect['params']);
                    }
                }
                header('Location:' .
                    URLHelper::getURL(
                        $lobby_url,
                        $lobby_params
                    )
                );
                exit;
            }
        }

        
        $joinParameters = new JoinParameters();
        $joinParameters->setMeetingId($room_id);
        $joinParameters->setIdentifier($meeting->identifier);
        $joinParameters->setRemoteId($meeting->remote_id);
        $joinParameters->setUsername(\get_username($user->id));
        $joinParameters->setEmail($user->Email);
        $joinParameters->setFirstName($user->Vorname);
        $joinParameters->setLastName($user->Nachname);
        $joinParameters->setMeeting($meeting);
        
        // Getting user's avatar url.
        $avatar = Avatar::getAvatar($user->id);
        if ($avatar && $avatar->is_customized()) {
            $joinParameters->setAvatarUrl($avatar->getURL(Avatar::SMALL));
        }

        if ($perm->have_studip_perm('tutor', $cid) || $meeting->join_as_moderator) {
            $joinParameters->setPassword($meeting->moderator_password);
            $joinParameters->setHasModerationPermissions(true);
        } else {
            $joinParameters->setPassword($meeting->attendee_password);
            $joinParameters->setHasModerationPermissions(false);
        }

        $lastJoin = new Join();
        $lastJoin->meeting_id = $room_id;
        $lastJoin->user_id = $user->id;
        $lastJoin->last_join = time();
        $lastJoin->store();

        $error_message = '';
        try {
            if ($join_url = $driver->getJoinMeetingUrl($joinParameters)) {
                // Clear QR Code Token.
                self::clearQRCode($meeting->id, $user->id);
                // directly redirect to room
                header('Location: ' . $join_url);
                exit;
            } else {
                $error_message = I18N::_('Konnte dem Meeting nicht beitreten, Kommunikation mit dem Meeting-Server fehlgeschlagen.');
            }
        } catch (Exception $e) {
            $error_message = I18N::_('Konnte dem Meeting nicht beitreten, Kommunikation mit dem Meeting-Server fehlgeschlagen. ('. $e->getMessage() .')');
            throw new Error($error_message, ($e->getCode() ? $e->getCode() : 404));
        }

        throw new Error($error_message, 404);
    }

    /**
     * Evaluates and perform join with QR Code join request.
     * 
     * @param string $cid course id
     * @param QRCodeToken $qrcode_token the qrcode token object
     * 
     */
    public static function performJoinWithQRCode(QRCodeToken $qrcode_token, $cid)
    {
        $room_id = $qrcode_token->meeting_id;
        $user_id = $qrcode_token->user_id;
        if (!$room_id || !$cid || !$user_id) {
            return false;
        }

        $sem_user = new Seminar_User($user_id);
        $GLOBALS['user'] = $sem_user;

        try {
            $lobby_redirect = [
                'url' => 'plugins.php/meetingplugin/room/qrcode_lobby/' . $room_id . '/' . $cid . '/' . $qrcode_token->hex . '/#lobby',
                'params' => [
                    'cancel_login' => 1
                ],
            ];
            $error_redirect = [
                'url' => 'plugins.php/meetingplugin/room/display_message',
                'params' => [
                    'cancel_login' => 1
                ],
            ];
            self::performJoin($room_id, $cid, $lobby_redirect, $error_redirect);
        } catch (Exception $e) {
            throw new Error($e->getMessage(), ($e->getCode() ? $e->getCode() : 404));
        }
    }

    /**
    * Generates new QR-Code token object to be consumed by QR-Code generator.
    *
    * @param int $room_id the id of the meeting
    * @param string $cid course id
    * @return array/boolean
    */
    public static function generateQRCode($room_id, $cid)
    {
        global $user, $perm;

        $meetingCourse = new MeetingCourse([$room_id, $cid]);
        if (!$room_id || !$cid || $meetingCourse->isNew()) {
            return false;
        }

        $new_token = random_int(10000, 99999);
        $new_hex = md5(uniqid());

        $qr_code_token = QRCodeToken::findOneBySQL('meeting_id = ? AND user_id = ?', [$room_id, $user->id]);
        if (!$qr_code_token) {
            $qr_code_token = new QRCodeToken();
            $qr_code_token->meeting_id = $room_id;
            $qr_code_token->user_id = $user->id;
        }
        $qr_code_token->hex = $new_hex;
        $qr_code_token->token = $new_token;
        $qr_code_token->store();
        
        $old_url_helper_url = URLHelper::setBaseURL($GLOBALS['ABSOLUTE_URI_STUDIP']);
        $join_url =
            URLHelper::getURL(
                'plugins.php/meetingplugin/room/qrcode/' . $qr_code_token->hex . '/' . $cid,
                ['cancel_login' => 1]
            );
        URLHelper::setBaseURL($old_url_helper_url);
        $qr_code = [
            'url' => $join_url,
            'token' => $qr_code_token->token
        ];
        return $qr_code;
    }

    /**
    * It clears the QR Code token record after the user has joined the meeting.
    *
    * @param int $room_id the id of the meeting
    * @param string $user_id the id of the user
    * @return boolean
    */
    private static function clearQRCode($room_id, $user_id)
    {
        $qr_code_token = QRCodeToken::findOneBySQL('meeting_id = ? AND user_id = ?', [$room_id, $user_id]);
        if ($qr_code_token) {
            $qr_code_token->delete();
        }
    }

    /**
     * Performs join for requests that have no authenticated user,
     * such as regular and moderator guests.
     *
     * @param Meeting $meeting the meeting object
     * @param string $cid course id
     * @param string $username username of the guest user to be displayed
     * @param string $firstname firstname of the guest user to be displayed
     * @param bool $is_moderator whether the join is for a moderator or not
     *
     * @throws Error
     */
    public static function performJoinWithoutUser(Meeting $meeting, $cid, $username, $firstname, $is_moderator = false)
    {
        self::adjustFeaturesBeforeJoin($meeting, $cid);

        $password = $is_moderator ? $meeting->moderator_password : $meeting->attendee_password;

        $driver_factory = new DriverFactory(Driver::getConfig());
        $driver = $driver_factory->getDriver($meeting->driver, $meeting->server_index);

        $joinParameters = new JoinParameters();
        $joinParameters->setMeetingId($meeting->id);
        $joinParameters->setIdentifier($meeting->identifier);
        $joinParameters->setRemoteId($meeting->remote_id);
        $joinParameters->setPassword($password);
        $joinParameters->setHasModerationPermissions($is_moderator);
        $joinParameters->setUsername($username);
        $joinParameters->setFirstName($firstname);
        
        $error_message = '';
        try {
            if ($join_url = $driver->getJoinMeetingUrl($joinParameters)) {
                // directly redirect to room
                header('Status: 301 Moved Permanently', false, 301);
                header('Location:' . $join_url);
                die;
            } else {
                $error_message = I18N::_('Konnte dem Meeting nicht beitreten, Kommunikation mit dem Meeting-Server fehlgeschlagen.');
            }
        } catch (Exception $e) {
            $error_message = I18N::_('Konnte dem Meeting nicht beitreten, Kommunikation mit dem Meeting-Server fehlgeschlagen. ('. $e->getMessage() .')');
            throw new Error($error_message, ($e->getCode() ? $e->getCode() : 404));
        }

        throw new Error($error_message, 404);
    }

    /**
     * Adjusts the meeting feature parameters before joining the room
     *
     * @param Meeting $meeting the meeting object
     * @param string $cid course id
     *
     */
    private static function adjustFeaturesBeforeJoin(Meeting $meeting, $cid)
    {
        if ($features = json_decode($meeting->features, true)) {
            //putting mandatory logoutURL into features
            
            $logout_url = RoomManager::generateMeetingBaseURL('index/return', ['cid' => $cid]);
            if (!isset($features['logoutURL']) || $features['logoutURL'] != $logout_url) {
                $features['logoutURL'] = $logout_url;
            }

            // Check Recording Capability
            if (isset($features['record']) && filter_var($features['record'], FILTER_VALIDATE_BOOLEAN)) {
                $recording_capability = RoomManager::checkRecordingCapability($meeting->driver, $cid);
                if ($recording_capability['allow_recording'] == true
                    && $recording_capability['type'] == 'opencast'
                    && !empty($recording_capability['seriesid'])) {
                    $features['meta_opencast-dc-isPartOf'] = $recording_capability['seriesid'];
                } else if (isset($features['meta_opencast-dc-isPartOf'])) {
                    unset($features['meta_opencast-dc-isPartOf']);
                }
            }
            $meeting->features = json_encode($features);
            $meeting->store();
        }
    }
}