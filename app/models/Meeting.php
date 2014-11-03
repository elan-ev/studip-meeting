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
 * @property string $name
 * @property string $driver
 * @property bool   $active
 * @property string $attendee_password
 * @property string $moderator_password
 */
class Meeting extends \SimpleORMap
{
    public function __construct($id = null)
    {
        $this->db_table = 'vc_meetings';

        parent::__construct($id);

        if (!$this->identifier) {
            $this->identifier = md5(uniqid());
        }
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
}
