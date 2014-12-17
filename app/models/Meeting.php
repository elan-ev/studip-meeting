<?php

namespace ElanEv\Model;

use ElanEv\Driver\MeetingParameters;

/**
 * A video conference meeting.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 *
 * @property int    $id
 * @property string $identifier
 * @property mixed  $remote_id
 * @property string $course_id
 * @property string $user_id
 * @property string $name
 * @property string $driver
 * @property bool   $active
 * @property string $attendee_password
 * @property string $moderator_password
 * @property Join[] $joins
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
     * Finds all meetings for a course.
     *
     * @param string $courseId The course id
     *
     * @return Meeting[] The meetings
     */
    public static function findByCourseId($courseId)
    {
        return static::findBySQL('course_id = :course_id', array('course_id' => $courseId));
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
        return static::findBySQL('course_id = :course_id AND active = 1', array('course_id' => $courseId));
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
}
