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

    private $email;

    private $firstName;

    private $lastName;

    private $hasModerationPermissions;

    private $meeting;

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

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    public function setHasModerationPermissions($hasModerationPermissions)
    {
        $this->hasModerationPermissions = $hasModerationPermissions;
    }

    public function hasModerationPermissions()
    {
        return $this->hasModerationPermissions;
    }

    public function getMeeting()
    {
        return $this->meeting;
    }

    public function setMeeting($meeting)
    {
        $this->meeting = $meeting;
    }
}
