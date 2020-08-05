<?php

namespace Meetings\Routes\Feedback;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Meetings\Errors\AuthorizationFailedException;
use Meetings\MeetingsTrait;
use Meetings\MeetingsController;
use Meetings\Errors\Error;
use Exception;
use StudipMail;
use Meetings\Models\I18N as _;

use ElanEv\Model\MeetingCourse;
use ElanEv\Model\Meeting;

class FeedbackSubmit extends MeetingsController
{
    use MeetingsTrait;
    /**
     * Returns the parameters of a selected room
     *
     * @param string $room_id room id
     *
     *
     * @return json room parameter
     *
     * @throws \Error if no parameters can be found
     */
    public function __invoke(Request $request, Response $response, $args)
    {
        $json = $this->getRequestData($request);
        global $UNI_CONTACT, $user;
        $current_user = $user;
        try {
            $to = filter_var($UNI_CONTACT, FILTER_VALIDATE_EMAIL);
            $room_id = filter_var($json['room_id'], FILTER_SANITIZE_NUMBER_INT);
            $cid = filter_var($json['cid'], FILTER_SANITIZE_STRING);
            $meetingCourse = new MeetingCourse([$room_id, $cid ]);
            if ($json && $to && $meetingCourse && $current_user) {
                $subject = _("Feedback zum Meetings-Plugin");
                $mailbody = $this->generateMessageBody($json, $meetingCourse, $current_user);
                StudipMail::sendMessage($to, $subject, $mailbody);
                $message = [
                    'text' => _("Ihr Feedback wird gesendet."),
                    'type' => 'success'
                ];
            } else {
                $message = [
                    'text' => _('Einige Informationen fehlen!'),
                    'type' => 'error'
                ];
            }
    
            return $this->createResponse([
                'message'=> $message,
            ], $response);
        } catch (Exception $e) {
            throw new Error($e->getMessage(), 404);
        }
    }

    /**
    * Gathers infor and generates Message body text
    *
    * @param (type) (name) (desc)
    * @return (type) (name) (desc)
    */
    private function generateMessageBody ($data, $meetingCourse, $current_user) 
    {    
        $msg .= _("Grundinformation:") . "\n";
        $msg = sprintf(_("Seminar ID: %s"), $meetingCourse->course_id) . "\n";
        $msg .= sprintf(_("Meetings ID: %s"), $meetingCourse->meeting->id) . "\n";
        $msg .= sprintf(_("User ID: %s"), $current_user->id) . "\n";
        $msg .= "==============\n";
        $msg .= _("Details:") . "\n";
        $msg .= sprintf(_("Browser-Name: %s"), $data['browser_name']) . "\n";
        $msg .= sprintf(_("Browser-Version: %s"), $data['browser_version']) . "\n";
        $msg .= sprintf(_("Download-Geschw. (Mbps): %s"), $data['download_speed']) . "\n";
        $msg .= sprintf(_("Upload-Geschw. (Mbps): %s"), $data['upload_speed']) . "\n";
        $msg .= sprintf(_("Netzwerk-Typ: %s"), $data['network_type']) . "\n";
        $msg .= sprintf(_("Betriebssystem (OS): %s"), $data['os_name']) . "\n";
        $msg .= sprintf(_("Prozessortyp: %s"), $data['cpu_type']) . "\n";
        $msg .= sprintf(_("Alter des Rechners: %s"), $data['cpu_old']) . "\n";
        $msg .= sprintf(_("Anzahl der CPU-Kerne: %s"), $data['cpu_num']) . "\n";
        $msg .= sprintf(_("RAM (Hauptspeicher) GB: %s"), $data['ram']) . "\n";
        $msg .= "==============\n";
        $msg .= _("Beschreibung:") . "\n";
        $msg .= $data['description'];
        return $msg;
    }

}
