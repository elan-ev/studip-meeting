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

class RoomEdit extends MeetingsController
{
    use MeetingsTrait;
    /**
     * Edits meeting room
     *
     * @param string $room_id room id
     * @param string $json['name'] meeting room name
     * @param string $json['recording_url'] recording url of the room
     * @param string $json['cid'] course id
     * @param boolean $json['join_as_moderator'] moderator permission
     * @param boolean $json['active'] moderator permission
     *
     * @return json message 
     *
     */
    public function __invoke(Request $request, Response $response, $args)
    {
        global $perm;
        $room_id = $args['room_id'];
        $json = $this->getRequestData($request);
        
        $meetingCourse = new MeetingCourse([$room_id, $json['cid']]);
        $name = utf8_decode($json['name']);
        $recordingUrl = utf8_decode($json['recording_url']);

        $message = [];
        if (!$meetingCourse->isNew() && $perm->have_studip_perm('tutor', $json['course_id']) && $name) {
            $meetingCourse->active = $json['active'];
            $meetingCourse->store();
            $meeting = new Meeting($room_id);
            $meeting->name = $name;
            $meeting->recording_url = $recordingUrl;
            $meeting->join_as_moderator = $json['join_as_moderator'];
            $meeting->store();
            $message = [
                'text' => _('Erledigt'),
                'type' => 'success'
            ];
        } else {
            $message = [
                'text' => _('Unable to edit meeting'),
                'type' => 'error'
            ];
        }

        return $this->createResponse([
            'message'=> $message,
        ], $response);
    }
}
