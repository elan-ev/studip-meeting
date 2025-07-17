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
            $old_intro = $data['introduction'] ?? '';
            if (!empty(trim($old_intro))) {
                // Make sure the introduction has no empty array (json) string.
                $old_intro = str_replace('[]', '', $old_intro);
                $introduction_str = null;
                if (!empty($old_intro)) {
                    $new_intro = new \stdClass();
                    $new_intro->title = '';
                    $new_intro->text = $old_intro;
                    $introductions[] = $new_intro;
                    $introduction_str = json_encode($introductions);
                }

                $update_stmt->execute([
                    'id'           => $data['id'],
                    'introduction' => $introduction_str
                ]);
            }
        }

        // Rename introduction to introductions.
        $db->exec("ALTER TABLE vc_course_config CHANGE COLUMN `introduction` `introductions` TEXT DEFAULT NULL");

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
            $introductions = json_decode($data['introductions'], true);
            if (!empty($introductions)) {
                $new_intro = $introductions[0];
                $text = $new_intro->text;

                $update_stmt->execute([
                    'id'           => $data['id'],
                    'introduction' => !empty($text) ? $text : null
                ]);
            }
        }

        // Rename introductions to introduction.
        $db->exec("ALTER TABLE vc_course_config CHANGE COLUMN `introductions` `introduction` TEXT DEFAULT NULL");

        SimpleORMap::expireTableScheme();
    }
}
