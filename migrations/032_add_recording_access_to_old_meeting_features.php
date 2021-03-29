<?php

require __DIR__.'/../vendor/autoload.php';

/**
 * Adding giveAccessToRecordings params to meeting features.
 *
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
*/

use ElanEv\Model\MeetingCourse;

class AddRecordingAccessToOldMeetingFeatures extends Migration
{

    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return "add giveAccessToRecordings params to meeting features, applies to meetings created before v2.42";
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        $meetingCourses = MeetingCourse::findAll();
        foreach ($meetingCourses as $meetingCourse) {
            $features = json_decode($meetingCourse->meeting->features, true);
            if (!isset($features['giveAccessToRecordings'])) {
                $features['giveAccessToRecordings'] = true;
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
        // No downgrade applies here, since the upgrade is complementary.
    }
}