<?php

namespace Meetings\Routes\Rooms;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Meetings\Errors\AuthorizationFailedException;
use Meetings\MeetingsTrait;
use Meetings\MeetingsController;
use Meetings\Errors\Error;
use Meetings\Errors\DriverError;
use Exception;
use Meetings\Models\I18N;

use ElanEv\Model\MeetingCourse;
use ElanEv\Model\Meeting;

use ElanEv\Model\Helper;
use ElanEv\Driver\DriverFactory;
use ElanEv\Model\Driver;
use MeetingPlugin;

class RoomsList extends MeetingsController
{
    use MeetingsTrait;

    /**
     * Return the list of rooms in a course
     *
     * @param string $course_id course id
     *
     *
     * @return json list of rooms available in that course
     *
     * @throws \Error if no room can be found
     */

    public function __invoke(Request $request, Response $response, $args)
    {
        global $perm;
        $driver_factory = new DriverFactory(Driver::getConfig());

        $cid = $args['cid'];

        if ($perm->have_studip_perm('tutor', $cid)) {
            $meeting_course_list_raw = MeetingCourse::findByCourseId($cid);

            // Default Room:
            // In case there is only one room and it is not default, we forcefully select the room as default.
            if (count($meeting_course_list_raw) == 1 && $meeting_course_list_raw[0]->is_default == 0) {
                $this->autoSelectCourseDefaultRoom($meeting_course_list_raw[0]);
            }
        } else {
            $meeting_course_list_raw = MeetingCourse::findActiveByCourseId($cid);
        }

        $course_rooms_list = [];
        foreach ($meeting_course_list_raw as $meetingCourse) {
            try {

                // Check Assigned Group
                $meetingCourse = $this->checkAssignedGroup($meetingCourse);

                // Check group access permission
                if ($meetingCourse->group_id && !$this->checkGroupPermission($meetingCourse->group_id, $cid)) {
                    continue;
                }

                $meetingEnabled = true;

                try {
                    $driver = $driver_factory->getDriver(
                        $meetingCourse->meeting->driver,
                        $meetingCourse->meeting->server_index
                    );
                } catch (DriverError $de) {
                    // disable this room if driver emits an error
                    $meetingEnabled = false;
                }

                // Checking Course Type
                if (!MeetingPlugin::checkCourseType($meetingCourse->course, $driver->course_type)) {
                    if (!$perm->have_studip_perm('tutor', $cid)) {
                        continue;
                    } else {
                        $meetingEnabled = false;
                    }
                }

                // Checking folder existence
                $this->checkAssignedFolder($meetingCourse->meeting);
                if (!filter_var(Driver::getConfigValueByDriver($meetingCourse->meeting->driver, 'preupload'), FILTER_VALIDATE_BOOLEAN)) {
                    $meeting['preupload_not_allowed'] = _('Das automatische Hochladen von Folien ist derzeit nicht möglich');
                }
                $meeting = $meetingCourse->meeting->toArray();
                $meeting = array_merge($meetingCourse->toArray(), $meeting);
                
                $meeting['has_recordings'] = false;

                // Check Recordings
                if (is_subclass_of($driver, 'ElanEv\Driver\RecordingInterface')) {
                    if ($perm->have_studip_perm('tutor', $cid)
                        || (!$perm->have_studip_perm('tutor', $cid)
                            && filter_var($this->getFeatures($meeting['features'],
                                'giveAccessToRecordings'
                            ), FILTER_VALIDATE_BOOLEAN))
                    ) {
                        $recordings = $driver->getRecordings($meetingCourse->meeting->getMeetingParameters());
                        if (!empty($recordings)
                            || ($this->getFeatures($meeting['features'], 'meta_opencast-dc-isPartOf') && !empty(MeetingPlugin::checkOpenCast($meetingCourse->course_id)) &&
                            $this->getFeatures($meeting['features'], 'meta_opencast-dc-isPartOf') == MeetingPlugin::checkOpenCast($meetingCourse->course_id)))
                        {
                            $meeting['has_recordings'] = true;
                        }
                    }
                }

                $meeting['features'] = $this->getFeatures($meeting['features']);

                // Check Recording Capability
                if (isset($meeting['features']['record']) && filter_var($meeting['features']['record'], FILTER_VALIDATE_BOOLEAN)) {
                    $recording_capability = $this->checkRecordingCapability($meetingCourse->meeting->driver, $cid);
                    $record_not_allowed = '';
                    if ($recording_capability['allow_recording'] == false
                        || ($recording_capability['allow_recording'] == true && $recording_capability['type'] == 'opencast'
                            && empty($recording_capability['seriesid']))) {
                        if (isset($meeting['features']['meta_opencast-dc-isPartOf'])) {
                            unset($meeting['features']['meta_opencast-dc-isPartOf']);
                        }
                        $record_not_allowed = _($recording_capability['message'] ? $recording_capability['message'] : 'Sitzungsaufzeichnung ist nicht erlaubt.');
                    } else {
                        if ($recording_capability['type'] == 'opencast') {
                            $meeting['features']['meta_opencast-dc-isPartOf'] = $recording_capability['seriesid'];
                        }
                    }
                    $meetingCourse->meeting->features = json_encode($meeting['features']);
                    $meetingCourse->meeting->store();
                    if ($record_not_allowed) {
                        $meeting['record_not_allowed'] = $record_not_allowed;
                    }
                }

                $creator = \User::find($meetingCourse->meeting->user_id);
                $meeting['name']= ltrim($meetingCourse->meeting->name);

                $meeting['details'] = [
                    'creator' => $creator ? $creator->getFullname() : 'unbekannt',
                    'date'    => date('d.m.Y H:i', $meetingCourse->meeting->mkdate)
                ];

                $meeting['enabled'] = $meetingEnabled;

                if ($meeting['folder_id']) {
                    $meeting['details']['folder'] = [
                        'name' => $meetingCourse->meeting->folder->name,
                        'link' => \URLHelper::getURL('dispatch.php/course/files/index/' . $meeting['folder_id'], [
                            'cid' => $cid
                        ])
                    ];
                }

                $course_rooms_list[] = $meeting;
            } catch (Exception $e) {
                $errorCode = 500;
                if ($e->getCode()) {
                    $errorCode = $e->getCode();
                }
                if (!in_array($e->getMessage(), $errors[$errorCode])) {
                    $errors[$errorCode][] = $e->getMessage();
                }
            }
        }

        if (!empty($errors)) {
            foreach ($errors as $error_code => $error_messages) {
                throw new Error(implode ("\n", $error_messages), $error_code);
            }
        } else {
            // Sort the list based on default. We want to push the default room to the top.
            $defaults = array_column($course_rooms_list, 'is_default');
            array_multisort($defaults, SORT_DESC, $course_rooms_list);
            
            return $this->createResponse($course_rooms_list, $response);
        }
    }

    private function getFeatures($str_features, $key = null)
    {
        $features = json_decode($str_features, true);

        if ($key) {
            return isset($features[$key]) ? $features[$key] : null;
        } else {
            return $features;
        }
    }
}
