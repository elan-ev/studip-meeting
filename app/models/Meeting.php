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
 * @property string    $recording_url
 * @property string    $driver
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

        $this->has_one['meeting_token'] = array(
            'class_name' => 'ElanEv\Model\MeetingToken',
            'assoc_foreign_key' => 'meeting_id',
            'on_delete' => 'delete'
        );

        parent::__construct($id);

        if (!$this->identifier) {
            $this->identifier = md5(uniqid());
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function configure($config = array())
    {
        $config['db_table'] = 'vc_meetings';
        $config['has_many']['joins'] = array(
            'class_name' => 'ElanEv\Model\Join',
            'assoc_foreign_key' => 'meeting_id',
            'on_delete' => 'delete',
        );
        $config['has_and_belongs_to_many']['courses'] = array(
            'class_name' => 'Course',
            'thru_table' => 'vc_meeting_course',
            'thru_key' => 'meeting_id',
            'thru_assoc_key' => 'course_id',
            'assoc_foreign_key' => 'seminar_id',
            'on_store' => true,
            'on_delete' => true,
        );

        $config['has_one']['meeting_token'] = array(
            'class_name' => 'ElanEv\Model\MeetingToken',
            'assoc_foreign_key' => 'meeting_id',
            'on_delete' => 'delete'
        );

        parent::configure($config);
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
        //Do we need one to many courses relationship anymore?
        $parameters->setMeetingName($this->courses[0]->name . ' - ' . $this->name);
        $parameters->setAttendeePassword($this->attendee_password);
        $parameters->setModeratorPassword($this->moderator_password);
        $parameters->setMeetingFeatures($this->features);

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
}
