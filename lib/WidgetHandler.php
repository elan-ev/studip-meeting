<?php

namespace Meetings;

use ElanEv\Model\MeetingCourse;
// use CourseMember;
use CourseDate;
use Course;
use PluginEngine;

/**
 * WidgetHandler.php - contains function for MeetingPlugin widget handling.
 *
 * @author Farbod Zamani Broujeni (zamani@elan-ev.de)
 */
class WidgetHandler
{
    /**
     * Get all current and up-coming meetings
     *
     * @param array $userCourses list of courses which user is participated and has a default meeting room
     * @return array
     */
    public static function getMeetingsForWidget()
    {
        global $user, $perm;

        // Handle Admin users.
        if ($perm->have_perm('admin') || $perm->have_perm('root')) {
            $courses = Course::findBySQL(
                'INNER JOIN vc_meeting_course AS mc ON seminar_id = mc.course_id
                 WHERE mc.is_default = 1 AND mc.active = 1'
            );
        } else {
            $courses = Course::findBySQL(
                'INNER JOIN seminar_user AS su USING(Seminar_id)
                 INNER JOIN vc_meeting_course AS mc ON seminar_id = mc.course_id
                 WHERE su.user_id = ? AND mc.is_default = 1 AND mc.active = 1 ORDER BY mkdate ASC',
                 [$user->id]
            );
        }

        $widgetItemsArray = self::getTodaysMeetings($courses);

        return $widgetItemsArray;
    }

    /**
     * Sort the meetings which are happening or will be happened today.
     *
     * @param array $courses list of courses which user is participated and has a default meeting room
     * @return array
     */
    private function getTodaysMeetings($courses) {
        if (empty($courses)) {
            return [];
        }

        $currents = [];
        $upcomings = [];

        $whereCurrent = 'range_id = ? AND date <= ? AND end_time >= ?';
        $whereUpcoming = 'range_id = ? AND date > ? AND end_time < ? ORDER BY date ASC';
        $now = strtotime('now');
        $tonight = strtotime('tomorrow midnight');

        foreach ($courses as $course) {
            $currentSessionDate = CourseDate::findOneBySQL($whereCurrent,
                [   
                    $course->seminar_id,
                    $now,
                    $now,
                ]);
            if ($currentSessionDate) {
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
            if ($upcomingSessionDate) {
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
    private function prepareWidgetItems(Course $course, CourseDate $courseDate) {
        $meetingCourse = MeetingCourse::findOneBySQL('course_id = ? AND is_default = 1', [$course->seminar_id]);
        $widgetItem = [
            'item_title' => $course->name . ': ' . _('Heute') . date(", H:i", $courseDate->date) . " - " . date("H:i", $courseDate->end_time),
            'course_id' => $course->seminar_id,
            'course_title' => $course->name,
            'termin_id' => $courseDate->termin_id,
            'termin_start' => $courseDate->date,
            'termin_end' => $courseDate->end_time,
            'termin_fullname' => $courseDate->getFullname('verbose'),
            'meeing_name' => $meetingCourse->meeting->name,
            'meeting_id' => $meetingCourse->meeting->id,
            'meeting_course_url' => PluginEngine::getURL('meetingplugin', ['cid' => $course->id], 'index', true),
            'meeting_join_url' => PluginEngine::getURL('meetingplugin', [], "api/rooms/join/{$course->id}/{$meetingCourse->meeting->id}")
        ];

        return $widgetItem;
    }
}