<?php

/**
 * Change and migrate the introduction column to introductions in course config table.
 *
 * @author Farbod Zamani Broujeni (zamani@elan-ev.de)
 */
class MigrateIntroductionColumnCourseConfig extends Migration
{
    /**
     * {@inheritdoc}
     */
    function description()
    {
        return 'Change and migrate the introduction column to introductions in course config table.';
    }

    /**
     * {@inheritdoc}
     */
    function up()
    {
        $db = DBManager::get();
        $update_stmt = $db->prepare("UPDATE vc_course_config
            SET introduction = :introduction WHERE id = :id");
        $results = $db->query("SELECT * FROM vc_course_config");

        while ($data = $results->fetch(PDO::FETCH_ASSOC)) {
            $introductions = [];
            $old_intro = $data['introduction'];
            $new_intro = new \stdClass();
            $new_intro->title = '';
            $new_intro->text = $old_intro;
            $introductions[] = $new_intro;

            $update_stmt->execute([
                'id'           => $data['id'],
                'introduction' => json_encode($introductions)
            ]);
        }

        // Rename introduction to introductions.
        $db->exec("ALTER TABLE vc_course_config RENAME COLUMN introduction TO introductions");

        SimpleORMap::expireTableScheme();
    }

    /**
     * {@inheritdoc}
     */
    function down()
    {
        $db = DBManager::get();
        $update_stmt = $db->prepare("UPDATE vc_course_config
            SET introductions = :introduction WHERE id = :id");
        $results = $db->query("SELECT * FROM vc_course_config");

        while ($data = $results->fetch(PDO::FETCH_ASSOC)) {
            $introductions = json_decode($data['introductions']);
            if (!empty($introductions)) {
                $new_intro = $introductions[0];
                $text = $new_intro->text;
                if (!empty($text)) {
                    $update_stmt->execute([
                        'id'           => $data['id'],
                        'introduction' => $text
                    ]);
                }
            }
        }

        // Rename introductions to introduction.
        $db->exec("ALTER TABLE vc_course_config RENAME COLUMN introductions TO introduction");

        SimpleORMap::expireTableScheme();
    }
}
