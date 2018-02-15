<?php

namespace ElanEv\Model;

/**
 * Logging of users joining meetings.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 *
 * @property int     $meeting_id
 * @property Meeting $meeting
 * @property string  $user_id
 * @property int     $last_join
 */
class Join extends \SimpleORMap
{
    public function __construct($id = null)
    {
        $this->db_table = 'vc_joins';
        $this->belongs_to['meeting'] = array(
            'class_name' => 'ElanEv\Model\Meeting',
            'foreign_key' => 'meeting_id',
        );

        parent::__construct($id);

        if ($this->last_join === null) {
            $this->last_join = time();
        }
    }

    public static function configure($config = array())
    {
        $config['db_table'] = 'vc_joins';

        $config['belongs_to']['meeting'] = array(
            'class_name' => 'ElanEv\Model\Meeting',
            'foreign_key' => 'meeting_id',
        );

        parent::configure($config);
    }

    /**
     * Finds the most recent joins for a meeting (the number of users that
     * joined a meeting in the last 24 hours).
     *
     * @param Meeting $meeting The meeting to filter recent joins by
     *
     * @return Join[] The joins
     */
    public static function findRecentJoinsForMeeting(Meeting $meeting)
    {
        return static::findBySQL(
            'meeting_id = :meeting_id AND last_join >= :recent_join_time GROUP BY user_id ORDER BY last_join',
            array(
                'meeting_id' => $meeting->id,
                'recent_join_time' => strtotime('-1day'),
            )
        );
    }

    /**
     * Finds all joins for a meeting (the number of users that ever joined a
     * meeting).
     *
     * @param Meeting $meeting The meeting to filter recent joins by
     *
     * @return Join[] The joins
     */
    public static function findAllJoinsForMeeting(Meeting $meeting)
    {
        return static::findBySQL('meeting_id = :meeting_id ORDER BY last_join ASC', array('meeting_id' => $meeting->id));
    }
}
