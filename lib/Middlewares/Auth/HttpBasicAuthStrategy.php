<?php

namespace Meetings\Middlewares\Auth;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class HttpBasicAuthStrategy implements Strategy
{
    protected $user;

    protected $request;

    public function __construct(Request $request, $authenticator)
    {
        $this->request = $request;
        $this->authenticator = $authenticator;
    }

    public function check()
    {
        return !is_null($this->user());
    }

    public function user()
    {
        if (!is_null($this->user)) {
            return $this->user;
        }

        $serverParams = $this->request->getServerParams();

        if (isset($serverParams['PHP_AUTH_USER'], $serverParams['PHP_AUTH_PW'])) {
            $authenticator = $this->authenticator;
            $this->user = $authenticator($serverParams['PHP_AUTH_USER'], $serverParams['PHP_AUTH_PW']);
        }

        return $this->user;
    }

    public function addChallenge(Response $response)
    {
        return $response->withHeader('WWW-Authenticate', sprintf('Basic realm="%s"', 'Stud.IP JSON-API'));
    }
}
