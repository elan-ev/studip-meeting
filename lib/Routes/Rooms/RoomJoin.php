<?php

namespace Meetings\Routes\Rooms;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Meetings\Errors\AuthorizationFailedException;
use Meetings\MeetingsTrait;
use Meetings\MeetingsController;
use Meetings\Errors\Error;
use Exception;
use Meetings\Models\I18N;

use ElanEv\Model\MeetingCourse;
use ElanEv\Model\Meeting;
use ElanEv\Driver\JoinParameters;
use ElanEv\Model\Join;
use ElanEv\Model\Helper;
use ElanEv\Driver\DriverFactory;
use ElanEv\Model\Driver;
use MeetingPlugin;

class RoomJoin extends MeetingsController
{
    use MeetingsTrait;
    /**
     * Returns the parameters of a selected room
     *
     * @param string $room_id room id
     * @param string $cid course id
     *
     *
     * @return json redirect parameter
     *
     * @throws \Error if there is any problem
     */
    public function __invoke(Request $request, Response $response, $args)
    {
        global $perm, $user;
        $driver_factory = new DriverFactory(Driver::getConfig());
        $room_id = $args['room_id'];
        $cid = $args['cid'];

        $meetingCourse = new MeetingCourse([$room_id, $cid ]);
        // Check Assigned Group
        $meetingCourse = $this->checkAssignedGroup($meetingCourse);

        // Check group access permission
        if (!$perm->have_studip_perm('user', $cid) || ($meetingCourse->group_id && !$this->checkGroupPermission($meetingCourse->group_id, $cid))) {
            header('Location:' .
                \URLHelper::getURL(
                    'plugins.php/meetingplugin/index',
                    ['cid' => $cid, 'err' => 'accessdenied']
                )
            );
            exit;
        }

        $meeting = $meetingCourse->meeting;

        // Checking folder existence
        $this->checkAssignedFolder($meeting);

        if (!($meeting && $meeting->courses->find($cid))) {
            throw new Error(I18N::_('Dieser Raum in diesem Kurs kann nicht gefunden werden!'), 404);
        }

        // Checking Course Type
        $servers = Driver::getConfigValueByDriver($meeting->driver, 'servers');
        $allow_course_type = MeetingPlugin::checkCourseType($meeting->courses->find($cid), $servers[$meeting->server_index]['course_types']);
        //Checking Server Active
        $active_server = $servers[$meeting->server_index]['active'];

        if (!$allow_course_type || !$active_server) {
            $err = ($allow_course_type == false) ? 'course-type' : 'server-inactive';
            header('Location:' .
                \URLHelper::getURL(
                    'plugins.php/meetingplugin/index',
                    ['cid' => $cid, 'err' => $err]
                )
            );
            exit;
        }

        if ($features = json_decode($meeting->features, true)) {
            //putting mandatory logoutURL into features
            if (!isset($features['logoutURL'])) {
                $hostUrl = $request->getUri()->getScheme() . '://' . $request->getUri()->getHost()
                    .($request->getUri()->getPort() ? ':' . $request->getUri()->getPort() : '');
                $features['logoutURL'] =  $hostUrl . \PluginEngine::getLink('meetingplugin', array('cid' => $cid), 'index');
            }

            // Check Recording Capability
            if (isset($features['record']) && filter_var($features['record'], FILTER_VALIDATE_BOOLEAN)) {
                $recording_capability = $this->checkRecordingCapability($meeting->driver, $cid);
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

        $driver = $driver_factory->getDriver($meeting->driver, $meeting->server_index);

        if (isset($features['room_anyone_can_start'])
            && $features['room_anyone_can_start'] === 'false'
            && !$perm->have_studip_perm('tutor', $cid)
        ) {
            $status = $driver->isMeetingRunning($meetingCourse->meeting->getMeetingParameters()) === 'true' ? true : false;

            if (!$status) {
                header('Location:' .
                    \URLHelper::getURL(
                        'plugins.php/meetingplugin/room/lobby/' . $room_id . '/' . $cid . '/#lobby',
                        ['cancel_login' => 1]
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
}
