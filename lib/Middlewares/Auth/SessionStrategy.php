<?php

namespace Meetings\Middlewares\Auth;

use Psr\Http\Message\ResponseInterface as Response;

class SessionStrategy implements Strategy
{
    protected $user;

    public function check()
    {
        return !is_null($this->user());
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function user()
    {
        if (!is_null($this->user)) {
            return $this->user;
        }

        $isAuthenticated = isset($GLOBALS['auth']) && $GLOBALS['auth']->is_authenticated() && 'nobody' !== $GLOBALS['user']->id;

        if ($isAuthenticated) {
            $this->user = $GLOBALS['user']->getAuthenticatedUser();
        }

        return $this->user;
    }

    public function addChallenge(Response $response)
    {
        return $response;
    }
}
