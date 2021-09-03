<?php

namespace Meetings;
use Meetings\Models\I18N;
use ElanEv\Model\Meeting;

use CourseDate;
use StudipNews;
/**
 * DefaultSlideHandler.php - contains function to handle news & announcements to show as default slide.
 *
 * @author Farbod Zamani Broujeni (zamani@elan-ev.de)
 */
class DefaultSlideHandler
{
    /**
     * Get all news from course and studip
     * 
     * @param Meeting the meeting object
     * @return array
     */
    public static function getNewsList(Meeting $meeting)
    {
        $news = [];
        $course = $meeting->courses[0];

        $course_news = StudipNews::getNewsByRange($course->seminar_id, true, false);
        if (!empty($course_news)) {
            $news['course']['news'] = $course_news;
            $news['course']['texts'] = [
                'title' => I18N::_('Veranstaltungsankündigungen'),
            ];
        }

        $studip_news = StudipNews::getNewsByRange('studip', true, false);
        if (!empty($studip_news)) {
            $news['studip']['news'] = $studip_news;
            $news['studip']['texts'] = [
                'title' => I18N::_('Allgemeine Ankündigungen'),
            ];
        }

        return $news;
    }

    /**
     * Get all required texts to replace in intro template
     *
     * @param Meeting the meeting object
     * @return array
     */
    public static function getIntroTexts(Meeting $meeting)
    {
        $course = $meeting->courses[0];
        $texts = [
            'welcome' => I18N::_('Willkommen zur Videokonferenz'),
            'meeting_name' => $meeting->name,
            'course_name' => $course->name,
        ];
        return $texts;
    }
}