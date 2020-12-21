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

use ElanEv\Model\MeetingCourse;
use ElanEv\Model\Meeting;

class RoomShow extends MeetingsController
{
    use MeetingsTrait;
    /**
     * Returns the parameters of a selected room
     *
     * @param string $room_id room id
     *
     *
     * @return json room parameter
     *
     * @throws \Error if no parameters can be found
     */
    public function __invoke(Request $request, Response $response, $args)
    {
        $room_id = $args['room_id'];

        $room_raw = Meeting::find($room_id);

        $parameters = $room_raw->getMeetingParameters()->toArray();

        if ($parameters) {
            return $this->createResponse(['parameters' => $parameters], $response);
        }

        throw new Error('Room Parameters not found', 404);
    }
}
