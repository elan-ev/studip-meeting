<?php

namespace ElanEv\Driver;

/**
 * Interface for conference server APIs which provide api-access to recordings
 *
 * @author Till Glöggler <tgloeggl@uos.de>
 */
interface RecordingInterface
{
    /**
     * Returns a list of recordings for the passed room or false if none are present
     *
     * @param MeetingParameters $parameters Options to configure the meeting
     *
     * @return bool list of urls to recordings (if any), false
     *              otherwise
     */
    public function getRecordings(MeetingParameters $parameters);
}
