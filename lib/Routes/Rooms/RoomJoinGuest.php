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

use ElanEv\Model\MeetingCourse;
use ElanEv\Model\Meeting;
use ElanEv\Model\InvitationsLink;
use ElanEv\Driver\JoinParameters;
use ElanEv\Model\Join;
use ElanEv\Model\Helper;
use ElanEv\Driver\DriverFactory;
use ElanEv\Model\Driver;
use MeetingPlugin;

class RoomJoinGuest extends MeetingsController
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
        $guest_name = $args['guest_name'];
        $cid = $args['cid'];

        if (!$GLOBALS['perm']->have_studip_perm('tutor', $cid)) {
            throw new Error(_('Access Denied'), 403);
        }

        $meeting = Meeting::find($room_id);
        if (!($meeting && $meeting->courses->find($cid))) {
            throw new Error(I18N::_('Dieser Raum in diesem Kurs kann nicht gefunden werden!'), 404);
        }

        // Checking Course Type
        $servers = Driver::getConfigValueByDriver($meeting->driver, 'servers');
        $allow_course_type = MeetingPlugin::checkCourseType($meeting->courses->find($cid), $servers[$meeting->server_index]['course_types']);
        //Checking Server Active
        $active_server = $servers[$meeting->server_index]['active'];

        $meetingFeatures = json_decode($meeting->features, true);
        if (!$meetingFeatures || !array_key_exists('guestPolicy-ALWAYS_ACCEPT', $meetingFeatures) || $meetingFeatures['guestPolicy-ALWAYS_ACCEPT'] == 'false'
            || !$active_server || !$allow_course_type) {
            throw new Error(I18N::_('Gäste können nicht eingeladen werden!'), 404);
        }

        $default_data = ['meeting_id' => $room_id, 'default_name' => $guest_name];
        $invitations_link = InvitationsLink::findOneBySQL('meeting_id = ?', [$room_id]);
        if(!$invitations_link) {
            $invitations_link = InvitationsLink::create($default_data + ['hex' => md5(uniqid())]);
        } else {
            $invitations_link->setData($default_data);
            $invitations_link->store();
        }
        $old_url_helper_url = \URLHelper::setBaseURL($GLOBALS['ABSOLUTE_URI_STUDIP']);
        $join_url =
            \URLHelper::getURL(
                'plugins.php/meetingplugin/room/index/' . $invitations_link->hex . '/' . $cid,
                ['cancel_login' => 1]
            );
        \URLHelper::setBaseURL($old_url_helper_url);
        return $this->createResponse(['join_url' => $join_url], $response);
    }
}
