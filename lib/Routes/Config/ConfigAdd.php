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
use Meetings\Models\I18N as _;

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
                $valid_servers = Driver::setConfigByDriver($driver_name, $config_options);
                if (!$valid_servers) {
                    $res_message_text[] = sprintf(_('(%s) hat ungültige Server'), $driver_name);
                }
            }
            
            $message = [
                'text' => ((!empty($res_message_text)) ? $res_message_text : _('Konfiguration gespeichert.')),
                'type' => ((!empty($res_message_text)) ? 'error' : 'success')
            ];
        } catch ( Exception $e) {
            $message = [
                'text' => _('Konnte Konfiguration nicht speichern!'),
                'type' => 'error'
            ];
        }

        return $this->createResponse([
            'config' => Driver::getConfig(),
            'message'=> $message,
        ], $response);
    }
}
