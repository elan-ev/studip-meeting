<?php

namespace Meetings\Routes\Rooms;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Meetings\MeetingsTrait;
use Meetings\MeetingsController;
use Meetings\Errors\Error;
use Exception;
use Meetings\Helpers\RoomManager;

class RoomGenerateQRCodePublic extends MeetingsController
{
    use MeetingsTrait;
    
    public function __invoke(Request $request, Response $response, $args)
    {
        $room_id = filter_var($args['room_id'], FILTER_SANITIZE_NUMBER_INT);
        $cid = filter_var($args['cid'], FILTER_SANITIZE_STRING);

        try {
            $join_url = RoomManager::generateMeetingBaseURL("room/public/$room_id/$cid", ['cancel_login' => 1]);
            $qr_code = [
                'url' => $join_url,
                'token' => random_int(10000, 99999)
            ];
            return $this->createResponse(['qr_code' => $qr_code], $response);
        } catch (Exception $ex) {
            throw new Error('Room Parameters not found', 404);
        }
    }
}
