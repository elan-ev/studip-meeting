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

    /**
     * @var string A unique identifier
     */
    protected $identifier;

    /**
     * @var mixed The remote identifier
     */
    protected $remoteId;

    public function setMeetingId($meetingId)
    {
        $this->meetingId = $meetingId;
    }

    public function getMeetingId()
    {
        return $this->meetingId;
    }

    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function setRemoteId($remoteId)
    {
        $this->remoteId = $remoteId;
    }

    public function getRemoteId()
    {
        return $this->remoteId;
    }
}
