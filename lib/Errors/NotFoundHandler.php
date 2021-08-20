<?php

namespace Meetings\Errors;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Container;

class NotFoundHandler extends ErrorHandler
{
    /**
     * Der Konstruktor...
     *
     * @param ContainerInterface $container der Dependency Container,
     *                                      der in der Slim-Applikation verwendet wird
     * @param callable           $previous  der zuvor installierte `Error
     *                                      Handler` als Fallback
     */
    public function __construct(Container $container)
    {
        parent::__construct($container);
    }

    /**
     * Diese Methode wird aufgerufen, sobald es zu einer Exception
     * kam, und generiert eine entsprechende JSON-API-spezifische Response.
     *
     * @param Request    $request   der eingehende Request
     * @param Response   $response  die vorbereitete ausgehende Response
     *
     * @throws Error/NotFoundException
     */
    public function __invoke(Request $request, Response $response)
    {
        $httpCode = 404;
        $details = null;

        $plugin_name = $this->getPluginName();

        $message = $plugin_name . ' - Slim Application Error: Request not found!';
        $details = 'The Action or Page you are looking for could not be found!';

        $meetingError = new Error($message, $httpCode, $details);
        if (!$this->displayErrorDetails()) {
            $meetingError->clearDetails();
        }
        
        $response = $this->prepareResponseMessage($request, $response, $meetingError);
        return $response->withStatus($httpCode);
    }
}
