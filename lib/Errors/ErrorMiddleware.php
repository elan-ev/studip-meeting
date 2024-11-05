<?php

namespace Meetings\Errors;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use StudipPlugin;
use Throwable;

class ErrorMiddleware
{
    public function __construct(private StudipPlugin $plugin)
    {
    }

    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ): ResponseInterface {
        $message_format = $this->plugin->getPluginName() . ' - Slim Application Error: %s';

        $accepts = $request->getHeaderLine('accept');
        $accepts = array_map('trim', explode(',', $accepts));

        $is_catchable = $exception->getCode() >= 400 && $exception->getCode() < 600;
        $is_accepted = is_array($accepts) && in_array('application/json', $accepts);

        if ($is_catchable && $is_accepted) {
            return $this->prepareResponseMessage(
                app(ResponseFactoryInterface::class)->createResponse($exception->getCode()),
                new Error(sprintf($message_format, $exception->getMessage()), $exception->getCode()),
                $displayErrorDetails
            );
        }

        throw $exception;
    }

    private function prepareResponseMessage(ResponseInterface $response, Error $error, $displayErrorDetails)
    {
        $response->getBody()->write($error->getJson($displayErrorDetails));

        return $response->withHeader('Content-Type', 'application/vnd.api+json');
    }
}
