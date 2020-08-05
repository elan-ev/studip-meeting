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
        } else {
            $meeting_course_list_raw = MeetingCourse::findActiveByCourseId($cid);
        }

        $course_rooms_list = [];
        foreach ($meeting_course_list_raw as $meetingCourse) {
            try {
                $driver = $driver_factory->getDriver($meetingCourse->meeting->driver, $meetingCourse->meeting->server_index);
                $meeting = $meetingCourse->meeting->toArray();
                $meeting = array_merge($meetingCourse->toArray(), $meeting);
                $meeting['recordings_count'] = 0;
                // Recording Capability
                if (is_subclass_of($driver, 'ElanEv\Driver\RecordingInterface')) {
                    if (Driver::getConfigValueByDriver($meeting['driver'], 'record')) { //config double check
                        if ($this->getFeatures($meeting['features'], 'record')) { //room recorded
                            if (Driver::getConfigValueByDriver($meeting['driver'] , 'opencast')) { // config check for opencast
                                if ($this->getFeatures($meeting['features'], 'meta_opencast-dc-isPartOf') && 
                                    $this->getFeatures($meeting['features'], 'meta_opencast-dc-isPartOf') == MeetingPlugin::checkOpenCast($meetingCourse->course_id))
                                {
                                    $meeting['recordings_count'] = \PluginEngine::getURL('OpenCast', ['cid' => $cid], 'course', true);
                                } else {
                                    $meeting['recordings_count'] = false;
                                }
                            } else {
                                $meeting['recordings_count'] = count($driver->getRecordings($meetingCourse->meeting->getMeetingParameters()));
                            }
                        }
                    }
                }
                $meeting['details'] = ['creator' => \User::find($meetingCourse->meeting->user_id)->getFullname(), 'date' => date('d.m.Y H:i', $meetingCourse->meeting->mkdate)];
                $meeting['features'] = $this->getFeatures($meeting['features']);
                $course_rooms_list[] = $meeting;
            } catch (Exception $e) {
                // $error_message = "There are meetings that are not currently reachable!";
            }
        }
        return $this->createResponse($course_rooms_list, $response);
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
