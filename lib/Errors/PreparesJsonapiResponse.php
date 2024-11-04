<?php

namespace Meetings\Errors;

use Psr\Http\Message\ResponseInterface as Response;

trait PreparesJsonapiResponse
{
    private function prepareResponseMessage(Response $response, Error $error)
    {
        $response->getBody()->write($error->getJson($this->displayErrorDetails));

        return $response->withHeader('Content-Type', 'application/vnd.api+json');
    }
}
