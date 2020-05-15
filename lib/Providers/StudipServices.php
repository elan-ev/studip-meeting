<?php

namespace Meetings\Providers;

use StudipAuthAbstract;
use User;

/**
 * Diese Klasse stellt Stud.IP-Spezifika zum Beispiel für die
 * Authentifizierung zur Verfügung.
 */
class StudipServices implements \Pimple\ServiceProviderInterface
{
    /**
     * Schlüssel für den Stud.IP-Authentifizierungservice.
     */
    const AUTHENTICATOR = 'studip-authenticator';

    /**
     * Diese Methode wird automatisch aufgerufen, wenn diese Klasse dem
     * Dependency Container der Slim-Applikation hinzugefügt wird.
     *
     * Hier werden die Stud.IP-Spezifika gesetzt
     *
     * @param \Pimple\Container $container der Dependency Container
     */
    public function register(\Pimple\Container $container)
    {
        $container[self::AUTHENTICATOR] = function ($c) {
            return function ($username, $password) {
                $check = StudipAuthAbstract::CheckAuthentication($username, $password);

                if ($check['uid'] && $check['uid'] != 'nobody') {
                    return User::find($check['uid']);
                }

                return null;
            };
        };
    }
}
