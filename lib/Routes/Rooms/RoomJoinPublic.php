<?php

namespace Meetings\Routes\Rooms;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Meetings\MeetingsTrait;
use Meetings\MeetingsController;
use Meetings\Errors\Error;
use Exception;
use Meetings\Helpers\RoomManager;


class RoomJoinPublic extends MeetingsController
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
            $public_room_url = RoomManager::generateMeetingBaseURL("room/public/$room_id/$cid", ['cancel_login' => 1]);
            header('Location:' . $public_room_url);
            die;
        } catch (Exception $e) {
            throw new Error($e->getMessage(), ($e->getCode() ? $e->getCode() : 404));
        }
    }
}
