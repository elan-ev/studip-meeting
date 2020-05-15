<?php

namespace Meetings\Routes\Config;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Meetings\Errors\AuthorizationFailedException;
use Meetings\Errors\Error;
use Meetings\MeetingsTrait;
use Meetings\MeetingsController;
use Meetings\Models\Config;

class ConfigDelete extends MeetingsController
{
    use MeetingsTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $config = Config::where('id', $args['id'])->first();
        if ($config == null)
        {
            throw new Error('config not found.', 404);
        }

        if (!$config->delete()) {
            throw new Error('Could not delete config.', 500);
        }

        return $response->withStatus(204);
    }
}
