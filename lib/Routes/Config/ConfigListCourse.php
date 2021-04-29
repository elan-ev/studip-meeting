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
use Meetings\Models\I18N;
use MeetingPlugin;

class ConfigListCourse extends MeetingsController
{
    use MeetingsTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $perm;

        $drivers = Driver::discover(true);
        $config = Driver::getConfig();

        $course_config = [];
        $cid = $args['cid'];


        $course_config = CourseConfig::findByCourseId($cid)->toArray();
        $displayAddRoom = false;
        $displayEditRoom = false;
        $displayDeleteRoom = false;
        $displayDeleteRecording = false;

        if ($perm->have_studip_perm('tutor', $cid)) {
            $displayAddRoom = true;
            $displayEditRoom = true;
            $displayDeleteRoom = true;
            $displayDeleteRecording = true;
        }

        $course_config['display'] = [
            'addRoom'         => $displayAddRoom,
            'editRoom'        => $displayEditRoom,
            'deleteRoom'      => $displayDeleteRoom,
            'deleteRecording' => $displayDeleteRecording,
        ];

        $course_config['introduction'] = $course_config['introduction']
            ? formatReady($course_config['introduction'])
            : '';

        if (!empty($config)) {
            $config = $this->setDefaultServerProfiles($config, $cid);
            $config = $this->setServerCourseType($config, $cid);
            $config = $this->setServerDetails($config);
        }

        if ($config && is_array($config)) {
            foreach ($config as $service => $service_val) {
                if (isset($config[$service]['servers'])
                    && is_array($config[$service]['servers'])
                    && $config[$service]['servers']
                ) {
                    foreach ($config[$service]['servers'] as $servers => $servers_val) {
                        $config[$service]['servers'][$servers] = $servers_val['active'];
                    }
                }
            }
        }

        $course_groups = [];
        if ($cid) {
            $groups = \Statusgruppen::findBySeminar_id($cid);
            foreach ($groups as $one_group) {
                $course_groups[$one_group->id] = (string)$one_group->name;
            }
        }

        $response_result = [];
        !$config ?: $response_result['config'] = $config;
        !$course_config ?: $response_result['course_config'] = $course_config;
        !$course_groups ?: $response_result['course_groups'] = $course_groups;

        if (!empty($response_result)) {
            return $this->createResponse($response_result, $response);
        }

        return $this->createResponse([], $response);
    }


    /**
     * Generates server preset settings and features based on number of participants
     * it adds another array to config called server_defaults
     *
     * @param $config   plugin general config
     * @param $cid      course id
     *
     * @return $config  plugin general config
     */
    private function setDefaultServerProfiles($config, $cid)
    {
        $course = new \Course($cid);
        $members_count = count($course->members) + 5;
        foreach ($config as $driver_name => $settings) {
            $server_defaults = [];
            $server_presets = [];

            if (!empty($settings['servers'])) {
                foreach ($settings['servers'] as $server_index => $server_values) {

                    //Take care of max participants and maxAllowedParticipants
                    $server_defaults[$server_index]['totalMembers'] = max(20, $members_count + 5);
                    if (isset($server_values['maxParticipants']) && $server_values['maxParticipants'] > 0) {
                        $server_defaults[$server_index]['maxAllowedParticipants'] = $server_values['maxParticipants'];
                        if ($members_count >= $server_values['maxParticipants']) {
                            $server_defaults[$server_index]['totalMembers'] = $server_values['maxParticipants'];
                        }
                    }

                    //Take care of create features
                    // add presets into config as well
                    if (isset($server_values['roomsize-presets']) && count($server_values['roomsize-presets']) > 0) {
                        foreach ($server_values['roomsize-presets'] as $size => $values) {
                            $server_presets[$server_index][$size] = $values;
                            if ($members_count >= $values['minParticipants']) {
                                unset($values['minParticipants']);
                                foreach ($values as $feature_name => $feature_value) {
                                    $value = $feature_value;
                                    if (filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)) {
                                        $value = filter_var($feature_value, FILTER_VALIDATE_BOOLEAN);
                                    }
                                    $server_defaults[$server_index][$feature_name] = $value;
                                }
                            }
                        }
                    }
                }
                $config[$driver_name]['server_defaults'] = $server_defaults;
                $config[$driver_name]['server_presets'] = $server_presets;
            }
        }
        return $config;
    }

    /**
     * Define the server course type compatibilty with the current course
     *
     * @param $config   plugin general config
     * @param $cid      course id
     *
     * @return $config  plugin general config
    */
    private function setServerCourseType($config, $cid)
    {
        foreach ($config as $driver_name => $settings) {
            if (isset($settings['servers']) && count($settings['servers'])) {
                foreach ($settings['servers'] as $index => $server_values) {
                    $config[$driver_name]['server_course_type'][$index] = [
                        "valid" => MeetingPlugin::checkCourseType(\Course::find($cid), $server_values['course_types']),
                        "name" => MeetingPlugin::getCourseTypeName($server_values['course_types'])
                    ];
                }
            }
        }
        return $config;
    }

    /**
     * Define the server label and description to display
     *
     * @param $config   plugin general config
     *
     * @return $config  plugin general config
    */
    private function setServerDetails($config)
    {
        foreach ($config as $driver_name => $settings) {
            if (isset($settings['servers']) && count($settings['servers'])) {
                foreach ($settings['servers'] as $index => $server_values) {
                    $label = "Server " . ($index + 1);
                    $description = "";
                    if (isset($server_values['label']) && trim($server_values['label']) != '') {
                        $label = ltrim(rtrim($server_values['label']));
                    }
                    if (isset($server_values['description']) && trim($server_values['description']) != '') {
                        $description = ltrim(rtrim($server_values['description']));
                    }
                    $config[$driver_name]['server_details'][$index] = [
                        "label" => \htmlReady($label),
                        "description" => \htmlReady($description)
                    ];
                }
            }
        }
        return $config;
    }
}
