<?php

namespace Meetings\Middlewares;

use DI\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RegisterServiceProviders implements MiddlewareInterface
{
    public function __construct(protected Container $container)
    {
    }

    /**
     * Handle the incoming request.
     *
     * @SuppressWarnings(Superglobals)
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->container->set(
            \PluginDispatcher::class,
            \DI\decorate(function ($dispatcher) {
                $dispatcher->trails_root = realpath(__DIR__ . '/../../app');
                return $dispatcher;
            })
        );

        $this->container->set(
            'roles',
            ['admin' => 'Meetings_Admin'],
        );

        $this->container->set(
            'studip-current-user',
            \DI\factory([\User::class, 'findCurrent']),
        );

        $this->container->set(
            \Meetings\Middlewares\Authentication::class,
            function () {
                return new \Meetings\Middlewares\Authentication(function ($username, $password) {
                    $check = \StudipAuthAbstract::CheckAuthentication($username, $password);

                    if ($check['uid'] && $check['uid'] != 'nobody') {
                        return \User::find($check['uid']);
                    }

                    return null;
                });
            },
        );

        return $handler->handle($request);
    }
}
