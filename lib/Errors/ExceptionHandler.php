<?php

namespace Meetings\Errors;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Container;

/**
 * Dieser spezielle Exception Handler wird in der Slim-Applikation
 * für alle JSON-API-Routen installiert und sorgt dafür, dass auch
 * evtl. Fehler JSON-API-kompatibel geliefert werden.
 */
class ExceptionHandler
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
     * @param \Exception $exception die aufgetretene Exception
     *
     * @return Response die JSON-API-kompatible Response
     */
    public function __invoke(Request $request, Response $response, \Exception $exception)
    {
        if ($exception instanceof Error) {
            $httpCode = $exception->getCode();
            $errors = new ErrorCollection();

            if (!$this->container['settings']['displayErrorDetails']) {
                $exception->clearDetails();
            }

            $errors->add($exception);
        } else {
            $httpCode = 500;
            $details = null;

            $message = $exception->getMessage();

            if ($this->container['settings']['displayErrorDetails']) {
                $details = (string) $exception;
            }

            $errors = new ErrorCollection();
            $errors->add(new Error($message, $httpCode, $details));
        }

        if (!empty($errors)) {
            $response = $response
                      ->withHeader(
                          'Content-Type',
                          'application/vnd.api+json'
                      )
                      ->write($errors->json());
        }

        return $response->withStatus($httpCode);
    }
}
