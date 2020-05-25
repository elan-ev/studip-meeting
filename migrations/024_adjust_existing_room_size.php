<?php
require __DIR__.'/../vendor/autoload.php';
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
        $roomSizeProfiles[] = [
            'maxParticipants' => 0,
            'roomSizeProfiles' => 'start'
        ];
        $roomSizeProfiles_raw = BigBlueButton::roomSizeProfile();
        foreach ($roomSizeProfiles_raw as $configOption) {
            $arr = $configOption->toArray();
            $values = array_column($arr['value'], 'value', 'name');
            $values['roomSizeProfiles'] = $arr['name'];
            $roomSizeProfiles[$arr['name']] = $values;
        }
        $meetingCourses = MeetingCourse::findAll();
        foreach ($meetingCourses as $meetingCourse) {
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
        }
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        
    }
}
