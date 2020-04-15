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
        $driver_factory = new DriverFactory(Driver::getConfig());

        $cid = $args['cid'];

        $meeting_course_list_raw = MeetingCourse::findByCourseId($cid);

        $course_rooms_list = [];
        foreach ($meeting_course_list_raw as $meetingCourse) {
            try {
                $driver = $driver_factory->getDriver($meetingCourse->meeting->driver, $meetingCourse->meeting->server_index);
                $meeting = $meetingCourse->meeting->toArray();
                $meeting = array_merge($meetingCourse->toArray(), $meeting);
                $meeting['recordings_count'] = 0;
                if (is_subclass_of($driver, 'ElanEv\Driver\RecordingInterface')) {
                    $meeting['recordings_count'] = count($driver->getRecordings($meetingCourse->meeting->getMeetingParameters()));
                }
                $meeting['details'] = ['creator' => \User::find($meetingCourse->meeting->user_id)->getFullname(), 'date' => date('d.m.Y H:m', $meetingCourse->meeting->mkdate)];
                $course_rooms_list[] = $meeting;
            } catch (Exception $e) {
                $error_message = "There are meetings that are not currently reachable!";
            }
        }
        return $this->createResponse($course_rooms_list, $response);
    }
}
