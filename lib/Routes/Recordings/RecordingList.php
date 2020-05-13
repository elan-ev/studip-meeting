<?php

namespace Meetings\Routes\Recordings;

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
use ElanEv\Driver\DriverFactory;
use ElanEv\Model\Driver;

class RecordingList extends MeetingsController
{
    use MeetingsTrait;
    /**
     * Returns the recordings_list of a selected room
     *
     * @param string $room_id room id
     * @param string $cid course id
     *
     *
     * @return json recording list
     *
     * @throws \Error in case of failure!
     */
    public function __invoke(Request $request, Response $response, $args)
    {
        $room_id = $args['room_id'];
        $cid = $args['cid'];
        $driver_factory = new DriverFactory(Driver::getConfig());

        $meetingCourse = new MeetingCourse([$room_id, $cid ]);
        if (!$meetingCourse->isNew()) {
            $recordings_list = [];
            try {
                $driver = $driver_factory->getDriver($meetingCourse->meeting->driver, $meetingCourse->meeting->server_index);
                if (is_subclass_of($driver, 'ElanEv\Driver\RecordingInterface')) {
                    $recordings = $driver->getRecordings($meetingCourse->meeting->getMeetingParameters());
                    if (!empty($recordings)) {
                        foreach ($recordings as $recording) {
                            //Converting datetimes here in php, becasue Vuejs date filter does not act normally !!!
                            $recording->startTime =  date('d.m.Y, H:i:s', (int)$recording->startTime / 1000);
                            $recording->endTime =  date('d.m.Y, H:i:s', (int)$recording->endTime / 1000);
                            $recording->room_id = $room_id;
                            $recordings_list[] = $recording;
                        }
                    }
                }
            } catch (Exception $e) {
                throw new Error('Fehler in der Aufzeichnungliste (' . $e->getMessage() . ')', 404);
            }
        }

        return $this->createResponse($recordings_list, $response);

    }
}
