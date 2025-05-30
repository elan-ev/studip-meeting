<?php

namespace Meetings\Middlewares;

use Meetings\Errors\Error;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use StudIPPlugin;
use Throwable;

class ErrorMiddleware implements MiddlewareInterface
{
    public function __construct(private StudIPPlugin $plugin)
    {
    }

    /**
     * Handle the incoming request.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $exception) {
            return $this->handleError($request, $exception);
        }
    }

    /**
     * @SuppressWarnings(Superglobals)
     */
    public function handleError(
        ServerRequestInterface $request,
        Throwable $exception,
    ): ResponseInterface {
        $messageFormat = $this->plugin->getPluginName() . ' - Slim Application Error: %s';

        $accepts = $request->getHeaderLine('accept');
        $accepts = array_map('trim', explode(',', $accepts));

        $isCatchable = $exception->getCode() >= 400 && $exception->getCode() < 600;
        $isAccepted = is_array($accepts) && in_array('application/json', $accepts);

        $details = $exception->getTrace() ?? null;
        $displayErrorDetails = (defined('\\Studip\\ENV') && \Studip\ENV === 'development') || $GLOBALS['perm']->have_perm('root');

        if ($isCatchable && $isAccepted) {
            return $this->prepareResponseMessage(
                app(ResponseFactoryInterface::class)->createResponse($exception->getCode()),
                new Error(sprintf($messageFormat, $exception->getMessage()), $exception->getCode(), $details),

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
