<?php

namespace Meetings\Routes\Rooms;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Meetings\Errors\AuthorizationFailedException;
use Meetings\MeetingsTrait;
use Meetings\MeetingsController;
use Meetings\Errors\Error;
use Meetings\Errors\DriverError;
use Exception;
use Meetings\Models\I18N as _;

use ElanEv\Model\MeetingCourse;
use ElanEv\Model\Meeting;

use ElanEv\Model\Helper;
use ElanEv\Driver\DriverFactory;
use ElanEv\Model\Driver;
use MeetingPlugin;

class RoomsList extends MeetingsController
{
    use MeetingsTrait;

    /**
     * Return the list of rooms in a course
     *
     * @param string $course_id course id
     *
     *
     * @return json list of rooms available in that course
     *
     * @throws \Error if no room can be found
     */

    public function __invoke(Request $request, Response $response, $args)
    {
        global $perm;
        $driver_factory = new DriverFactory(Driver::getConfig());

        $cid = $args['cid'];

        if ($perm->have_studip_perm('tutor', $cid)) {
            $meeting_course_list_raw = MeetingCourse::findByCourseId($cid);
        } else {
            $meeting_course_list_raw = MeetingCourse::findActiveByCourseId($cid);
        }

        $course_rooms_list = [];
        foreach ($meeting_course_list_raw as $meetingCourse) {
            try {

                if ($meetingCourse->group_id && !$this->checkPermission($meetingCourse->group_id, $cid)) {
                    continue;
                }

                $meetingEnabled = true;

                try {
                    $driver = $driver_factory->getDriver(
                        $meetingCourse->meeting->driver,
                        $meetingCourse->meeting->server_index
                    );
                } catch (DriverError $de) {
                    // disable this room if driver emits an error
                    $meetingEnabled = false;
                }

                $meeting = $meetingCourse->meeting->toArray();
                $meeting = array_merge($meetingCourse->toArray(), $meeting);
                $meeting['has_recordings'] = false;

                // Recording Capability
                if (is_subclass_of($driver, 'ElanEv\Driver\RecordingInterface')) {
                    if ($perm->have_studip_perm('tutor', $cid)
                        || (!$perm->have_studip_perm('tutor', $cid)
                            && filter_var($this->getFeatures($meeting['features'],
                                'giveAccessToRecordings'
                            ), FILTER_VALIDATE_BOOLEAN))
                    ) {
                        if ((count($driver->getRecordings($meetingCourse->meeting->getMeetingParameters())) > 0)
                            || ($this->getFeatures($meeting['features'], 'meta_opencast-dc-isPartOf') &&
                            $this->getFeatures($meeting['features'], 'meta_opencast-dc-isPartOf') == MeetingPlugin::checkOpenCast($meetingCourse->course_id)))
                        {
                            $meeting['has_recordings'] = true;
                        }
                    }
                }

                $creator = \User::find($meetingCourse->meeting->user_id);

                $meeting['details'] = [
                    'creator' => $create ? $creator->getFullname() : 'unbekannt',
                    'date'    => date('d.m.Y H:i', $meetingCourse->meeting->mkdate)
                ];

                $meeting['features'] = $this->getFeatures($meeting['features']);
                $meeting['enabled'] = $meetingEnabled;

                $course_rooms_list[] = $meeting;
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (!empty($errors)) {
            throw new Error(implode ("\n", $errors), 500);
        } else {
            return $this->createResponse($course_rooms_list, $response);
        }
    }

    private function getFeatures($str_features, $key = null)
    {
        $features = json_decode($str_features, true);

        if ($key) {
            return isset($features[$key]) ? $features[$key] : null;
        } else {
            return $features;
        }
    }

    /**
     * This method check the permission (global and if he is in the group) for a given user
     *
     * @param $group_id The Group-ID
     * @param $cid The Course-ID
     * @return bool True if user have permission, False otherwise
     */
    public function checkPermission($group_id, $cid)
    {
        global $perm, $user;
        $group = new \Statusgruppen($group_id);

        return $group->isMember($user->id)
            || ($user && is_object($perm)
                && $perm->have_studip_perm('tutor', $cid, $user->id)
            );
    }
}
