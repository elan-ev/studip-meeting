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
use ElanEv\Driver\DriverFactory;
use ElanEv\Model\Driver;

class RoomDelete extends MeetingsController
{
    use MeetingsTrait;
    /**
     * Deletes meeting room
     *
     * @param string $room_id room id
     * @param string $json['course_id'] course id     *
     *
     * @return json message 
     *
     * @throws \Exception \Error if something goes wrong with driver room deletion
     */
    public function __invoke(Request $request, Response $response, $args)
    {
        global $perm;
        $driver_factory = new DriverFactory(Driver::getConfig());

        $room_id = $args['room_id'];
        $json = $this->getRequestData($request);
        $message = [
            'text' => _('Unable to delete meeting'),
            'type' => 'error'
        ];

        $meetingCourse = new MeetingCourse([$room_id, $json['course_id']]);

        if (!$meetingCourse->isNew() && $perm->have_studip_perm('tutor', $meetingCourse->course->id)) {
            // don't associate the meeting and the course any more
            $meetingId = $meetingCourse->meeting->id;
            $meetingCourse->delete();

            $meeting = new Meeting($room_id);

            // if the meeting isn't associated with at least one course at all,
            // it can be removed entirely
            if (count($meeting->courses) === 0) {
                // inform the driver to delete the meeting as well
                $driver = $driver_factory->getDriver($meeting->driver, $meeting->server_index);
                try {
                    $driver->deleteMeeting($meeting->getMeetingParameters());
                } catch (Exception $e) {
                    throw new Error($e->getMessage(), 404);
                }

                $meeting->delete();
                $message = [
                    'text' => _('Meeting wurde gelöscht.'),
                    'type' => 'success'
                ];
            }
        }
        return $this->createResponse([
            'message'=> $message,
        ], $response);
    }
}
