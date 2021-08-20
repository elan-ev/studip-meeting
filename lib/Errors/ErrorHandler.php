<?php

namespace Meetings\Errors;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Container;

class ErrorHandler
{
    public $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Check the accept type of the request and decide the response.
     * In case that the accept type includes "application/json", 
     * it means that it needs to json response format,
     * otherwise we throw an Error to display.
     *
     * @param Request $request
     * @param Response $response
     * @param Error $error
     * 
     * @return Response response
     * @throws Error
     */
    public function prepareResponseMessage(Request $request, Response $response, Error $error)
    {
        $accepts = $request->getHeaderLine('accept');
        $accepts = array_map('trim', explode(',', $accepts));

        if (is_array($accepts) && !in_array('application/json', $accepts)) {
            throw new Error($error->getDetailedMessage(), $error->getCode(), $error->getDetails());
        }
        
        return $response
                ->withHeader(
                    'Content-Type',
                    'application/vnd.api+json'
                )
                ->write($error->getJson());

    }

    /**
     * Creates and returns plugin name.
     *
     * @return string
     */
    public function getPluginName()
    {
        $plugin_name = 'Meeting Plugin';
        if ($this->container['plugin']) {
            $plugin_name = $this->container['plugin']->getPluginName();
        }
        return $plugin_name;
    }

    /**
     * Get display error details value from container to decide whether to show the details or not!
     *
     * @return boolean
     */
    public function displayErrorDetails() 
    {
        return $this->container['settings']['displayErrorDetails'];
    }
}
