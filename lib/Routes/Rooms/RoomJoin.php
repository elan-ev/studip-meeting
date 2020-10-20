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
use ElanEv\Driver\JoinParameters;
use ElanEv\Model\Join;
use ElanEv\Model\Helper;
use ElanEv\Driver\DriverFactory;
use ElanEv\Model\Driver;

class RoomJoin extends MeetingsController
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
        global $perm, $user;
        $driver_factory = new DriverFactory(Driver::getConfig());
        $room_id = $args['room_id'];
        $cid = $args['cid'];

        if (!$perm->have_studip_perm('user', $cid)) {
            throw new \AccessDeniedException();
        }

        $meeting = Meeting::find($room_id);
        if (!($meeting && $meeting->courses->find($cid))) {
            throw new Error(_('Dieser Raum in diesem Kurs kann nicht gefunden werden!'), 404);
        }

        //putting mandatory logoutURL into features
        if ($features = json_decode($meeting->features, true)) {
            if (!isset($features['logoutURL'])) {
                $hostUrl = $request->getUri()->getScheme() . '://' . $request->getUri()->getHost()
                    .($request->getUri()->getPort() ? ':' . $request->getUri()->getPort() : '');
                $features['logoutURL'] =  $hostUrl . \PluginEngine::getLink('meetingplugin', array('cid' => $cid), 'index');
                $meeting->features = json_encode($features);
                $meeting->store();
            }
        }

        $driver = $driver_factory->getDriver($meeting->driver, $meeting->server_index);

        $joinParameters = new JoinParameters();
        $joinParameters->setMeetingId($room_id);
        $joinParameters->setIdentifier($meeting->identifier);
        $joinParameters->setRemoteId($meeting->remote_id);
        $joinParameters->setUsername(\get_username($user->id));
        $joinParameters->setEmail($user->Email);
        $joinParameters->setFirstName($user->Vorname);
        $joinParameters->setLastName($user->Nachname);



        if ($perm->have_studip_perm('tutor', $cid) || $meeting->join_as_moderator) {
            $joinParameters->setPassword($meeting->moderator_password);
            $joinParameters->setHasModerationPermissions(true);
        } else {
            $joinParameters->setPassword($meeting->attendee_password);
            $joinParameters->setHasModerationPermissions(false);
        }

        $lastJoin = new Join();
        $lastJoin->meeting_id = $room_id;
        $lastJoin->user_id = $user->id;
        $lastJoin->last_join = time();
        $lastJoin->store();

        $error_message = '';
        try {
            if ($join_url = $driver->getJoinMeetingUrl($joinParameters)) {
                // directly redirect to room
                header('Location: ' . $join_url);
                exit;
            } else {
                $error_message = _('Konnte dem Meeting nicht beitreten, Kommunikation mit dem Meeting-Server fehlgeschlagen.');
            }
        } catch (Exception $e) {
            $error_message = _('Konnte dem Meeting nicht beitreten, Kommunikation mit dem Meeting-Server fehlgeschlagen. ('. $e->getMessage() .')');
        }

        throw new Error($error_message, 404);
    }
}
