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
        $query = "ALTER TABLE `vc_meeting_course`
                    ADD INDEX `course_id` (`course_id`)";
        DBManager::get()->exec($query);
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        $query = "ALTER TABLE `vc_meeting_course`
                    DROP INDEX `course_id`";
        DBManager::get()->exec($query);
    }
}
