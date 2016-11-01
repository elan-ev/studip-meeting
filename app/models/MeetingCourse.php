<?php

namespace ElanEv\Model;

/**
 * Association of {@link Meeting meetings} to courses.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 *
 * @property Meeting $meeting
 * @property \Course $course
 * @property bool    $active
 */
class MeetingCourse extends \SimpleORMap
{
    public function __construct($id = null)
    {
        $this->db_table = 'vc_meeting_course';
        $this->has_one['meeting'] = array(
            'class_name' => 'ElanEv\Model\Meeting',
            'foreign_key' => 'meeting_id',
            'assoc_foreign_key' => 'id',
        );
        $this->has_one['course'] = array(
            'class_name' => 'Course',
            'foreign_key' => 'course_id',
            'assoc_foreign_key' => 'id',
        );

        parent::__construct($id);
    }

    /**
     * {@inheritdoc}
     */
    public static function configure($config = array())
    {
        $config['db_table'] = 'vc_meeting_course';
        $config['has_one']['meeting'] = array(
            'class_name' => 'ElanEv\Model\Meeting',
            'foreign_key' => 'meeting_id',
            'assoc_foreign_key' => 'id',
        );
        $config['has_one']['course'] = array(
            'class_name' => 'Course',
            'foreign_key' => 'course_id',
            'assoc_foreign_key' => 'id',
        );

        parent::configure($config);
    }

    /**
     * Finds all meetings.
     *
     * @return MeetingCourse[] The meetings
     */
    public static function findAll()
    {
        return static::findBySQL('INNER JOIN vc_meetings AS m ON meeting_id = m.id ORDER BY m.name');
    }

    /**
     * Finds all meetings for a course.
     *
     * @param string $courseId The course id
     *
     * @return MeetingCourse[] The meetings
     */
    public static function findByCourseId($courseId)
    {
        return static::findBySQL(
            'INNER JOIN vc_meetings AS m ON meeting_id = m.id WHERE course_id = :course_id ORDER BY m.name',
            array('course_id' => $courseId)
        );
    }

    /**
     * Finds all meetings for a course.
     *
     * @param string $courseId The course id
     *
     * @return MeetingCourse[] The meetings
     */
    public static function findActiveByCourseId($courseId)
    {
        return static::findBySQL(
            'INNER JOIN vc_meetings AS m ON meeting_id = m.id WHERE active = 1 AND course_id = :course_id ORDER BY m.name',
            array('course_id' => $courseId)
        );
    }

    /**
     * Finds all meetings that a certain user created.
     *
     * @param \Seminar_User $user The user
     *
     * @return MeetingCourse[] The meetings
     */
    public static function findByUser(\Seminar_User $user)
    {
        return static::findBySQL(
            'INNER JOIN vc_meetings AS m ON meeting_id = m.id WHERE m.user_id = :user_id ORDER BY m.name',
            array('user_id' => $user->cfg->getUserId())
        );
    }

    /**
     * Finds all meetings that a certain user created and that can be linked
     * to a particular course.
     *
     * A course can only be linked if it is not already associated with the
     * meeting.
     *
     * @param \Seminar_User $user   The user
     * @param \Course       $course The course in which the link should be
     *                              added
     *
     * @return MeetingCourse[] The meetings
     */
    public static function findLinkableByUser(\Seminar_User $user, \Course $course)
    {
        $meetingCourses = static::findByUser($user);
        $linkableMeetings = array();

        foreach ($meetingCourses as $meetingCourse) {
            if (!$meetingCourse->meeting->isAssignedToCourse($course)) {
                $linkableMeetings[] = $meetingCourse;
            }
        }

        return $linkableMeetings;
    }
}
