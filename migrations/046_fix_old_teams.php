<?php

/**
 * Drop expiration primary key and index, change token collation from vc_meeting_token
 *
 * @author Farbod Zamani <zamani@elan-ev.de>
 */
class FixOldTeams extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return 'Fix teams-rooms for users of the nodejs implementation';
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        // check if tables for the nodejs version exist
        $db = DBManager::get();
        $results  = $db->query("SHOW TABLES LIKE 'stt_seminare_teams'")->fetchAll();

        if (!empty($results)) {
            $stt_stmt    = $db->prepare('SELECT * FROM stt_seminare_teams WHERE seminar_id = ?');
            $update_stmt = $db->prepare("UPDATE vc_meetings
                SET features = :features, driver = 'MicrosoftTeams'
                WHERE id = :id
            ");

            // table found, try to migrate
            $results = $db->query("SELECT vc_meetings.*, vc_meeting_course.course_id FROM vc_meetings
                LEFT JOIN vc_meeting_course ON (vc_meeting_course.meeting_id = vc_meetings.id)
                WHERE driver = 'teams'
                    AND course_id IS NOT NULL");

            while ($data = $results->fetch(PDO::FETCH_ASSOC)) {
                $features = json_decode($data['features'], true);

                if (!isset($features['teams'])) {
                    $features['teams'] = [];
                }

                // get data from nodejs-table
                $stt_stmt->execute([$data['course_id']]);
                $stt = $stt_stmt->fetch(PDO::FETCH_ASSOC);

                if (!empty($stt))
                $features['teams'] = [
                    'groupId' => $stt['teams_course_Id'],
                    'name'    => $stt['teams_course_name'],
                    'webUrl'  => $stt['teams_course_web_url']
                ];

                $json = json_encode($features);

                if (!empty($json)) {
                    $update_stmt->execute([
                        'id'       => $data['id'],
                        'features' => $json
                    ]);
                }
            }
        }

        SimpleORMap::expireTableScheme();
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {

        SimpleORMap::expireTableScheme();
    }
}
