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
use Meetings\Models\I18N;

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
        $allow_change_driver = (isset($json['driver']) && !empty($json['driver'])) || !isset($json['driver']);
        $allow_change_server_index = (isset($json['server_index']) && is_numeric($json['server_index'])) || !isset($json['server_index']);
        // Checking Course Type
        $servers = Driver::getConfigValueByDriver($json['driver'], 'servers');
        $allow_course_type = MeetingPlugin::checkCourseType($meetingCourse->course, $servers[$json['server_index']]['course_types']);
        //Checking Server Active
        $active_server = $servers[$json['server_index']]['active'];

        // Check group
        $allow_group = true;
        if (isset($json['group_id']) && !empty($json['group_id'])) {
            $group = \Statusgruppen::find($json['group_id']);
            if (!$group) {
                $allow_group = false;
            }
        }

        $message = [];
        if (!$meetingCourse->isNew() && $name && $allow_change_driver && $allow_change_server_index && $allow_course_type && $active_server && $allow_group) {
            $change_date = new \DateTime();
            if (isset($json['active'])) {
                $meetingCourse->active = $json['active'];
                $meetingCourse->store();
            }
            if (isset($json['group_id'])) {
                $meetingCourse->group_id = ((empty($json['group_id']) ? null : $json['group_id']));
                $meetingCourse->store();
            }

            // Handel course default room.
            $is_default = isset($json['is_default']) ? intval($json['is_default']) : 0;
            $this->manageCourseDefaultRoom($room_id, $json['cid'], $is_default);

            $meeting = $meetingCourse->meeting;
            $meeting->name = $name;
            !isset($json['recordingUrl']) ?: $meeting->recording_url = utf8_decode($json['recording_url']);
            !isset($json['join_as_moderator']) ?: $meeting->join_as_moderator = $json['join_as_moderator'];
            !isset($json['driver']) ?: $meeting->driver = $json['driver'];
            !isset($json['server_index']) ?: $meeting->server_index = $json['server_index'];

            // apply default features

            if (isset($json['features'])) {
                // Apply validation on features inputs
                try {
                    $validated_features = $this->validateFeatureInputs($json['features'], $meeting->driver);
                    if (!$validated_features) {
                        $message = [
                            'text' => I18N::_('Raumeinstellung kann nicht bearbeitet werden!'),
                            'type' => 'error'
                        ];
                        return $this->createResponse([
                            'message'=> $message,
                        ], $response);
                        die();
                    } else {
                        $json['features'] = $validated_features;
                    }
                } catch (Exception $e) {
                    throw new Error($e->getMessage(), 404);
                }
                
                if (!is_numeric($json['features']['duration'])) {
                    $json['features']['duration'] = "240";
                }

                //Handle recording stuff
                $has_recording_error = false;
                $recording_error_text = '';
                if (isset($json['features']['record']) && filter_var($json['features']['record'], FILTER_VALIDATE_BOOLEAN)) {  // Recording is asked...
                    $recording_capability = $this->checkRecordingCapability($json['driver'], $json['cid']);
                    if ($recording_capability['allow_recording'] == false
                        || ($recording_capability['allow_recording'] == true && $recording_capability['type'] == 'opencast'
                            && empty($recording_capability['seriesid']))) {
                        $has_recording_error = true;
                        $recording_error_text = I18N::_($recording_capability['message'] ? $recording_capability['message'] : 'Sitzungsaufzeichnung ist nicht möglich!');
                    } else {
                        if ($recording_capability['type'] == 'opencast') {
                            $json['features']['meta_opencast-dc-isPartOf'] = $recording_capability['seriesid'];
                        }
                    }
                }
                if ($has_recording_error) {
                    $message = [
                        'text' => $recording_error_text,
                        'type' => 'error'
                    ];
                    return $this->createResponse([
                        'message'=> $message,
                    ], $response);
                    die();
                }

                //validate maxParticipants if the server has default
                $servers = Driver::getConfigValueByDriver($json['driver'], 'servers');
                $server_maxParticipants = $servers[$json['server_index']]['maxParticipants'];
                if (is_numeric($server_maxParticipants) && $server_maxParticipants > 0 && $json['features']['maxParticipants'] > $server_maxParticipants) {
                    $message = [
                        'text' => sprintf(I18N::_('Teilnehmerzahl darf %d nicht überschreiten'), $server_maxParticipants),
                        'type' => 'error'
                    ];
                    return $this->createResponse([
                        'message'=> $message,
                    ], $response);
                }

                $meeting->features = json_encode($json['features']);
            }
            $meeting->folder_id = $json['folder_id'];
            $meeting->chdate = $change_date->getTimestamp();
            $meeting->store();
            $message = [
                'text' => I18N::_('Die Bearbeitung wurde erfolgreich abgeschlossen.'),
                'type' => 'success'
            ];
        } else {
            $message = 'Raumeinstellung kann nicht bearbeitet werden!';
            if (!$allow_group) {
                $message = 'Die ausgewählte Gruppe ist nicht mehr verfügbar.';
            } else if (!$allow_course_type) {
                $message = 'Der ausgewählte Server ist in diesem Veranstaltungstyp nicht verfügbar.';
            }
            $message = [
                'text' => I18N::_($message),
                'type' => 'error'
            ];
        }

        return $this->createResponse([
            'message'=> $message,
        ], $response);
    }
}
