<?php

namespace Meetings\Providers;

class PluginRoles
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
        $container->set('roles', [
            'admin' => 'Meetings_Admin'
        ]);
    }
}
