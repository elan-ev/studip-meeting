<?php

namespace Meetings\Routes\Config;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Meetings\Errors\AuthorizationFailedException;
use Meetings\Errors\Error;
use Meetings\MeetingsTrait;
use Meetings\MeetingsController;
use ElanEv\Model\Driver;
use ElanEv\Model\CourseConfig;

class ConfigList extends MeetingsController
{
    use MeetingsTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $drivers = Driver::discover(true);
        $config  = Driver::getConfig();

        $response_result = [];
        !$drivers ?: $response_result['drivers'] = $drivers;
        !$config  ?: $response_result['config']  = $config;

        if (!empty($response_result)) {
            return $this->createResponse($response_result, $response);
        }

        return $this->createResponse([], $response);
    }
}
