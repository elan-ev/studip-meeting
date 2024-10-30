<?php

namespace Meetings\Errors;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use StudipPlugin;
use Throwable;

class NotAllowedHandler
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
        $message = $this->plugin->getPluginName() . ' - Slim Application Error: Method not Allowed!';
        $details = '';

        return $this->prepareResponseMessage(
            $request,
            app(ResponseFactoryInterface::class)->createResponse(405),
            new Error($message, 405, $details)
        );
    }
}
