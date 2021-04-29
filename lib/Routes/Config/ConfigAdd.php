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
use MeetingPlugin;

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

                $result = Driver::setConfigByDriver($driver_name, $config_options);

                if ($result['valid_servers'] === false) {
                    $info = $driver_name;
                    $message = '';
                    if (count($result['invalid_indices'])) {
                        $info .= ' (' . implode(', ', $result['invalid_indices']) . ') ';
                        $message = sprintf(I18N::_('Die Überprüfung der Servereinstellungen '
                        . 'für %s war nicht erfolgreich, wurden aber trotzdem gespeichert.'), $info);
                    } else {
                        $message = sprintf(I18N::_('Der Treiber %s kann nicht verwendet werden, da er keinen Server hat.'), $info);
                    }
                    $res_message_text[] = $message;
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
