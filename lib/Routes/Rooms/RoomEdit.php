<?php

namespace Meetings\Routes\Rooms;

use DateTime;
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
use ElanEv\Model\Driver;
use MeetingPlugin;

class RoomEdit extends MeetingsController
{
    use MeetingsTrait;
    /**
     * Edits meeting room
     *
     * @param string $room_id room id
     * @param string $json['name'] meeting room name
     * @param string $json['recording_url'] recording url of the room
     * @param string $json['cid'] course id
     * @param boolean $json['join_as_moderator'] moderator permission
     * @param boolean $json['active'] moderator permission
     *
     * @return json message 
     *
     */
    public function __invoke(Request $request, Response $response, $args)
    {
        global $perm;
        $json = $this->getRequestData($request);
        if (!$perm->have_studip_perm('tutor', $json['cid'])) {
            throw new Error(_('Access Denied'), 403);
        }

        $room_id = $args['room_id'];
        
        $meetingCourse = new MeetingCourse([$room_id, $json['cid']]);
        $name = trim($json['name']);
        $allow_change_driver = (isset($json['driver_name']) && !empty($json['driver_name'])) || !isset($json['driver_name']);
        $allow_change_server_index = (isset($json['server_index']) && is_numeric($json['server_index'])) || !isset($json['server_index']);

        $message = [];
        if (!$meetingCourse->isNew() && $name && $allow_change_driver && $allow_change_server_index) {
            $change_date = new \DateTime();
            if (isset($json['active'])) {
                $meetingCourse->active = $json['active'];
                $meetingCourse->store();
            }
            if (isset($json['group_id'])) {
                $meetingCourse->group_id = ((empty($json['group_id']) ? null : $json['group_id']));
                $meetingCourse->store();
            }
            $meeting = $meetingCourse->meeting;
            $meeting->name = $name;
            !isset($json['recordingUrl']) ?: $meeting->recording_url = utf8_decode($json['recording_url']);
            !isset($json['join_as_moderator']) ?: $meeting->join_as_moderator = $json['join_as_moderator'];
            !isset($json['driver_name']) ?: $meeting->driver = $json['driver_name'];
            !isset($json['server_index']) ?: $meeting->server_index = $json['server_index'];

            // apply default features

            if (isset($json['features'])) {
                if (!is_numeric($json['features']['duration'])) {
                    $json['features']['duration'] = "240";
                }

                //Handle recording stuff
                $record = 'false';
                $opencast_series_id = '';
                if (Driver::getConfigValueByDriver($json['driver_name'], 'record')) { //config double check
                    if (isset($json['features']['record']) && $json['features']['record'] == 'true') { //user record request
                        $record = 'true';
                        if (Driver::getConfigValueByDriver($json['driver_name'], 'opencast')) { // config check for opencast
                            $series_id = MeetingPlugin::checkOpenCast($json['cid']);
                            if ($series_id) {
                                $opencast_series_id = $series_id;
                            } else {
                                $message = [
                                    'text' => _('Opencast Series id kann nicht gefunden werden!'),
                                    'type' => 'error'
                                ];
                                return $this->createResponse([
                                    'message'=> $message,
                                ], $response);
                            }
                        }
                    }
                }
                $json['features']['record'] = $record;
                !$opencast_series_id ?: $json['features']['meta_opencast-dc-isPartOf'] = $opencast_series_id;

                //validate maxParticipants if the server has default
                $servers = Driver::getConfigValueByDriver($json['driver_name'], 'servers');
                $server_maxParticipants = $servers[$json['server_index']]['maxParticipants'];
                if (is_numeric($server_maxParticipants) && $server_maxParticipants > 0 && $json['features']['maxParticipants'] > $server_maxParticipants) {
                    $message = [
                        'text' => sprintf(_('Teilnehmerzahl darf %d nicht überschreiten'), $server_maxParticipants),
                        'type' => 'error'
                    ];
                    return $this->createResponse([
                        'message'=> $message,
                    ], $response);
                }

                $meeting->features = json_encode($json['features']);
            }
            $meeting->chdate = $change_date->getTimestamp();
            $meeting->store();
            $message = [
                'text' => _('Die Bearbeitung wurde erfolgreich abgeschlossen.'),
                'type' => 'success'
            ];
        } else {
            $message = [
                'text' => _('Raumeinstellung kann nicht bearbeitet werden!'),
                'type' => 'error'
            ];
        }

        return $this->createResponse([
            'message'=> $message,
        ], $response);
    }
}
