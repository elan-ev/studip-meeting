<?php

namespace Meetings\Routes\Config;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Meetings\Errors\AuthorizationFailedException;
use Meetings\MeetingsTrait;
use Meetings\MeetingsController;
use ElanEv\Model\Driver;
use Meetings\Errors\Error;
use Exception;
use Meetings\Models\I18N;
use ElanEv\Model\MeetingCourse;

class ConfigAdd extends MeetingsController
{
    use MeetingsTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $json = $this->getRequestData($request);
        $message = [];
        try {
            $res_message_text = [];
            foreach ($json['config'] as $driver_name => $config_options ) {
                //Make every record features to false when the record config is disabled
                if (isset($config_options['record']) && !filter_var($config_options['record'], FILTER_VALIDATE_BOOLEAN)) {
                    $courseMeetings = MeetingCourse::findAll();
                    foreach ($courseMeetings as $courseMeeting) {
                        $features = json_decode($courseMeeting->meeting->features, true);
                        if (isset($features['record']) && filter_var($features['record'], FILTER_VALIDATE_BOOLEAN)) {
                            $features['record'] = false;
                            $courseMeeting->meeting->features = json_encode($features);
                            $courseMeeting->meeting->store();
                        }
                    }
                }
                $valid_servers = Driver::setConfigByDriver($driver_name, $config_options);

                if (!$valid_servers) {
                    $res_message_text[] = sprintf(I18N::_('Die Überprüfung der Servereinstellungen '
                        . 'für %s war nicht erfolgreich, wurden aber trotzdem gespeichert.'), $driver_name);
                }
            }

            if (isset($json['general_config']) && !empty($json['general_config'])) {
                if (isset($json['general_config']['feedback_contact_address']) && !empty($json['general_config']['feedback_contact_address'])
                    && !filter_var($json['general_config']['feedback_contact_address'], FILTER_VALIDATE_EMAIL)) {
                    $res_message_text[] = I18N::_('Die Adresse des Feedback-Supports muss eine gültige E-Mail-Adresse sein');
                    $json['general_config']['feedback_contact_address'] = '';
                }
                Driver::setGeneralConfig($json['general_config']);
            }

            $message = [
                'text' => ((!empty($res_message_text)) ? $res_message_text : I18N::_('Konfiguration gespeichert.')),
                'type' => ((!empty($res_message_text)) ? 'error' : 'success')
            ];
        } catch ( Exception $e) {
            $message = [
                'text' => I18N::_('Konnte Konfiguration nicht speichern!'),
                'type' => 'error'
            ];
        }

        return $this->createResponse([
            'config' => Driver::getConfig(),
            'message'=> $message,
        ], $response);
    }
}
