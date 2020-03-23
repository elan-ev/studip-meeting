<?php

namespace Meetings\Routes\Config;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Meetings\Errors\AuthorizationFailedException;
use Meetings\Errors\Error;
use Meetings\MeetingsTrait;
use Meetings\MeetingsController;
use ElanEv\Model\Driver;

class ConfigList extends MeetingsController
{
    use MeetingsTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $config = Driver::getConfig();

        if (!empty($config)) {
            return $this->createResponse(['config' => $config], $response);
        }

        return $this->createResponse([], $response);
    }
}
