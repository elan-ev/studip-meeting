<?php

namespace Meetings;

use Psr\Container\ContainerInterface;
use Meetings\Errors\Error;
use ElanEv\Model\Meeting;
use ElanEv\Model\MeetingCourse;
use ElanEv\Model\Driver;
use MeetingPlugin;
use Throwable;

class MeetingsController
{
    /**
     * Der Konstruktor.
     *
     * @param ContainerInterface $container der Dependency Container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * validateFeatureInputs which check inputs againt the original configOptions
     *  gets the type of configOption value and validate the feature input
     *
     *  @param array $features input features
     *  @param string $driver_name the name of driver to get the class
     *
     *  @return array $features (validated -- neccessary for Integers)
     *  @return bool  $is_valid (false) in case something is not right!
     *  @throws 404 Error "Validation failed" reason: Class not found (mostly)
     */
    public function validateFeatureInputs($features, $driver_name) {
        try {
            $is_valid = true;
            $class = 'ElanEv\\Driver\\' . $driver_name;
            if (in_array('ElanEv\Driver\DriverInterface', class_implements($class)) !== false) {
                if ($create_features = $class::getCreateFeatures()) {
                    //loop through create_features
                    foreach ($create_features as $create_feature_name => $create_feature_contents ) {
                        if (isset($features[$create_feature_name])) {
                            switch (gettype($create_feature_contents->getValue())) {
                                case "boolean":
                                    $value = filter_var($features[$create_feature_name], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                                    if (is_null($value)) {
                                        $is_valid = false;
                                    }
                                    break;
                                case "integer":
                                    $value = filter_var((int)$features[$create_feature_name], FILTER_VALIDATE_INT);
                                    if (!$value || $value < 1 || ($create_feature_name == 'duration' && $value > 1440)) {
                                        $is_valid = false;
                                    } else {
                                        $features[$create_feature_name] = $value;
                                    }
                                    break;
                                case "array":
                                    if (!array_key_exists($features[$create_feature_name], $create_feature_contents->getValue())) {
                                        $is_valid = false;
                                    }
                                    break;
                                default:
                                    $value = (string)$features[$create_feature_name];
                                    $text = '';
                                    if ($create_feature_name == 'welcome') {
                                        $text = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $value);
                                    } else {
                                        $text = filter_var($value, FILTER_SANITIZE_STRING);
                                    }
                                    $features[$create_feature_name] = $text;
                            }
                        }
                    }
                }
            }
            return $is_valid ? $features : false;
        } catch (Throwable $e) {
            throw new Error(_('Validation failed!'), 404);
        }
    }


    /**
     * Checks if a folder assigned to a meeting still exists, otherwise remove the folder_id
     * from the Meeting model
     *
     * @param Meeting $meeting the meeting object
     */
    public function checkAssignedFolder(Meeting $meeting) {
        if ($meeting->folder_id) {
            try {
                $folder = \Folder::find($meeting->folder_id);
                if (!$folder) {
                    $meeting->folder_id = null;
                    $meeting->store();
                }
            } catch (Throwable $e) {
                throw new Error(_('Unable to check Assigned Folder'), 404);
            }
        }
    }

    /**
     * Check recording capabilities based on config and course features
     *
     * @param string $driver the driver name
     * @param string $cid the course id
     *
     * @return array
     */
    public function checkRecordingCapability($driver, $cid) {
        $allow_recording = false;
        $message = 'Sitzungsaufzeichnung ist nicht erlaubt.';
        $type = '';
        $seriesid = '';
        $record_config = filter_var(Driver::getConfigValueByDriver($driver, 'record'), FILTER_VALIDATE_BOOLEAN);
        $opencast_config = filter_var(Driver::getConfigValueByDriver($driver, 'opencast'), FILTER_VALIDATE_BOOLEAN);
        if ($opencast_config) {
            $type = 'opencast';
            $message = 'Opencast Serie kann nicht gefunden werden. Bis der
                        Reiter »Opencast« unter »Mehr« aktiviert wurde und eine
                        Serie angelegt wurde, ist eine Aufzeichnung nicht
                        möglich!';
            if (!empty($cid)) {
                $series_id = MeetingPlugin::checkOpenCast($cid);
                if (!empty($series_id)) {
                    $allow_recording = true;
                    $seriesid = $series_id;
                    $message = '';
                }
            }
        } else if ($record_config) {
            $type = 'bbb';
            $allow_recording = true;
            $message = '';
        }
        return [
            "allow_recording" => $allow_recording,
            "message" => $message,
            "type" => $type,
            "seriesid" => $seriesid
        ];
    }

    /**
     * Checks if a group assigned to a meeting still exists, otherwise remove the group_id
     * from the MeetingCourse model
     *
     * @param MeetingCourse $meetingCourse the meeting course object
     */
    public function checkAssignedGroup(MeetingCourse $meetingCourse) {
        if ($meetingCourse->group_id) {
            try {
                $group = \Statusgruppen::find($meetingCourse->group_id);
                if (!$group) {
                    $meetingCourse->group_id = null;
                    $meetingCourse->store();
                }
            } catch (Throwable $e) {
                throw new Error(_('Unable to check Assigned Group'), 404);
            }
        }
        return $meetingCourse;
    }

    /**
     * This method check the permission (global and if he is in the group) for a given user
     *
     * @param $group_id The Group-ID
     * @param $cid The Course-ID
     * @return bool True if user have permission, False otherwise
     */
    public function checkGroupPermission($group_id, $cid)
    {
        global $perm, $user;
        $group = new \Statusgruppen($group_id);

        return $group->isMember($user->id)
            || ($user && is_object($perm)
                && $perm->have_studip_perm('tutor', $cid, $user->id)
            );
    }

    /**
     * Selects/Deselect a room as default for a course.
     * When a room is selected as default, other default room will be deselected automatically.
     *
     * @param string $meeting_id room id
     * @param string $cid course id
     * @param int $is_default the default flag
     */
    public function manageCourseDefaultRoom($meeting_id, $cid, $is_default) {

        $meetingCourse = new MeetingCourse([$meeting_id, $cid]);
        $meetingCourse->is_default = $is_default;
        $meetingCourse->store();

        // Check for other records.
        $otherCourseMeetings = MeetingCourse::findBySQL('course_id = ? AND meeting_id != ?', [$cid, $meeting_id]);
        // Make sure there is no other default room if there are other records and we select this room as default.
        if (!empty($otherCourseMeetings) && $is_default == 1) {
            // Loop through all other courseMeeting records.
            foreach ($otherCourseMeetings as $meetingCourse) {
                $meetingCourse->is_default = 0;
                $meetingCourse->store();
            }
        }
    }

    /**
     * When There is only one room in course, this method helps to auto select it as default.
     *
     * @param MeetingCourse $meetingCourse the meeting course object
     */
    public function autoSelectCourseDefaultRoom(MeetingCourse $meetingCourse) {
        $meetingCourse->is_default = 1;
        $meetingCourse->store();
    }
}
