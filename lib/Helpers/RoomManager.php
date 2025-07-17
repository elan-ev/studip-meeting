<?php

namespace Meetings\Helpers;

use Meetings\Errors\Error;
use ElanEv\Model\Meeting;
use ElanEv\Model\MeetingCourse;
use ElanEv\Model\Driver;
use MeetingPlugin;
use Throwable;
use PluginEngine;

/**
 * RoomManager.php - contains general function to manage rooms and to evaluate related features.
 *
 * @author Farbod Zamani Broujeni (zamani@elan-ev.de)
 */
class RoomManager
{
    /**
     * validateFeatureInputs which check inputs againt the original configOptions
     *  gets the type of configOption value and validate the feature input
     *
     * @param array $features input features
     * @param string $driver_name the name of driver to get the class
     *
     * @return array $features (validated -- neccessary for Integers)
     * @return bool  $is_valid (false) in case something is not right!
     * @throws 404 Error "Validation failed" reason: Class not found (mostly)
     */
    public static function validateFeatureInputs($features, $driver_name)
    {
        try {
            $is_valid = true;
            $class    = 'ElanEv\\Driver\\' . $driver_name;
            if (in_array('ElanEv\Driver\DriverInterface', class_implements($class)) !== false) {
                if ($create_features = $class::getCreateFeatures()) {
                    //loop through create_features
                    foreach ($create_features as $create_feature_name => $create_feature_contents) {
                        if (isset($features[$create_feature_name])) {
                            switch (gettype($create_feature_contents->getValue())) {
                                case "boolean":
                                    $value = filter_var($features[$create_feature_name], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                                    if (is_null($value)) {
                                        $is_valid = false;
                                    }
                                    break;
                                case "integer":
                                    $value       = filter_var((int)$features[$create_feature_name], FILTER_VALIDATE_INT);
                                    $value_range = ($create_feature_name == 'maxParticipants') ? -1 : 1;
                                    if ($value === false || $value < $value_range || ($create_feature_name == 'duration' && $value > 1440)) {
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
                                    $text  = '';
                                    if ($create_feature_name == 'welcome') {
                                        $text = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $value);
                                    } else {
                                        $text = htmlspecialchars($value);
                                    }
                                    $features[$create_feature_name] = $text;
                            }
                        }
                    }
                }
            }
            return $is_valid ? $features : false;
        } catch (Throwable $e) {
            throw new Error(_('Überprüfung fehlgeschlagen!'), 404);
        }
    }


    /**
     * Checks if a folder assigned to a meeting still exists, otherwise remove the folder_id
     * from the Meeting model
     *
     * @param Meeting $meeting the meeting object
     */
    public static function checkAssignedFolder(Meeting $meeting)
    {
        if ($meeting->folder_id) {
            try {
                $folder = \Folder::find($meeting->folder_id);
                if (!$folder) {
                    $meeting->folder_id = null;
                    $meeting->store();
                }
            } catch (Throwable $e) {
                throw new Error(_('Der zugewiesene Ordner kann nicht überprüft werden'), 404);
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
    public static function checkRecordingCapability($driver, $cid)
    {
        $allow_recording = false;
        $message         = 'Sitzungsaufzeichnung derzeit deaktiviert.';
        $type            = '';
        $seriesid        = '';
        $record_config   = filter_var(Driver::getConfigValueByDriver($driver, 'record'), FILTER_VALIDATE_BOOLEAN);
        $opencast_config = filter_var(Driver::getConfigValueByDriver($driver, 'opencast'), FILTER_VALIDATE_BOOLEAN);
        if ($opencast_config) {
            $type    = 'opencast';
            $message = 'Opencast Serie kann nicht gefunden werden. Bis der
                        Reiter »Opencast« unter »Mehr« aktiviert wurde und eine
                        Serie angelegt wurde, ist eine Aufzeichnung nicht
                        möglich!';
            if (!empty($cid)) {
                $series_id = MeetingPlugin::checkOpenCast($cid);
                if (!empty($series_id)) {
                    $allow_recording = true;
                    $seriesid        = $series_id;
                    $message         = '';
                }
            }
        } else if ($record_config) {
            $type            = 'bbb';
            $allow_recording = true;
            $message         = '';
        }
        return [
            "allow_recording" => $allow_recording,
            "message"         => $message,
            "type"            => $type,
            "seriesid"        => $seriesid
        ];
    }

    /**
     * Checks if a group assigned to a meeting still exists, otherwise remove the group_id
     * from the MeetingCourse model
     *
     * @param MeetingCourse $meetingCourse the meeting course object
     */
    public static function checkAssignedGroup(MeetingCourse $meetingCourse)
    {
        if ($meetingCourse->group_id) {
            try {
                $group = \Statusgruppen::find($meetingCourse->group_id);
                if (!$group) {
                    $meetingCourse->group_id = null;
                    $meetingCourse->store();
                }
            } catch (Throwable $e) {
                throw new Error(_('Die zugewiesene Gruppe kann nicht überprüft werden'), 404);
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
    public static function checkGroupPermission($group_id, $cid)
    {
        $user = \User::findCurrent();
        $group = new \Statusgruppen($group_id);

        return $group->isMember($user->id)
            || ($user && is_object($GLOBALS['perm'])
                && $GLOBALS['perm']->have_studip_perm('tutor', $cid, $user->id)
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
    public static function manageCourseDefaultRoom($meeting_id, $cid, $is_default)
    {

        $meetingCourse             = new MeetingCourse([$meeting_id, $cid]);
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
    public static function autoSelectCourseDefaultRoom(MeetingCourse $meetingCourse)
    {
        $meetingCourse->is_default = 1;
        $meetingCourse->store();
    }

    /**
     * Adjust the room size settings based on current number of course participants for created room.
     *
     * @param array $meeting_course_list a list of meeting courses
     */
    public static function adjustMaxParticipants($meeting_course_list)
    {
        // Loop through the meeting course list.
        foreach ($meeting_course_list as $meetingCourse) {
            $members_count = ($meetingCourse->course->members) + 5;
            $features      = json_decode($meetingCourse->meeting->features, true);
            // In case the maxParticipants could not be read, or is set to zero (0), we reject the adjustment process.
            if (!$features || !isset($features['maxParticipants']) || $features['maxParticipants'] == 0) {
                continue;
            }

            // In case the count of course members is greater than the features setting (maxParicipants), we adjust the feature settings.
            if ($members_count > $features['maxParticipants']) {
                // Try to get driver server config.
                $servers       = Driver::getConfigValueByDriver($meetingCourse->meeting->driver, 'servers');
                $server_config = [];
                if ($servers && isset($servers[$meetingCourse->meeting->server_index])) {
                    $server_config = $servers[$meetingCourse->meeting->server_index];
                }

                $max_allowed_participants = $members_count;
                // Check if the server has maxParticipant and if member count is greater than it.
                if (isset($server_config['maxParticipants']) && $members_count > intval($server_config['maxParticipants'])) {
                    $max_allowed_participants = intval($server_config['maxParticipants']);
                }

                $features['maxParticipants'] = $max_allowed_participants;

                // Take care of server presets.
                if (isset($server_config['roomsize-presets']) && count($server_config['roomsize-presets']) > 0) {
                    foreach ($server_config['roomsize-presets'] as $size => $values) {
                        if ($features['maxParticipants'] >= intval($values['minParticipants'])) {
                            unset($values['minParticipants']);
                            foreach ($values as $feature_name => $feature_value) {
                                $value = $feature_value;
                                if (filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)) {
                                    $value = filter_var($feature_value, FILTER_VALIDATE_BOOLEAN);
                                }
                                if (isset($features[$feature_name])) {
                                    $features[$feature_name] = $value;
                                }
                            }
                        }
                    }
                }

                // Finally, we store the features back!
                $meetingCourse->meeting->features = json_encode($features);
                $meetingCourse->meeting->store();
            }
        }
    }

    /**
     * Generates the meeting plugin url with dynamic controller and params.
     *
     * NOTE: as we are using getLink by default, the generated url might be encoded initially,
     * THerefore, to get a proper result you might need to turn the param $encoded to false!
     *
     * @param string $controller where to land in terms of controller
     * @param array $params the url query string params
     * @param boolean $encoded make sure the url is encoded using getLink, otherwise getURL is used!
     *
     * @return string
     */
    public static function generateMeetingBaseURL($controller, $params = [], $encoded = true)
    {
        $meeting_link = PluginEngine::getLink('meetingplugin', $params, $controller);
        if (!$encoded) {
            $meeting_link = PluginEngine::getURL('meetingplugin', $params, $controller);
        }
        $meeting_link = ltrim(rtrim($meeting_link, '/'), '/');
        $parsed_meeting_link = parse_url($meeting_link);

        // A fallback to ensure the link is complete.
        $studip_absolute_url = rtrim($GLOBALS['ABSOLUTE_URI_STUDIP'], '/');
        if (!empty($studip_absolute_url) &&
            (empty($parsed_meeting_link['scheme']) || empty($parsed_meeting_link['host']))) {
            $parsed_studip_absolute_url = parse_url($studip_absolute_url);
            $parsed_meeting_link['scheme'] = $parsed_studip_absolute_url['scheme'] ?? 'http';
            $parsed_meeting_link['host'] = $parsed_studip_absolute_url['host'];
            if (!empty($parsed_studip_absolute_url['port'])) {
                $parsed_meeting_link['port'] = $parsed_studip_absolute_url['port'];
            }
            if (!empty($parsed_studip_absolute_url['user'])) {
                $parsed_meeting_link['user'] = $parsed_studip_absolute_url['user'];
            }
            if (!empty($parsed_studip_absolute_url['pass'])) {
                $parsed_meeting_link['pass'] = $parsed_studip_absolute_url['pass'];
            }
        }

        $meeting_base_url = self::unparse_url($parsed_meeting_link);

        return $meeting_base_url;
    }

    /**
     * Glue back the url parts.
     *
     * @param array $parsed_url the parsed url parts
     * @return string the glued url out of parsed url parts.
     */
    private static function unparse_url($parsed_url)
    {
        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
        $auth     = ($user || $pass) ? "$user$pass@" : '';
        $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';

        return "$scheme$auth$host$port$path$query$fragment";
    }
}
