<?php

namespace Meetings\Routes\Rooms;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Meetings\Errors\AuthorizationFailedException;
use Meetings\MeetingsTrait;
use Meetings\MeetingsController;
use Meetings\Errors\Error;
use Exception;
use Throwable;
use Meetings\Models\I18N;
use Meetings\Helpers\MeetingsHelper;

class RoomJoin extends MeetingsController
{
    use MeetingsTrait;
    /**
     * Processes the join request.
     *
     * @param string $room_id room id
     * @param string $cid course id
     *
     *
     * @throws \Error if there is any problem
     */
    public function __invoke(Request $request, Response $response, $args)
    {
        $room_id = filter_var($args['room_id'], FILTER_SANITIZE_NUMBER_INT);
        $cid = htmlspecialchars($args['cid']);
        try {
            MeetingsHelper::performJoin($room_id, $cid);
        } catch (Exception $e) {
            throw new Error($e->getMessage(), ($e->getCode() ? $e->getCode() : 404));
        }
    }
}
