<?php

namespace ElanEv\Model;

use ElanEv\Driver\MeetingParameters;

/**
 * A video conference meeting.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 *
 * @property int       $id
 * @property string    $identifier
 * @property mixed     $remote_id
 * @property Meeting[] $courses
 * @property string    $user_id
 * @property string    $name
 * @property string    $driver
 * @property bool      $active
 * @property string    $attendee_password
 * @property string    $moderator_password
 * @property bool      $join_as_moderator
 * @property int       $mkdate
 * @property int       $chdate
 * @property Join[]    $joins
 */
class Meeting extends \SimpleORMap
{
    public function __construct($id = null)
    {
        $this->db_table = 'vc_meetings';
        $this->has_many['joins'] = array(
            'class_name' => 'ElanEv\Model\Join',
            'assoc_foreign_key' => 'meeting_id',
            'on_delete' => 'delete',
        );
        $this->has_and_belongs_to_many['courses'] = array(
            'class_name' => 'Course',
            'thru_table' => 'vc_meeting_course',
            'thru_key' => 'meeting_id',
            'thru_assoc_key' => 'course_id',
            'assoc_foreign_key' => 'seminar_id',
            'on_store' => true,
            'on_delete' => true,
        );

        parent::__construct($id);

        if (!$this->identifier) {
            $this->identifier = md5(uniqid());
        }
    }

    /**
     * Returns the most recent user joins of the meeting (the users that
     * joined the meeting during the last 24 hours).
     *
     * @return Join[] The joins
     */
    public function getRecentJoins()
    {
        return Join::findRecentJoinsForMeeting($this);
    }

    /**
     * Returns all user joins (the users that ever joined the meeting).
     *
     * @return Join[] The joins
     */
    public function getAllJoins()
    {
        return Join::findAllJoinsForMeeting($this);
    }

    /**
     * Returns the parameters describing the meeting.
     *
     * @return MeetingParameters
     */
    public function getMeetingParameters()
    {
        $parameters = new MeetingParameters();
        $parameters->setMeetingId($this->id);
        $parameters->setIdentifier($this->identifier);
        $parameters->setRemoteId($this->remote_id);
        $parameters->setMeetingName($this->name);
        $parameters->setAttendeePassword($this->attendee_password);
        $parameters->setModeratorPassword($this->moderator_password);

        return $parameters;
    }

    /**
     * Checks if the meeting is associated with a particular course.
     *
     * @param \Course $course The course to test
     *
     * @return bool True if the meeting and the course are associated, false
     *              otherwise
     */
    public function isAssignedToCourse(\Course $course)
    {
        foreach ($this->courses as $assignedCourse) {
            if ($assignedCourse == $course) {
                return true;
            }
        }

        return false;
    }

    /**
     * Finds all meetings for a course.
     *
     * @param string $courseId The course id
     *
     * @return Meeting[] The meetings
     */
    public static function findByCourseId($courseId)
    {
        return static::findBySQL(
            'INNER JOIN vc_meeting_course AS mc ON vc_meetings.id = mc.meeting_id WHERE mc.course_id = :course_id',
            array('course_id' => $courseId)
        );
    }

    /**
     * Finds all meetings for a course.
     *
     * @param \Seminar $course The course
     *
     * @return Meeting[] The meetings
     */
    public static function findByCourse(\Seminar $course)
    {
        return static::findByCourseId($course->id);
    }

    /**
     * Finds all active meetings for a course.
     *
     * @param string $courseId The course id
     *
     * @return Meeting[] The meetings
     */
    public static function findActiveByCourseId($courseId)
    {
        return static::findBySQL(
            'INNER JOIN vc_meeting_course AS mc ON vc_meetings.id = mc.meeting_id WHERE mc.course_id = :course_id AND active = 1',
            array('course_id' => $courseId)
        );
    }

    /**
     * Finds all active meetings for a course.
     *
     * @param \Seminar $course The course
     *
     * @return Meeting[] The meetings
     */
    public static function findActiveByCourse(\Seminar $course)
    {
        return static::findActiveByCourseId($course->id);
    }

    /**
     * Finds all meetings that a certain user created.
     *
     * @param \Seminar_User $user The user
     *
     * @return Meeting[] The meetings
     */
    public static function findByUser(\Seminar_User $user)
    {
        return static::findBySQL('user_id = :user_id', array('user_id' => $user->cfg->getUserId()));
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
     * @return Meeting[] The meetings
     */
    public static function findLinkableByUser(\Seminar_User $user, \Course $course)
    {
        $meetings = static::findByUser($user);
        $linkableMeetings = array();

        foreach ($meetings as $meeting) {
            if (!$meeting->isAssignedToCourse($course)) {
                $linkableMeetings[] = $meeting;
            }
        }

        return $linkableMeetings;
    }
}
