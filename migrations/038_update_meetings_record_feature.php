<?php

require __DIR__.'/../vendor/autoload.php';

/**
 * Update/remove series-id record feature of meeting based on driver config
 *
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
*/

use ElanEv\Model\MeetingCourse;
use ElanEv\Model\Driver;
use MeetingPlugin;

class UpdateMeetingsRecordFeature extends Migration
{

    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return "Check the driver preferred recording config and apply that for all meetings, removes/update series-id param from meeting features";
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        $meetingCourses = MeetingCourse::findAll();
        foreach ($meetingCourses as $meetingCourse) {
            $features = json_decode($meetingCourse->meeting->features, true);
            if (isset($features['record'])
                && filter_var($features['record'], FILTER_VALIDATE_BOOLEAN)
                && $meetingCourse->meeting->driver) {

                $record_config = filter_var(Driver::getConfigValueByDriver($meetingCourse->meeting->driver, 'record'), FILTER_VALIDATE_BOOLEAN);
                $opencast_config = filter_var(Driver::getConfigValueByDriver($meetingCourse->meeting->driver, 'opencast'), FILTER_VALIDATE_BOOLEAN);

                if ($opencast_config) {
                    $series_id = self::checkOpenCast($meetingCourse->course_id);
                    if (!empty($series_id)) {
                        $features['meta_opencast-dc-isPartOf'] = $series_id;
                    } else if (isset($features['meta_opencast-dc-isPartOf'])) {
                        unset($features['meta_opencast-dc-isPartOf']);
                    }
                    $meetingCourse->meeting->features = json_encode($features);
                    $meetingCourse->meeting->store();
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        //No downgrade is available, since it is an update!
    }

    private static function checkOpenCast($cid) {
        $opencast_plugin = PluginEngine::getPlugin("OpenCast");
        if ($opencast_plugin && $opencast_plugin->isActivated($cid)) {
            $db = DBManager::get();
            $stmt = $db->prepare('SELECT series_id FROM oc_seminar_series WHERE seminar_id = ?');
            $stmt->execute(array($cid));
            $OCSeries = $stmt->fetchColumn();
            if (!empty($OCSeries)) {
                return $OCSeries;
            }
        }
        return false;
    }
}
