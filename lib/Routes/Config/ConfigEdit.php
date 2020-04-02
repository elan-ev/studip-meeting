<?php

namespace Meetings\Routes\Config;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Meetings\Errors\AuthorizationFailedException;
use Meetings\MeetingsTrait;
use Meetings\MeetingsController;
use Meetings\Models\Config;

class ConfigEdit extends MeetingsController
{
    use MeetingsTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $json = $this->getRequestData($request);

        $config = Config::where('id', $args['id'])->first();

        foreach ($json['config'] as $attr => $val) {
            if (isset($config->$attr)) {
                $config->$attr = $val;
            }
        }

        $config->save();

        return $response->withStatus(204);
    }
}
