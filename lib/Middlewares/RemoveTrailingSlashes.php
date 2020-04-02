<?php

namespace Meetings\Middlewares;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Diese Klasse definiert eine Middleware, die Requests  umleitet,
 * die mit einem Schrägstrich enden (und zwar jeweils auf das Pendant
 * ohne Schrägstrich).
 */
class RemoveTrailingSlashes
{
    /**
     * Diese Middleware überprüft den Pfad der URI des Requests. Endet
     * diese auf einem Schrägstrich, wird nicht weiter an `$next`
     * delegiert, sondern eine Response mit `Location`-Header also
     * einem Redirect zurückgegeben.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request  das
     *                                                           PSR-7 Request-Objekt
     * @param \Psr\Http\Message\ResponseInterface      $response das PSR-7
     *                                                           Response-Objekt
     * @param callable                                 $next     das nächste Middleware-Callable
     *
     * @return \Psr\Http\Message\ResponseInterface die neue Response
     */
    public function __invoke(Request $request, Response $response, $next)
    {
        $uri = $request->getUri();
        $path = $uri->getPath();
        if ($path != '/' && substr($path, -1) == '/') {
            // permanently redirect paths with a trailing slash
            // to their non-trailing counterpart
            $uri = $uri->withPath(substr($path, 0, -1));

            return $response->withRedirect((string) $uri, 301);
        }

        return $next($request, $response);
    }
}
