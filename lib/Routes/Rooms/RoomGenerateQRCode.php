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
use Meetings\RoomSlimController;

class RoomGenerateQRCode extends MeetingsController
{
    use MeetingsTrait;
    
    public function __invoke(Request $request, Response $response, $args)
    {
        $room_id = filter_var($args['room_id'], FILTER_SANITIZE_NUMBER_INT);
        $cid = filter_var($args['cid'], FILTER_SANITIZE_STRING);

        try {
            $qr_code_object = RoomSlimController::generateQRCode($room_id, $cid);
            if ($qr_code_object) {
                return $this->createResponse(['qr_code' => $qr_code_object], $response);
            }

            $message = [
                'type' => 'error',
                'text' => I18N::_('QR-Code kann nicht generiert werden')
            ];
            return $this->createResponse(['message' => $message], $response);
        } catch (Exception $ex) {
            throw new Error('Room Parameters not found', 404);
        }
    }
}
