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

class RoomAdd extends MeetingsController
{
    use MeetingsTrait;
    /**
     * Create meeting room in a course with specific driver
     *
     * @param string $json['course_id'] course id
     * @param string $json['user_id'] user id
     * @param string $json['name'] meeting room name
     * @param string $json['driver_name'] name of driver
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
        $driver_factory = new DriverFactory(Driver::getConfig());

        $json = $this->getRequestData($request);

        $meeting = new Meeting();
        $meeting->courses[] = new \Course($json['course_id']);
        $meeting->user_id = $json['user_id'];
        $meeting->name = $json['name'];
        $meeting->driver = $json['driver_name'];
        $meeting->server_index = $json['server_index'];
        $meeting->attendee_password = Helper::createPassword();
        $meeting->moderator_password = Helper::createPassword();
        $meeting->join_as_moderator = $json['join_as_moderator'];
        $meeting->remote_id = md5(uniqid());
        $meeting->store();
        $meetingParameters = $meeting->getMeetingParameters();

        $driver = $driver_factory->getDriver($json['driver_name'], $json['server_index']);

        try {
            if (!$driver->createMeeting($meetingParameters)) {
                throw new Error(sprintf('unable to create meeting with driver %s', $json['driver_name']), 404);
            }
        } catch (Exception $e) {
            throw new Error($e->getMessage(), 404);
        }

        $meeting->remote_id = $meetingParameters->getRemoteId();
        $meeting->store();
        
        return $this->createResponse([
            'success'=> _('Room created!'),
        ], $response);
    }
}
