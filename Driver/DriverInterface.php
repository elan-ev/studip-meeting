<?php

namespace ElanEv\Driver;

/**
 * Common interface for different conference server API driver implementations.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
interface DriverInterface
{
    /**
     * Creates a new meeting with the given parameters.
     *
     * @param MeetingParameters $parameters Options to configure the meeting
     *
     * @return bool True if the meeting has been created successfully, false
     *              otherwise
     */
    public function createMeeting(MeetingParameters $parameters);

    /**
     * Checks if a meeting with a certain id is already running.
     *
     * @param string $meetingId The id of the meeting
     *
     * @return bool True if the meeting is running, false otherwise
     */
    public function isMeetingRunning($meetingId);

    /**
     * Returns the URL which can be browsed to join a meeting.
     *
     * @param JoinParameters $parameters Parameters that describe the meeting
     *                                   to join
     *
     * @return string The URL
     */
    public function getJoinMeetingUrl(JoinParameters $parameters);
}
