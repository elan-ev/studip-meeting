<?php

namespace Meetings\Errors;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use StudipPlugin;
use Throwable;

class DefaultErrorHandler
{
    use PreparesJsonapiResponse;

    public function __construct(private StudipPlugin $plugin)
    {
    }

    /**
     * Diese Methode wird aufgerufen, sobald es zu einer Exception
     * kam, und generiert eine entsprechende JSON-API-spezifische Response.
     */
    public function __invoke(ServerRequestInterface $request, Throwable $exception, bool $displayErrorDetails)
    {
        $code = $exception->getCode() ?: 500;
        return $this->prepareResponseMessage(
            $request,
            app(ResponseFactoryInterface::class)->createResponse($code),
            new Error($exception->getMessage(), $code, '')
        );
    }
}
