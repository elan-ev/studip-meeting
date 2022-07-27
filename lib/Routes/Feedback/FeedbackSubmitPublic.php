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
use Meetings\Models\I18N;

use ElanEv\Model\MeetingCourse;
use ElanEv\Model\Meeting;
use ElanEv\Model\Driver;

class FeedbackSubmitPublic extends MeetingsController
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
        global $UNI_CONTACT;
        try {
            $feedback_contact_address = Driver::getGeneralConfigValue('feedback_contact_address');
            $feedback_contact_address = ($feedback_contact_address ?: $UNI_CONTACT);

            $to = filter_var($feedback_contact_address, FILTER_VALIDATE_EMAIL);
            $room_id = filter_var($json['room_id'], FILTER_SANITIZE_NUMBER_INT);
            $cid = htmlspecialchars($json['cid']);
            $meetingCourse = new MeetingCourse([$room_id, $cid ]);

            if (!$to) {
                $message = [
                    'text' => I18N::_('Es ist keine Kontaktadresse hinterlegt! '
                        . 'Bitte wenden Sie sich an eine/n Systemadministrator/in!'),
                    'type' => 'error'
                ];
            } else if ($json && $meetingCourse) {
                $subject = I18N::_("Feedback zum Meetings-Plugin");
                $mailbody = $this->generateMessageBody($json, $meetingCourse);
                $feedback = new StudipMail();

                $feedback->setSubject($subject)
                    ->addRecipient($to)
                    ->setBodyText($mailbody);

                $done_sending = $feedback->send();

                $message = [
                    'text' => I18N::_("Ihr Feedback wird gesendet."),
                    'type' => 'success'
                ];

                if (!$done_sending) {
                    $message['type'] = 'error';
                    $message['text'] = I18N::_("Feedback kann nicht gesendet werden.");
                }
            } else {
                $message = [
                    'text' => I18N::_('Einige Informationen fehlen!'),
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
    * Gathers info and generates Message body text.
    *
    * @param array $data browser data
    * @param object $meetingCourse meeting course object
    * @return string
    */
    private function generateMessageBody($data, $meetingCourse)
    {
        $course = new \Course($meetingCourse->course_id);

        $msg = "Grundinformationen:" . "\n";
        $msg .= sprintf("Seminar ID: %s", $meetingCourse->course_id) . "\n";
        $msg .= sprintf("Seminar: %s", $course->getFullname('number-name-semester')) . "\n";
        $msg .= sprintf("Meetings ID: %s", $meetingCourse->meeting->id) . "\n";
        $msg .= "User: Public User in Public Seminar\n";
        $msg .= "==============\n";
        $msg .= "Details:" . "\n";
        $msg .= sprintf("Browser-Name: %s", $data['browser_name']) . "\n";
        $msg .= sprintf("Browser-Version: %s", $data['browser_version']) . "\n";
        $msg .= sprintf("Download-Geschw. (Mbps): %s", $data['download_speed']) . "\n";
        $msg .= sprintf("Upload-Geschw. (Mbps): %s", $data['upload_speed']) . "\n";
        $msg .= sprintf("Netzwerk-Typ: %s", $data['network_type']) . "\n";
        $msg .= sprintf("Betriebssystem (OS): %s", $data['os_name']) . "\n";
        $msg .= sprintf("Prozessortyp: %s", $data['cpu_type']) . "\n";
        $msg .= sprintf("Alter des Rechners: %s", $data['cpu_old']) . "\n";
        $msg .= sprintf("Anzahl der CPU-Kerne: %s", $data['cpu_num']) . "\n";
        $msg .= sprintf("RAM (Hauptspeicher) GB: %s", $data['ram']) . "\n";
        $msg .= "==============\n";
        $msg .= "Beschreibung:" . "\n";
        $msg .= $data['description'];

        return $msg;
    }

}
