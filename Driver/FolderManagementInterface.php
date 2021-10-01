<?php

namespace ElanEv\Driver;

/**
 * Interface for pre-upload slides which enables drivers to use Folder/File management
 *
 * @author Farbod Zamani <zamani@elan-ev.de>
 */
interface FolderManagementInterface
{
    /**
     * Prepares pre-upload slide links to be passed to the requests
     *
     * @param meetingId $meetingId the Id of the meeting
     *
     * @return array an extra option array for the request
     */
    public function prepareSlides($meetingId);

    /**
     * Returns configOptions related to File Management and PDF generation to show as slides in meetings when creating the room
     *
     * @return ConfigOption a single configOption record feature
     */
    public static function getPreUploadFeature();
}
