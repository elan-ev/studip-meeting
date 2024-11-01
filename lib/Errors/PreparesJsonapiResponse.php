<?php

namespace Meetings\Errors;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

trait PreparesJsonapiResponse
{
    /**
     * Check the accept type of the request and decide the response.
     * In case that the accept type includes "application/json",
     * it means that it needs to json response format,
     * otherwise we throw an Error to display.
     */
    private function prepareResponseMessage(Request $request, Response $response, Error $error)
    {
        $accepts = $request->getHeaderLine('accept');
        $accepts = array_map('trim', explode(',', $accepts));

        if (is_array($accepts) && !in_array('application/json', $accepts)) {
            throw new Error($error->getDetailedMessage(), $error->getCode(), $error->getDetails());
        }

        $response->getBody()->write($error->getJson());

        return $response->withHeader('Content-Type', 'application/vnd.api+json');
    }
}
