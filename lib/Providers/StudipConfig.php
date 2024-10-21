<?php

namespace Meetings\Providers;

class StudipConfig
{
    /**
     * Diese Methode wird automatisch aufgerufen, wenn diese Klasse dem
     * Dependency Container der Slim-Applikation hinzugefügt wird.
     *
     * @param \Pimple\Container $container der Dependency Container
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function __construct(\DI\Container $container)
    {
        $container->set('studip-current-user', function () {
            if ($user = $GLOBALS['user']) {
                return $user->getAuthenticatedUser();
            }

            return null;
        });
    }
}
