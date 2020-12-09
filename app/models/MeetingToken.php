<?php

namespace ElanEv\Model;

/**
 * Tokenizing the meeting to use via an external call
 *
 * @author Farbod Zamani <zamani@elan-ev.de>
 *
 */
class MeetingToken extends \SimpleORMap
{
    public function __construct($id = null)
    {
        $this->db_table = 'vc_meeting_token';
        $this->belongs_to['meeting'] = array(
            'class_name' => 'ElanEv\Model\Meeting',
            'foreign_key' => 'meeting_id',
        );

        parent::__construct($id);
    }

    /**
     * {@inheritdoc}
     */
    public static function configure($config = array())
    {
        $config['db_table'] = 'vc_meeting_token';
        $config['belongs_to']['meeting'] = array(
            'class_name' => 'ElanEv\Model\Meeting',
            'foreign_key' => 'meeting_id',
        );

        parent::configure($config);
    }

    /**
     * Finds all tokens.
     *
     * @return MeetingToken[] The tokens
     */
    public static function findAll()
    {
        return static::findBySQL('1');
    }

    public static function generate_token()
    {
        $allTokens = self::findAll();
        $tokens = array_column($allTokens, 'token');
        do {
            $token = md5(uniqid(__CLASS__, true));
            $exists = in_array($token, $tokens);
        } while ($exists);

        return $token;
    }

    public function get_token()
    {
        return $this->token;
    }

    public function get_string()
    {
        return $this->get_token();
    }

    public function __toString()
    {
        return $this->token;
    }

    public function is_expired() {
        return ((int) strtotime('+1 minutes') > (int) $this->expiration);
    }

    public function is_valid($token) {
        return (((int) strtotime('now') <= (int) $this->expiration) && ($this->token == $token));
    }
}
