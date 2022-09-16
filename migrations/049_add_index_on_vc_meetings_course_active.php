<?php

/**
 * Adds an index on vc_meetings_course.active for perfomrance reasons.
 *
 * @author Jan-Hendrik Willms <tleilax+studip@gmail.com>
 */
class AddIndexOnVcMeetingsCourseActive extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return 'Adds an index on vc_meetings_course.active for perfomrance reasons';
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
                  ADD INDEX `active` (`active`)";
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
                  DROP INDEX `active`";
        DBManager::get()->exec($query);


        SimpleORMap::expireTableScheme();
    }

    /**
     * Returns whether the table vc_meetings_course already has the index on
     * column "active".
     */
    private function hasIndex(): bool
    {
        $query = "SHOW INDEX FROM vc_meetings_course WHERE Key_name = 'active'";
        $result = DBManager::get()->query($query);

        return $result && $result->rowCount() > 0;
    }
}
