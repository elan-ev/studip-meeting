<?php

namespace Meetings\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Meetings\Errors\Error;

class AdminPerms
{
    /**
     * Checks, if the current user has the admin role
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request  das
     *                                                           PSR-7 Request-Objekt
     * @param \Psr\Http\Message\ResponseInterface      $response das PSR-7
     *                                                           Response-Objekt
     * @param callable                                 $next     das nächste Middleware-Callable
     *
     * @return \Psr\Http\Message\ResponseInterface das neue Response-Objekt
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler)
    {
        if (!$GLOBALS['perm']->have_perm('root')) {
            throw new Error('Access Denied', 403);
        }

        return $handler->handle($request);
    }
}
