<?php

namespace Meetings\Routes\Rooms;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Meetings\Errors\AuthorizationFailedException;
use Meetings\MeetingsTrait;
use Meetings\MeetingsController;
use Meetings\Errors\Error;
use Exception;
use Meetings\Models\I18N as _;

use ElanEv\Model\MeetingCourse;
use ElanEv\Model\Meeting;
use ElanEv\Model\Helper;
use ElanEv\Driver\DriverFactory;
use ElanEv\Model\Driver;
use MeetingPlugin;

class RoomAdd extends MeetingsController
{
    use MeetingsTrait;
    /**
     * Create meeting room in a course with specific driver
     *
     * @param string $json['cid'] course id
     * @param string $json['name'] meeting room name
     * @param string $json['driver'] name of driver
     * @param string $json['server_index'] driver server index
     * @param boolean $json['join_as_moderator'] moderator permission
     *
     *
     * @return json success: "message"
     *
     * @throws \Error if the the driver is not abel to create the meeting room
     * @throws \Exception \Error if something goes wrong with driver room creation
     */

    public function __invoke(Request $request, Response $response, $args)
    {
        global $perm;
        $json = $this->getRequestData($request);
        if (!$perm->have_studip_perm('tutor', $json['cid'])) {
            throw new Error(_('Access Denied'), 403);
        }
        try {
            $has_error = false;
            $error_text = '';
            $user = $GLOBALS['user'];
            $driver_factory = new DriverFactory(Driver::getConfig());

            $exists = false;
            foreach (MeetingCourse::findByUser($user) as $meetingCourse) {
                if (self::meeting_exists($meetingCourse, $json)) {
                    $exists = true;
                }
            }
            //validations
            if ($exists) {
                $has_error = true;
                $error_text = _('Es existiert bereits ein gleichnamiger Raum.');
            }

            if (empty($json['name'])) {
                $has_error = true;
                $error_text = _('Der Raumname darf nicht leer sein');
            }

            if (empty($json['driver'])) {
                $has_error = true;
                $error_text = _('Es ist kein Konferenzsystem ausgewählt');
            }

            if (!is_numeric($json['server_index'])) {
                $has_error = true;
                $error_text = _('Server ist nicht definiert');
            }

            $servers = Driver::getConfigValueByDriver($json['driver'], 'servers');
            $server_maxParticipants = $servers[$json['server_index']]['maxParticipants'];
            if (is_numeric($server_maxParticipants) && $server_maxParticipants > 0 && $json['features']['maxParticipants'] > $server_maxParticipants) {
                $has_error = true;
                $error_text = sprintf(_('Teilnehmerzahl darf %d nicht überschreiten'), $server_maxParticipants);
            }

            if (!$has_error) {
                //putting mandatory logoutURL into features
                $hostUrl = $request->getUri()->getScheme() . '://' . $request->getUri()->getHost()
                        .($request->getUri()->getPort() ? ':' . $request->getUri()->getPort() : '');
                $json['features']['logoutURL'] = $hostUrl . \PluginEngine::getLink('meetingplugin', array('cid' => $json['cid']), 'index');

                //Adding default "duration" of 240 Minutes into features if it is not set
                if (!isset($json['features']['duration']) || !is_numeric($json['features']['duration'])) {
                    $json['features']['duration'] = "240";
                }

                //Handle recording stuff
                $record = 'false';
                $opencast_series_id = '';
                if (Driver::getConfigValueByDriver($json['driver'], 'record')) { //config double check
                    if (isset($json['features']['record']) && $json['features']['record'] == 'true') { //user record request
                        $record = 'true';
                        if (Driver::getConfigValueByDriver($json['driver'], 'opencast')) { // config check for opencast
                            $series_id = MeetingPlugin::checkOpenCast($json['cid']);
                            if ($series_id) {
                                $opencast_series_id = $series_id;
                            } else {
                                throw new Error(_('Opencast Series id kann nicht gefunden werden!'), 404);
                            }
                        }
                    }
                }
                $json['features']['record'] = $record;
                !$opencast_series_id ?: $json['features']['meta_opencast-dc-isPartOf'] = $opencast_series_id;

                $meeting = new Meeting();
                $meeting->courses[] = new \Course($json['cid']);
                $meeting->user_id = $user->id;
                $meeting->name = trim($json['name']);
                $meeting->driver = $json['driver'];
                $meeting->server_index = $json['server_index'];
                $meeting->attendee_password = Helper::createPassword();
                $meeting->moderator_password = Helper::createPassword();
                $meeting->join_as_moderator = $json['join_as_moderator'];
                $meeting->remote_id = md5(uniqid());
                $meeting->features = json_encode($json['features']);
                $meeting->store();
                $meetingParameters = $meeting->getMeetingParameters();

                //adding group into MeetingCourse if exists
                if (isset($json['group_id']) && !empty($json['group_id'])) {
                    $meetingCourse = new MeetingCourse([$meeting->id, $json['cid']]);
                    $meetingCourse->group_id = $json['group_id'];
                    $meetingCourse->store();
                }

                $driver = $driver_factory->getDriver($json['driver'], $json['server_index']);

                try {
                    if (!$driver->createMeeting($meetingParameters)) {
                        self::revert_on_fail($meeting, $json['cid']);
                        throw new Error(sprintf('Meeting mit %s Treiber kann nicht erstellt werden', $json['driver']), 404);
                    }
                } catch (Exception $e) {
                    self::revert_on_fail($meeting, $json['cid']);
                    throw new Error($e->getMessage(), 404);
                }

                $meeting->remote_id = $meetingParameters->getRemoteId();
                $meeting->store();

                $message = [
                    'text' => _('Raum wurde erfolgreich erstellt.'),
                    'type' => 'success'
                ];
            } else {
                $message = [
                    'text' => $error_text,
                    'type' => 'error'
                ];
            }

        } catch (Exception $e) {
            throw new Error($e->getMessage(), 404);
        }

        return $this->createResponse([
            'message'=> $message,
        ], $response);
    }

    /**
     * Checks if a meeting is identically exists
     *
     * @param \MeetingCourse $meetingCourse user defined course meeting
     *
     * @param array $data request data
     *
     * @return boolean
     */
    private function meeting_exists($meetingCourse, $data)
    {
        if ($meetingCourse->course_id == $data['cid']
            && trim($meetingCourse->meeting->name) == trim($data['name'])
            && $meetingCourse->meeting->driver == $data['driver']
            && $meetingCourse->meeting->server_index == $data['server_index']) {
                return true;
        } else {
            return false;
        }
    }

    /**
     * Delete the meeting on failure
     *
     * @param \Meeting $meeting
     * @param string $cid course id
     *
     */
    private function revert_on_fail($meeting, $cid)
    {
        $meetingCourse = new MeetingCourse([$meeting->id, $cid ]);
        $meetingCourse->delete();
        $meeting->delete();
    }

}
