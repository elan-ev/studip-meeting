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
use ElanEv\Driver\DriverFactory;
use ElanEv\Model\Driver;

class RoomInfo extends MeetingsController
{
    use MeetingsTrait;
    /**
     * Determines whether a room is running or not
     *
     * @param string $room_id room id
     * @param string $cid course id
     *
     *
     * @return bool room running status
     *
     * @throws \Error if error occurs
     */
    public function __invoke(Request $request, Response $response, $args)
    {
        global $perm;

        $driver_factory = new DriverFactory(Driver::getConfig());
        $cache = \StudipCacheFactory::getCache();

        $cid = $args['cid'];

        if ($perm->have_studip_perm('tutor', $cid)) {
            $meeting_course_list_raw = MeetingCourse::findByCourseId($cid);
        } else {
            $meeting_course_list_raw = MeetingCourse::findActiveByCourseId($cid);
        }

        $room_infos = [];

        foreach ($meeting_course_list_raw as $meetingCourse) {
            if (!$meetingCourse->isNew()) {
                try {
                    if (!$data = $cache->read('meetings/' . $meetingCourse->meeting->id)) {
                        $driver = $driver_factory->getDriver($meetingCourse->meeting->driver, $meetingCourse->meeting->server_index);
                        $info = $driver->getMeetingInfo($meetingCourse->meeting->getMeetingParameters());

                        if ($info) {
                            $info->chdate = $meetingCourse->meeting->chdate;
                            $cache->write('meetings/' . $meetingCourse->meeting->id, $info->asXML(), 300);   // cache expires after 5 minutes
                        }
                    } else {
                        $info = simplexml_load_string($data);
                    }

                    $room_infos[$meetingCourse->meeting->id] = $info;
                } catch (Exception $e) {
                }
            }
        }

        return $this->createResponse(['rooms_info' => $room_infos], $response);

    }
}
