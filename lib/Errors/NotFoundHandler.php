<?php

namespace Meetings\Errors;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Container;

class NotFoundHandler extends ErrorHandler
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
     * @throws Error/NotFoundException
     */
    public function __invoke(Request $request, Response $response)
    {
        $errors = new ErrorCollection();
        $httpCode = 404;
        $details = null;

        $plugin_name = 'Meeting Plugin';
        if ($this->container['plugin']) {
            $plugin_name = $this->container['plugin']->getPluginName();
        }

        $message = _($plugin_name . ' - Slim Application Error: Request not found!');

        if ($this->container['settings']['displayErrorDetails']) {
            $details = 'The Action or Page you are looking for could not be found!';
        }

        $errors->add(new Error($message, $httpCode, $details));

        if (!empty($errors)) {
            $response = $this->prepareResponseMessage($request, $response, $errors);
        }

        return $response->withStatus($httpCode);
    }
}
