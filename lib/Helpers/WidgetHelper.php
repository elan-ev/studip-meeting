<?php

namespace Meetings\Helpers;

use ElanEv\Model\MeetingCourse;
use ElanEv\Model\Driver;
use CourseDate;
use Course;
use User;
use PluginEngine;

/**
 * WidgetHelper.php - contains function for MeetingPlugin widget handling.
 *
 * @author Farbod Zamani Broujeni (zamani@elan-ev.de)
 */
class WidgetHelper
{
    /**
     * Get all current and up-coming meetings
     *
     * @return array
     */
    public static function getMeetingsForWidget()
    {
        $user    = User::findCurrent();
        $courses = [];
        // To avoid performance issue, this feature is permanently out for the administrators.
        // Anyone except admin or root!
        if (!$GLOBALS['perm']->have_perm('admin') && !$GLOBALS['perm']->have_perm('root') && $user) {
            $courses = Course::findBySQL(
                'INNER JOIN seminar_user AS su USING(Seminar_id)
                    INNER JOIN vc_meeting_course AS mc ON seminar_id = mc.course_id
                    WHERE su.user_id = ? AND mc.is_default = 1 AND mc.active = 1 ORDER BY mkdate ASC',
                [$user->id]
            );
        }

        return self::getTodaysMeetings($courses);
    }

    /**
     * Sort the meetings which are happening or will be happened today.
     *
     * @param array $courses list of courses which user is participated and has a default meeting room
     * @return array
     */
    private static function getTodaysMeetings($courses)
    {
        if (empty($courses)) {
            return [];
        }

        $currents  = [];
        $upcomings = [];

        $whereCurrent  = 'range_id = ? AND date <= ? AND end_time >= ?';
        $whereUpcoming = 'range_id = ? AND date > ? AND end_time < ? ORDER BY date ASC';
        $now           = strtotime('now');
        $tonight       = strtotime('tomorrow midnight');

        foreach ($courses as $course) {
            $currentSessionDate = CourseDate::findOneBySQL($whereCurrent,
                [
                    $course->seminar_id,
                    $now,
                    $now,
                ]);
            if ($currentSessionDate && empty($currentSessionDate->room_booking->resource_id)) {
                $widgetItem = self::prepareWidgetItems($course, $currentSessionDate);
                if (!empty($widgetItem)) {
                    $currents[] = $widgetItem;
                }
                // Here we stop the process by simply passing the itteration.
                continue;
            }

            $upcomingSessionDate = CourseDate::findOneBySQL($whereUpcoming,
                [
                    $course->seminar_id,
                    $now,
                    $tonight,
                ]);
            if ($upcomingSessionDate && empty($upcomingSessionDate->room_booking->resource_id)) {
                $upcomings[] = self::prepareWidgetItems($course, $upcomingSessionDate);
            }
        }

        $widgetItemsArray = [];

        if (!empty($currents)) {
            $widgetItemsArray['current'] = $currents;
        }

        if (!empty($upcomings)) {
            $widgetItemsArray['upcoming'] = $upcomings;
        }

        return $widgetItemsArray;
    }

    /**
     * Prepare the widget item.
     *
     * @param Course $course course object
     * @param CourseDate $courseDate course date object
     * @return array
     */
    private static function prepareWidgetItems(Course $course, CourseDate $courseDate)
    {
        $meetingCourse = MeetingCourse::findOneBySQL('course_id = ? AND is_default = 1', [$course->seminar_id]);
        $features      = json_decode($meetingCourse->meeting->features, true);
        $widgetItem    = [
            'item_title'         => $course->name . ': ' . _('Heute') . date(", H:i", $courseDate->date) . " - " . date("H:i", $courseDate->end_time),
            'course_id'          => $course->seminar_id,
            'course_title'       => $course->name,
            'termin_id'          => $courseDate->termin_id,
            'termin_start'       => $courseDate->date,
            'termin_end'         => $courseDate->end_time,
            'termin_fullname'    => $courseDate->getFullname('verbose'),
            'meeing_name'        => $meetingCourse->meeting->name,
            'meeting_id'         => $meetingCourse->meeting->id,
            'meeting_course_url' => PluginEngine::getURL('meetingplugin', ['cid' => $course->id], 'index', true),
            'meeting_join_url'   => PluginEngine::getURL('meetingplugin', [], "api/rooms/join/{$course->id}/{$meetingCourse->meeting->id}")
        ];

        // Display Privacy Agreement.
        $showRecordingPrivacyText = Driver::getGeneralConfigValue('show_recording_privacy_text');
        if ($showRecordingPrivacyText && !$GLOBALS['perm']->have_studip_perm('tutor', $course->id)
            && isset($features['record']) && $features['record'] == 'true') {
            $widgetItem['privacy_notice'] = true;
        }

        return $widgetItem;
    }
}
