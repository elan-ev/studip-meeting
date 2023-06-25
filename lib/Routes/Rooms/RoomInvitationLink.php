<?php

namespace Meetings\Routes\Rooms;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Meetings\Errors\AuthorizationFailedException;
use Meetings\MeetingsTrait;
use Meetings\MeetingsController;
use Meetings\Errors\Error;
use ElanEv\Model\InvitationsLink;
use Meetings\Helpers\RoomManager;


class RoomInvitationLink extends MeetingsController
{
    use MeetingsTrait;
    /**
     * Returns the parameters of a selected room
     *
     * @param string $room_id room id
     * @param string $cid course id
     *
     *
     * @return json redirect parameter
     *
     * @throws \Error if there is any problem
     */
    public function __invoke(Request $request, Response $response, $args)
    {
        $room_id = $args['room_id'];
        $cid = $args['cid'];

        if (!$GLOBALS['perm']->have_studip_perm('tutor', $cid)) {
            throw new Error('Access Denied', 403);
        }

        $invitations_link = InvitationsLink::findOneBySQL('meeting_id = ?', [$room_id]);

        return $this->createResponse(['hex' => $invitations_link->hex, 'default_name' => $invitations_link->default_name], $response);
    }
}
