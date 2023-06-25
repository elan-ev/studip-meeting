<?php

namespace Meetings\Routes\Rooms;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Meetings\Errors\AuthorizationFailedException;
use Meetings\MeetingsTrait;
use Meetings\MeetingsController;
use Meetings\Errors\Error;
use ElanEv\Model\ModeratorInvitationsLink;
use Meetings\Helpers\RoomManager;


class RoomModeratorInvitationLinkGet extends MeetingsController
{
    use MeetingsTrait;
    /**
     * Returns moderator invitation link
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

        $moderators_invitation_link = ModeratorInvitationsLink::findOneBySQL('meeting_id = ?', [$room_id]);

        return $this->createResponse(['hex' => $moderators_invitation_link->hex, 'password' => $moderators_invitation_link->password], $response);
    }
}
