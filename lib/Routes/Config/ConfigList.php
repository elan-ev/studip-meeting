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
        $cid = $args['cid'];

        if ($cid) {
            global $perm;
            $course_config = CourseConfig::findByCourseId($cid)->toArray();
            $displayAddRoom = false;
            $displayEditRoom = false;
            $displayDeleteRoom = false;
            if ($perm->have_studip_perm('tutor', $cid)) {
                $displayAddRoom = true;
                $displayEditRoom = true;
                $displayDeleteRoom = true;
            }
            $course_config['display'] = [
                'addRoom' => $displayAddRoom,
                'editRoom' => $displayEditRoom,
                'deleteRoom' => $displayDeleteRoom,
            ];

            $course_config['introduction'] = formatReady($course_config['introduction']);

            !$config ?: $config = $this->setDefaultRoomSizeProfile($config, $cid);
        }

        $response_result = [];
        !$drivers           ?: $response_result['drivers'] = $drivers;
        !$config            ?: $response_result['config'] = $config;
        !$course_config     ?: $response_result['course_config'] = $course_config;

        if (!empty($response_result)) {
            return $this->createResponse($response_result, $response);
        }

        return $this->createResponse([], $response);
    }


    /**
     * Automatically sets room size profile based on number of course members!
     * It decides the best room size profile and sets the maxParticipants and make that profile seleted
     *
     * @param $config   plugin general config
     * @param $cid      course id
     *
     * @return $config  plugin general config
     */
    private function setDefaultRoomSizeProfile ($config, $cid)
    {
        $course = new \Course($cid);
        $members_count = count($course->members) + 5;
        foreach ($config as $driver_name => $settings) {
            if (isset($settings['features']['create'])) {
                $roomSizeProfiles[] = [
                    'maxParticipants' => 0,
                    'roomSizeProfiles' => 'start'
                ];
                $index = array_search('roomSizeProfiles', array_column($settings['features']['create'], 'roomSizeProfiles'));
                $roomSizeProfiles_raw = $settings['features']['create'][$index]['value'];
                foreach ($roomSizeProfiles_raw as $configOption) {
                    $values = array_column($configOption['value'], 'value', 'name');
                    $values['roomSizeProfiles'] = $configOption['name'];
                    $roomSizeProfiles[$configOption['name']] = $values;
                }
                $maxParticipants = array_column($roomSizeProfiles, 'maxParticipants');
                $profile = 'no-limit';
                for ($i = 0; $i < count($maxParticipants); $i++) {
                    if ($maxParticipants[$i + 1]  >= $members_count && $members_count > $maxParticipants[$i] ) {
                        $profile = array_search($maxParticipants[$i + 1], array_column($roomSizeProfiles, 'maxParticipants', 'roomSizeProfiles'));
                    }
                }
                $profileIndex = array_search($profile, array_column($roomSizeProfiles_raw, 'name'));
                $roomSizeProfiles_raw[$profileIndex]['selected'] = true;
                $profileValue = [];
                $profileValue = $roomSizeProfiles_raw[$profileIndex]['value'];
                $valueIndex = array_search('maxParticipants', array_column($profileValue, 'name'));
                $roomSizeProfiles_raw[$profileIndex]['value'][$valueIndex]['value'] = $members_count;
                $config[$driver_name]['features']['create'][$index]['value'] = $roomSizeProfiles_raw;
            }
        }
        return $config;
    }
}
