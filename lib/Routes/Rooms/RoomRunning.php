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
use Meetings\Helpers\RoomManager;

use ElanEv\Model\MeetingCourse;
use ElanEv\Model\Meeting;
use ElanEv\Driver\DriverFactory;
use ElanEv\Model\Driver;

class RoomRunning extends MeetingsController
{
    use MeetingsTrait;
    /**
     * Determines whether a room is running or not
     *
     * @param string $room_id room id
     * @param string $cid course id
     *
     *
     * @return bool room running status
     *
     * @throws \Error if error occurs
     */
    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $driver_factory = new DriverFactory(Driver::getConfig());

            $room_id = $args['room_id'];
            $cid = $args['cid'];

            $meetingCourse = new MeetingCourse([$room_id, $cid ]);

            if (!$meetingCourse->isNew()) {
                $driver = $driver_factory->getDriver($meetingCourse->meeting->driver, $meetingCourse->meeting->server_index);
                $status = $driver->isMeetingRunning($meetingCourse->meeting->getMeetingParameters()) === 'true' ? true : false;
                return $this->createResponse(['status' => $status], $response);
            }
        } catch (Exception $e) {
            throw new Error($error_message, ($e->getCode() ? $e->getCode() : 404));
        }
    }
}
