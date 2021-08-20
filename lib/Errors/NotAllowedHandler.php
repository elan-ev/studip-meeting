<?php

namespace Meetings\Errors;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\MethodNotAllowedException;
use Slim\Container;

class NotAllowedHandler extends ErrorHandler
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
     * @return Error/NotFoundException
     */
    public function __invoke(Request $request, Response $response, $methods = ['GET', 'POST', 'PUT', 'DELETE'])
    {
        $httpCode = 405;
        $details = null;

        $plugin_name = $this->getPluginName();

        $message = $plugin_name . ' - Slim Application Error: Method Not Allowed!';
        $details = 'Allowed methods: ' . implode(', ', $methods);

        $meetingError = new Error($message, $httpCode, $details);
        if (!$this->displayErrorDetails()) {
            $meetingError->clearDetails();
        }
        
        $response = $this->prepareResponseMessage($request, $response, $meetingError);
        return $response->withStatus($httpCode);
    }
}
