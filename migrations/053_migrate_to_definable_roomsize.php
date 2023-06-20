<?php
/**
 * Migrate to definable server roomsizes
 *
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */

use ElanEv\Model\MeetingCourse;
use ElanEv\Driver\BigBlueButton;

class MigrateToDefinableRoomsize extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return 'Migrate to Definable Server Roomsizes';
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        if (Config::get()->VC_CONFIG) {
            $current_config = Config::get()->getValue('VC_CONFIG');
            $current_config = json_decode($current_config, true);
            if ($current_config && isset($current_config['BigBlueButton']) && isset($current_config['BigBlueButton']['servers'])) {
                $servers = $current_config['BigBlueButton']['servers'];
                foreach ($servers as $server_index => $server) {
                    if (isset($server['roomsize-presets'])) {
                        $current_presets = $server['roomsize-presets'];
                        $new_presets = [];
                        foreach ($current_presets as $preset_id => $preset) {
                            $new_preset = $preset;
                            $preset_name = 'Kleiner Raum';
                            if ($preset_id == 'medium') {
                                $preset_name = 'Mittlerer Raum';
                            } else if ($preset_id == 'large') {
                                $preset_name = 'Großer Raum';
                            }
                            $new_preset['presetName'] = $preset_name;
                            $new_preset['roomsizeSensitive'] = true;
                            $new_presets[] = $new_preset;
                        }
                        $current_config['BigBlueButton']['servers'][$server_index]['roomsize-presets'] = $new_presets;
                    }
                }

                $encoded_json = json_encode($current_config);
                if ($encoded_json) {
                    Config::get()->store('VC_CONFIG', json_encode($current_config));
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        if (Config::get()->VC_CONFIG) {
            $current_config = Config::get()->getValue('VC_CONFIG');
            $current_config = json_decode($current_config, true);
            if ($current_config && isset($current_config['BigBlueButton']) && isset($current_config['BigBlueButton']['servers'])) {
                $servers = $current_config['BigBlueButton']['servers'];
                $old_presets = [];
                $default_preset = json_decode('{"minParticipants":"0","lockSettingsDisableNote":false,"lockSettingsDisableMic":false,"lockSettingsDisableCam":false,"webcamsOnlyForModerator":false,"muteOnStart":false}', true);
                $small_preset = $default_preset;
                $old_presets['small'] = $small_preset;
                $medium_preset = $default_preset;
                $medium_preset['minParticipants'] = '50';
                $old_presets['medium'] = $medium_preset;
                $large_preset = $default_preset;
                $large_preset['minParticipants'] = '150';
                $old_presets['large'] = $large_preset;
                foreach ($servers as $server_index => $server) {
                    if (isset($server['roomsize-presets'])) {
                        $current_config['BigBlueButton']['servers'][$server_index]['roomsize-presets'] = $old_presets;
                    }
                }

                $encoded_json = json_encode($current_config);
                if ($encoded_json) {
                    Config::get()->store('VC_CONFIG', json_encode($current_config));
                }
            }
        }
    }
}
