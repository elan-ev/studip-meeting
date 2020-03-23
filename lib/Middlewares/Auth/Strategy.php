<?php

namespace Meetings\Middlewares\Auth;

use Psr\Http\Message\ResponseInterface as Response;

interface Strategy
{
    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check();

    /**
     * Get the currently authenticated user.
     *
     * @return \User|null
     */
    public function user();

    /**
     * Validate a user's credentials.
     *
     * @param array $credentials
     *
     * @return bool
     */
    public function addChallenge(Response $response);
}
