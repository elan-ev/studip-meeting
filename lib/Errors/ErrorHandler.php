<?php

namespace Meetings\Errors;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ErrorHandler
{
    public function prepareResponseMessage(Request $request, Response $response, ErrorCollection $errors)
    {
        $accepts = $request->getHeaderLine('accept');
        $accepts = array_map('trim', explode(',', $accepts));

        if (is_array($accepts) && !in_array('application/json', $accepts)) {
            return $response
                ->withHeader(
                    'Content-Type',
                    'text/html'
                )
                ->write($errors->text_html());
        }
        
        return $response
                ->withHeader(
                    'Content-Type',
                    'application/vnd.api+json'
                )
                ->write($errors->json());

    }
}
