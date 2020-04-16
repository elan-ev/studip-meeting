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

        $config = Driver::getConfig();

        $course_config = [];
        $cid =  filter_var(str_replace('cid=','', rtrim($request->getUri()->getQuery(), '/')), FILTER_SANITIZE_STRING);
        if ($cid) {
            global $user;
            $course_config = CourseConfig::findByCourseId($cid)->toArray();
            $displayAddRoom = false;
            $displayEditRoom = false;
            $displayDeleteRoom = false;
            //TODO: permissions must be optimized!
            if (in_array($user->perms, ['admin','root', 'dozent', 'tutor'])) {
                $displayAddRoom = true;
                $displayEditRoom = true;
                $displayDeleteRoom = true;
            }
            $course_config['display'] = [
                'addRoom' => $displayAddRoom,
                'editRoom' => $displayEditRoom,
                'deleteRoom' => $displayDeleteRoom,
            ];
        }
        $response_result = [];
        !$drivers           ?: $response_result['drivers'] = $drivers;
        !$config            ?: $response_result['config'] = $config;
        !$course_config     ?: $response_result['course_config'] = $course_config;

        if (!empty($config)) {
            return $this->createResponse($response_result, $response);
        }

        return $this->createResponse([], $response);
    }
}
