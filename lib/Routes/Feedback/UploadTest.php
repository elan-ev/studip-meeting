<?php

namespace Meetings\Routes\Feedback;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Meetings\MeetingsTrait;
use Meetings\MeetingsController;


class UploadTest extends MeetingsController
{
    use MeetingsTrait;

    public function __invoke(Request $request, Response $response, $args)
    {

        return $this->createResponse([
            'message'=> ['type' => 'success', 'text' => 'test internet speed.'],
        ], $response);
    }
}
