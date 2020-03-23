<?php

namespace Meetings\Providers;

class MeetingsConstants implements \Pimple\ServiceProviderInterface
{
    /**
     * Diese Methode wird automatisch aufgerufen, wenn diese Klasse dem
     * Dependency Container der Slim-Applikation hinzugefügt wird.
     *
     * @param \Pimple\Container $container der Dependency Container
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function register(\Pimple\Container $container)
    {
        $container['Meetings'] = [
            'services' => [
                'acl-manager',          // alles admin-node
                'archive',
                'apievents',
                'apiseries',
                'apiworkflows',
                'capture-admin',
                'ingest',
                'recordings',
                'search',               // ausser hier: engage-node
                'series',
                'services',
                'upload',
                'workflow'
            ]
        ];
    }
}
