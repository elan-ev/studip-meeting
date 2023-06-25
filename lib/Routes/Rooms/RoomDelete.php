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
use Meetings\Helpers\RoomManager;

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
     * @param string $cid course id
     *
     * @return json message
     *
     * @throws \Exception \Error if something goes wrong with driver room deletion
     */
    public function __invoke(Request $request, Response $response, $args)
    {
        global $perm;
        $cid = $args['cid'];

        if (!$perm->have_studip_perm('tutor', $cid)) {
            throw new Error('Access Denied', 403);
        }
        try {
            $driver_factory = new DriverFactory(Driver::getConfig());
            $room_id = $args['room_id'];
            $message = [
                'text' => I18N::_('Dieser Raum konnte nicht gefunden werden'),
                'type' => 'error'
            ];

            $meetingCourse = new MeetingCourse([$room_id, $cid ]);

            if (!$meetingCourse->isNew()) {
                // don't associate the meeting and the course any more
                $meetingId = $meetingCourse->meeting->id;

                $meeting = new Meeting($room_id);

                // if the meeting isn't associated with at least one course at all,
                // it can be removed entirely
                if (count($meeting->courses) === 1) {
                    // inform the driver to delete the meeting as well
                    $driver = $driver_factory->getDriver($meeting->driver, $meeting->server_index);
                    try {
                        if ($driver->deleteMeeting($meeting)) {
                            $meetingCourse->delete();
                            $meeting->delete();

                            $message = [
                                'text' => I18N::_('Meeting wurde gelöscht.'),
                                'type' => 'success'
                            ];
                        } else {
                            $message = [
                                'text' => I18N::_('Der Raum konnte nicht auf dem Meetingerver gelöscht werden und bleibt deshalb hier gelistet!'),
                                'type' => 'success'
                            ];
                        }
                    } catch (Exception $e) {
                        throw new Error($e->getMessage(), ($e->getCode() ? $e->getCode() : 404));
                    }
                } else {
                    // if there are more references to this room, delete only this reference
                    $meetingCourse->delete();
                }
            }

            return $this->createResponse([
                'message'=> $message,
            ], $response);

        } catch (Exception $e) {
            throw new Error($e->getMessage(), ($e->getCode() ? $e->getCode() : 404));
        }
    }
}
