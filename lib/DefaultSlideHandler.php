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
     * @param string $courseid the meeting object
     * @param int $limit the number of news to show, default is 3.
     * @return array
     */
    public static function getNewsList($range = 'studip', $limit = 3)
    {
        $news = [];

        $news = StudipNews::getNewsByRange($range, true, false);

        if (count($news) > $limit) {
            $news = array_slice($news, 0, $limit);
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
            'course_news_title' => I18N::_('Veranstaltungsankündigungen'),
            'studip_news_title' => I18N::_('Allgemeine Ankündigungen'),
        ];
        return $texts;
    }
}