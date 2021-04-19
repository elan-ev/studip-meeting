<?php
require __DIR__ . '/../vendor/autoload.php';

/**
 * Adjusts guestPolicy in features
 *
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */

use ElanEv\Model\MeetingCourse;
use ElanEv\Driver\BigBlueButton;

class AdjustGuestpolicyInFeatures extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return 'Adjusts guestPolicy in features, remove guestPolicy and add guestPolicy-ALWAY_ACCEPT & guestPolicy-ASK_MODERATOR';
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        $meetingCourses = MeetingCourse::findAll();
        foreach ($meetingCourses as $meetingCourse) {
            $always_accept = "false";
            $ask_moderator = "false";
            $features = json_decode($meetingCourse->meeting->features, true);
            if (isset($features['guestPolicy'])) {
                if ($features['guestPolicy'] == 'ALWAYS_ACCEPT') {
                    $always_accept = "true";
                }
                if ($features['guestPolicy'] == 'ASK_MODERATOR') {
                    $ask_moderator = "true";
                }
                unset($features['guestPolicy']);
            }
            $features['guestPolicy-ALWAYS_ACCEPT'] = $always_accept;
            $features['guestPolicy-ASK_MODERATOR'] = $ask_moderator;
            $meetingCourse->meeting->features = json_encode($features);
            $meetingCourse->meeting->store();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        $meetingCourses = MeetingCourse::findAll();
        foreach ($meetingCourses as $meetingCourse) {
            $guest_policy = 'ALWAY_DENY';
            $features = json_decode($meetingCourse->meeting->features, true);
            if (isset($features['guestPolicy-ALWAYS_ACCEPT'])) {
                if ($features['guestPolicy-ALWAYS_ACCEPT'] == true) {
                    $guest_policy = 'ALWAYS_ACCEPT';
                }
                unset($features['guestPolicy-ALWAYS_ACCEPT']);
            }
            if (isset($features['guestPolicy-ASK_MODERATOR'])) {
                if ($features['guestPolicy-ASK_MODERATOR'] == true) {
                    $guest_policy = 'ASK_MODERATOR';
                }
                unset($features['guestPolicy-ASK_MODERATOR']);
            }
            $features['guestPolicy'] = $guest_policy;
            $meetingCourse->meeting->features = json_encode($features);
            $meetingCourse->meeting->store();
        }
    }
}
