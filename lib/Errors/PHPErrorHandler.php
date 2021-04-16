<?php

namespace Meetings\Errors;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Container;
use Throwable;

class PHPErrorHandler extends Error
{
    private $container;

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
        $this->container = $container;
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
        $message = "(Meeting Plugin) Slim Application Internal Error";
        if ($this->container['settings']['displayErrorDetails']) {
            $message .= ': ' . $error->getMessage() . ' in:' . $error->getFile() . ' line:' . $error->getLine();
        }
        throw new Error($message, 500);
    }
}
