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
            foreach ($json['config'] as $driver_name => $config_options ) {
                Driver::setConfigByDriver($driver_name, $config_options);
            }
            $message = [
                'text' => _('Konfiguration gespeichert.'),
                'type' => 'success'
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
