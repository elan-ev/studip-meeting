<?php

/**
 * Adds an index on vc_meetings_course.course_id for perfomrance reasons.
 *
 * @author Farbod Zamani Broujeni (zamani@elan-ev.de)
 */
class AddIndexOnVcMeetingsCourseCourseId extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return 'Adds an index on vc_meeting_course.course_id for perfomrance reasons';
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        // avoid running this migration twice
        if ($this->hasIndex()) {
            return;
        }

        $query = "ALTER TABLE `vc_meeting_course`
                    ADD INDEX `course_id` (`course_id`)";
        DBManager::get()->exec($query);

        SimpleORMap::expireTableScheme();
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        if (!$this->hasIndex()) {
            return;
        }

        $query = "ALTER TABLE `vc_meeting_course`
                    DROP INDEX `course_id`";
        DBManager::get()->exec($query);


        SimpleORMap::expireTableScheme();
    }

    /**
     * Returns whether the table vc_meetings_course already has the index on
     * column "course_id".
     */
    private function hasIndex(): bool
    {
        $query = "SHOW INDEX FROM vc_meeting_course WHERE Key_name = 'course_id'";
        $result = DBManager::get()->query($query);

        return $result && $result->rowCount() > 0;
    }
}
