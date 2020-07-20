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

    /**
     * Deletes single or multi recordings 
     *
     * @param  array | string  $recordID recording ID
     * @return bool
     */
    public function deleteRecordings($recordID);

    /**
     * Returns a list of opencast related config vars or false if OpenCast plugin is not there
     *
     * @return array | bool opencast related configs list, false if OpenCast is not there
     */
    public function useOpenCastForRecording();


    /**
     * Returns recording configOptions required when creating the room
     *
     * @return ConfigOption a single configOption record feature 
     */
    public function getRecordFeature();
}
