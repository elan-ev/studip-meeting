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

        if (!$perm->have_studip_perm('user', $cid)) {
            throw new \AccessDeniedException();
        }

        $meeting = Meeting::find($room_id);

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

        //putting mandatory logoutURL into features
        if ($features = json_decode($meeting->features, true)) {
            if (!isset($features['logoutURL'])) {
                $hostUrl = $request->getUri()->getScheme() . '://' . $request->getUri()->getHost()
                    .($request->getUri()->getPort() ? ':' . $request->getUri()->getPort() : '');
                $features['logoutURL'] =  $hostUrl . \PluginEngine::getLink('meetingplugin', array('cid' => $cid), 'index');
            }

            //update/removing opencast series id if the OpenCast is not activated in the course or it has been changed unnoticed!
            if (isset($features['meta_opencast-dc-isPartOf']) && !empty($features['meta_opencast-dc-isPartOf'])) {
                if (isset($features['record']) && $features['record'] == true
                    && Driver::getConfigValueByDriver($meeting->driver, 'opencast') == 1) {
                    $current_series_id = MeetingPlugin::checkOpenCast($cid);
                    if (empty($current_series_id)) { // Opencast is not activated for this course
                        unset($features['meta_opencast-dc-isPartOf']);
                    } else if ($current_series_id != $features['meta_opencast-dc-isPartOf']) {
                        $features['meta_opencast-dc-isPartOf'] = $current_series_id;
                    }
                } else {
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
            $meetingCourse = new MeetingCourse([$room_id, $cid ]);
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
