<?php

namespace ElanEv\Driver;

/**
 * Parameters to configure a meeting.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class MeetingParameters extends Parameters
{
    /**
     * @var string The meeting name
     */
    private $meetingName;

    /**
     * @var string A password needed to moderate the meeting
     */
    private $moderatorPassword;

    /**
     * @var string A password needed to attend a meeting
     */
    private $attendeePassword;

    /**
     * @var Array features that can be added to create the room (always optional)
     */
    private $meetingFeatures;

    /**
     * @var int the server index of the meeting
     */
    private $meetingServerIndex;

    public function setMeetingName($meetingName)
    {
        $this->meetingName = $meetingName;
    }

    public function getMeetingName()
    {
        return $this->meetingName;
    }

    public function setModeratorPassword($password)
    {
        $this->moderatorPassword = $password;
    }

    public function getModeratorPassword()
    {
        return $this->moderatorPassword;
    }

    public function setAttendeePassword($password)
    {
        $this->attendeePassword = $password;
    }

    public function getAttendeePassword()
    {
        return $this->attendeePassword;
    }

    public function setMeetingFeatures($features)
    {
        $this->meetingFeatures = $features;
    }

    public function getMeetingFeatures()
    {
        return $this->meetingFeatures;
    }

    public function setMeetingServerIndex($index)
    {
        $this->meetingServerIndex = $index;
    }

    public function getMeetingServerIndex()
    {
        return $this->meetingServerIndex;
    }

    public function toArray() {
        return [
            'meetingName' => self::getMeetingName(),
            'moderatorPassword' => self::getModeratorPassword(),
            'attendeePassword' => self::getAttendeePassword(),
            'attendeePassword' => self::getAttendeePassword(),
            'meetingId' => self::getMeetingId(),
            'identifier' => self::getIdentifier(),
            'remoteId' => self::getRemoteId(),
            'meetingFeatures' => self::getMeetingFeatures()
        ];
    }
}
