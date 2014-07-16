<?php

namespace ElanEv\Driver;

/**
 * Parameters needed to join a meeting.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class JoinParameters extends Parameters
{
    /**
     * @var string Name of the user joining the meeting
     */
    private $username;

    /**
     * @var string Password needed to join the meeting
     */
    private $password;

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function getPassword()
    {
        return $this->password;
    }
}
