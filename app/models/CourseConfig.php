<?php

namespace ElanEv\Model;

/**
 * TODO: documentation
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 *
 * @property string $course_id    The course id
 * @property string $title        The title and label for navigation items
 * @property string $introduction The introductory text
 */
class CourseConfig extends \SimpleORMap
{
    /**
     * {@inheritdoc}
     */
    public static function configure($config = array())
    {
        $config['db_table'] = 'vc_course_config';
        parent::configure($config);
    }

    /**
     * {@inheritdoc}
     */
    public function __get($name)
    {
        $value = parent::__get($name);

        if ($name === 'title' && ($value === null || trim($value) === '')) {
            return 'Meetings';
        }

        return $value;
    }

    /**
     * Get course configuration for passed course
     *
     * @param string $courseId The course id
     *
     * @return CourseConfig The meeting configuration for the given course
     */
    public static function findByCourseId($courseId)
    {
        $config = static::findOneBySQL('course_id = :course_id', array('course_id' => $courseId));

        if ($config === null) {
            $config = new CourseConfig();
            $config->course_id = $courseId;
        }

        return $config;
    }
}
