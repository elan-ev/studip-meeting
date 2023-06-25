<?php

namespace Meetings\Routes\Recordings;

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
use ElanEv\Driver\DriverFactory;
use ElanEv\Model\Driver;

class RecordingDelete extends MeetingsController
{
    use MeetingsTrait;
    /**
     * Delete specific recording of a room
     *
     * @param string $recordings_id recordings id
     * @param string $room_id room id
     * @param string $cid course id
     *
     *
     * @throws \Error if there is problem
     */
    public function __invoke(Request $request, Response $response, $args)
    {
        global $perm;
        $cid = $args['cid'];
        if (!$perm->have_studip_perm('tutor', $cid)) {
            throw new Error('Access Denied', 403);
        }
        try {
            $recordings_id = $args['recordings_id'];
            $room_id = $args['room_id'];
            $driver_factory = new DriverFactory(Driver::getConfig());
            $meetingCourse = new MeetingCourse([$room_id, $cid ]);
            if (!$meetingCourse->isNew()) {
                $driver = $driver_factory->getDriver($meetingCourse->meeting->driver, $meetingCourse->meeting->server_index);
                $delete_result = $driver->deleteRecordings($recordings_id);
                $message = [
                    'text' => I18N::_('Aufzeichnung wurde gelöscht.'),
                    'type' => 'success'
                ];
                if (!$delete_result) {
                    $message = [
                        'text' => I18N::_('Aufzeichnung kann nicht gelöscht werden'),
                        'type' => 'error'
                    ];
                }
                return $this->createResponse([
                    'message'=> $message,
                ], $response);
            } else {
                throw new Error('Room not found', 404);
            }

        } catch (Exception $e) {
            throw new Error($e->getMessage(), ($e->getCode() ? $e->getCode() : 404));
        }
    }
}
