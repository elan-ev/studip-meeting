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
}
