<?php

namespace Meetings\Routes\Config;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Meetings\Errors\AuthorizationFailedException;
use Meetings\Errors\Error;
use Meetings\MeetingsTrait;
use Meetings\MeetingsController;
use Meetings\Models\Config;

class ConfigShow extends MeetingsController
{
    use MeetingsTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $config = Config::find($args['id']);

        if ($config) {
            $config->config['id'] = $config->id;
            return $this->createResponse(['config' => json_decode($config->config)], $response);
        }

        throw new Error('Config not found', 404);
    }
}
