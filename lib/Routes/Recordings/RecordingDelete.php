<?php

namespace Meetings\Routes\Recordings;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Meetings\Errors\AuthorizationFailedException;
use Meetings\MeetingsTrait;
use Meetings\MeetingsController;
use Meetings\Errors\Error;
use Exception;
use Meetings\Models\I18N as _;

use ElanEv\Model\MeetingCourse;
use ElanEv\Model\Meeting;
use ElanEv\Driver\DriverFactory;
use ElanEv\Model\Driver;

class RecordingDelete extends MeetingsController
{
    use MeetingsTrait;
    /**
     * Delete specific recording of a room
     *
     * @param string $recordings_id recordings id
     *
     *
     * @throws \Error if there is problem
     */
    public function __invoke(Request $request, Response $response, $args)
    {
        $recordings_id = $args['recordings_id'];
        try {
            //TODO: To implement delete recordings

        } catch (Exception $e) {
            throw new Error($e->getMessage(), 404);
        }
    }
}
