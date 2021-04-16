<?php

namespace Meetings\Errors;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\MethodNotAllowedException;
use Slim\Container;

class NotAllowedHandler extends Error
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
     *
     * @return Error/NotFoundException
     */
    public function __invoke(Request $request, Response $response, $methods = ['GET', 'POST', 'PUT', 'DELETE'])
    {
        if ($this->container['settings']['displayErrorDetails']) {
            throw new MethodNotAllowedException($request, $response, $methods);
        } else {
            throw new Error('(Meeting Plugin) Slim Application Error: Method Not Allowed!', 405);
        }
    }
}
