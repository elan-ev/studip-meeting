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
            'addRoom' => $displayAddRoom,
            'editRoom' => $displayEditRoom,
            'deleteRoom' => $displayDeleteRoom,
            'deleteRecording' => $displayDeleteRecording,
        ];

        $course_config['introduction'] = formatReady($course_config['introduction']);

        // !$config ?: $config = $this->setDefaultRoomSizeProfile($config, $cid);
        if (!empty($config)) {
            $config = $this->setDefaultRoomSizeProfile($config, $cid);
            $config = $this->setOpencastTooltipText($config, $cid);
        }

        if ($config && is_array($config)) {
            foreach($config as $service => $service_val){
                if (isset($config[$service]['servers'])
                    && is_array($config[$service]['servers'])
                    && $config[$service]['servers']
                ) {
                    foreach($config[$service]['servers'] as $servers => $servers_val){
                        $config[$service]['servers'][$servers] = true;
                    }
                }
            }
        }

        $response_result = [];
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
        $members_count = count($course->members) + 10;
        foreach ($config as $driver_name => $settings) {
            if (isset($settings['features']['create'])) {
                $features = $settings['features']['create'];
                $maxParticipants = min(20, max(300, $members_counts));
                $muteOnStart_index = array_search('muteOnStart', array_column($features, 'name'));
                $webcamsOnlyForModerator_index = array_search('webcamsOnlyForModerator', array_column($features, 'name'));
                $lockSettingsDisableCam_index = array_search('lockSettingsDisableCam', array_column($features, 'name'));
                $lockSettingsDisableMic_index = array_search('lockSettingsDisableMic', array_column($features, 'name'));
                $lockSettingsDisableNote_index = array_search('lockSettingsDisableNote', array_column($features, 'name'));
                $maxParticipants_index = array_search('maxParticipants', array_column($features, 'name'));
                if ($maxParticipants_index !== FALSE) {
                    $config[$driver_name]['features']['create'][$maxParticipants_index]['value'] = $members_count;
                }
                if ($maxParticipants >= 50) { //small
                    if ($muteOnStart_index !== FALSE) {
                        $config[$driver_name]['features']['create'][$muteOnStart_index]['value'] = true;
                    }
                }
                if ($maxParticipants >= 150) { //medium
                    if ($webcamsOnlyForModerator_index !== FALSE) {
                        $config[$driver_name]['features']['create'][$webcamsOnlyForModerator_index]['value'] = true;
                    }
                }
                if ($maxParticipants >= 300) { //large
                    if ($lockSettingsDisableCam_index !== FALSE) {
                        $config[$driver_name]['features']['create'][$lockSettingsDisableCam_index]['value'] = true;
                    }
                    if ($lockSettingsDisableMic_index !== FALSE) {
                        $config[$driver_name]['features']['create'][$lockSettingsDisableMic_index]['value'] = true;
                    }
                    if ($lockSettingsDisableNote_index !== FALSE) {
                        $config[$driver_name]['features']['create'][$lockSettingsDisableNote_index]['value'] = true;
                    }
                }
            }
        }
        return $config;
    }

    /**
     * Check against record feature, if exists looks for opencast recording capability and changes the 
     *
     * @param $config   plugin general config
     * @param $cid      course id
     *
     * @return $config  plugin general config
    */
    private function setOpencastTooltipText($config, $cid)
    {
        foreach ($config as $driver_name => $settings) {
            if ((isset($settings['record']) && $settings['record'] == "1") 
                    && (isset($settings['opencast']) && $settings['opencast'] == "1") 
                    && !empty(MeetingPlugin::checkOpenCast($cid))
                    && (isset($settings['features']['record']))) {
                $record_index = array_search('record', array_column($settings['features']['record'], 'name'));
                if ($record_index !== FALSE) {
                    $tooltip_text = _('Opencast wird als Aufzeichnungsserver verwendet. Diese Funktion ist im Testbetrieb und es kann noch zu Fehlern kommen.');
                    $config[$driver_name]['features']['record'][$record_index]['info'] = $tooltip_text;
                }
            }
        }
        return $config;
    }
}
