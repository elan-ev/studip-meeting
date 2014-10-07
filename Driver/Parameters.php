<?php

namespace ElanEv\Driver;

/**
 * Base class for concrete parameter classes.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
abstract class Parameters
{
    /**
     * @var int The meeting id
     */
    protected $meetingId;

    public function setMeetingId($meetingId)
    {
        $this->meetingId = $meetingId;
    }

    public function getMeetingId()
    {
        return $this->meetingId;
    }
}
