<?php

namespace Meetings\Errors;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Container;
use Throwable;

class PHPErrorHandler extends ErrorHandler
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
     * @param Throwable  $error der Fehler
     *
     * @return Error
     */
    public function __invoke(Request $request, Response $response, Throwable $error)
    {
        $httpCode = 500;
        $details = null;

        $plugin_name = $this->getPluginName();

        $message = $plugin_name . ' - Slim Application Internal Error' . ': ' . $error->getMessage();
        $details = 'in:' . $error->getFile() . ' line:' . $error->getLine();
        
        $meetingError = new Error($message, $httpCode, $details);
        
        if (!$this->displayErrorDetails()) {
            $meetingError->clearDetails();
        }

        $response = $this->prepareResponseMessage($request, $response, $meetingError);
        return $response->withStatus($httpCode);
    }
}
