<?php
require __DIR__ . '/../vendor/autoload.php';

/**
 * Adjusts room size with the number of course members
 *
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */

use ElanEv\Model\MeetingCourse;
use ElanEv\Driver\BigBlueButton;

class AdjustExistingRoomSize extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return 'Adjusts room size with the number of course members';
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        $roomSizeProfiles = json_decode('{"0":{"maxParticipants":0,"roomSizeProfiles":"start"},"small":{"maxParticipants":50,"muteOnStart":true,"webcamsOnlyForModerator":false,"lockSettingsDisableCam":false,"lockSettingsDisableMic":false,"lockSettingsDisableNote":false,"roomSizeProfiles":"small"},"medium":{"maxParticipants":150,"muteOnStart":true,"webcamsOnlyForModerator":true,"lockSettingsDisableCam":false,"lockSettingsDisableMic":false,"lockSettingsDisableNote":false,"roomSizeProfiles":"medium"},"large":{"maxParticipants":300,"muteOnStart":true,"webcamsOnlyForModerator":false,"lockSettingsDisableCam":true,"lockSettingsDisableMic":true,"lockSettingsDisableNote":true,"roomSizeProfiles":"large"},"no-limit":{"maxParticipants":null,"muteOnStart":false,"webcamsOnlyForModerator":false,"lockSettingsDisableCam":false,"lockSettingsDisableMic":false,"lockSettingsDisableNote":false,"roomSizeProfiles":"no-limit"}}', true);

        $result = DBManager::get()->query('SELECT * FROM vc_meeting_course
            INNER JOIN vc_meetings AS m
                ON meeting_id = m.id ORDER BY m.name');

        while($data = $result->fetch()) {
            $meetingCourse = MeetingCourse::findBySQL('meeting_id = ? AND course_id = ?', [
                $data['meeting_id'], $data['course_id']
            ]);

            $members_count = count($meetingCourse->course->members) + 5;
            $maxParticipants = array_column($roomSizeProfiles, 'maxParticipants');
            $profile = 'no-limit';
            for ($i = 0; $i < count($maxParticipants); $i++) {
                if ($maxParticipants[$i + 1]  >= $members_count && $members_count > $maxParticipants[$i] ) {
                    $profile = array_search($maxParticipants[$i + 1], array_column($roomSizeProfiles, 'maxParticipants', 'roomSizeProfiles'));
                }
            }
            $preset = $roomSizeProfiles[$profile];
            unset($preset['maxParticipants']);

            $features = json_decode($meetingCourse->meeting->features, true);
            if ($features && $preset) {
                $features['maxParticipants'] = $members_count;
                foreach ($features as $feature => $value) {
                    if (array_key_exists($feature, $preset)) {
                        $features[$feature] = $preset[$feature];
                    }
                }

                $meetingCourse->meeting->features = json_encode($features);
                $meetingCourse->meeting->store();
            }

            unset($meetingCourse);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {

    }
}
