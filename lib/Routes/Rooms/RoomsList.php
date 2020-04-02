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
        $course_id = $args['course_id'];

        $course_rooms_list_raw = MeetingCourse::findByCourseId($course_id);

        $course_rooms_list = [];
        foreach ($course_rooms_list_raw as $room) {
            $course_rooms_list[] = $room->toArray();
        }

        if ($course_rooms_list) {
            return $this->createResponse(['list' => $course_rooms_list], $response);
        }

        throw new Error('Rooms not found', 404);
    }
}
