<?php

namespace Meetings\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

class Authentication
{
    // der Schlüssel des Request-Attributs, in dem der Stud.IP-Nutzer
    // gefunden werden kann:

    // $user = $request->getAttribute(Authentication::USER_KEY);
    const USER_KEY = 'studip-user';

    // a callable accepting two arguments username and password and
    // returning either null or a Stud.IP user object
    private $authenticator;

    /**
     * Der Konstruktor.
     *
     * @param callable $authenticator ein Callable, das den
     *                                Nutzernamen und das Passwort als Argumente erhält und damit
     *                                entweder einen Stud.IP-User-Objekt oder null zurückgibt
     */
    public function __construct($authenticator)
    {
        $this->authenticator = $authenticator;
    }

    /**
     * Hier muss die Autorisierung implementiert werden.
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
        $guards = [
            new Auth\SessionStrategy(),
            new Auth\HttpBasicAuthStrategy($request, $this->authenticator)
        ];

        foreach ($guards as $guard) {
            if ($guard->check()) {
                $request = $this->provideUser($request, $guard->user());

                return $handler->handle($request);
            }
        }

        return $this->generateChallenges($request, $guards);
    }

    // according to RFC 2616
    private function generateChallenges(ServerRequestInterface $request, array $guards)
    {
        $response = new Response();
        $response = $response->withStatus(401);

        foreach ($guards as $guard) {
            $response = $guard->addChallenge($response);
        }

        return $response;
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    private function provideUser(ServerRequestInterface $request, \User $user)
    {
        if ('nobody' === $GLOBALS['user']->id) {
            $GLOBALS['user'] = new \Seminar_User($user->id);
            $GLOBALS['auth'] = new \Seminar_Auth();
            $GLOBALS['auth']->auth = [
                'uid' => $user->id,
                'uname' => $user->username,
                'perm' => $user->perms,
            ];
            $GLOBALS['perm'] = new \Seminar_Perm();
            $GLOBALS['MAIL_VALIDATE_BOX'] = false;
            $GLOBALS['sess']->delete();
            setTempLanguage($user->id);
        }

        return $request->withAttribute(self::USER_KEY, $user);
    }
}
