<?php

namespace Meetings;

use Slim\App;
use StudipPlugin;
use Slim\Factory\AppFactory as SlimApp;

/**
 * Diese Klasse erstellt eine neue Slim-Applikation und konfiguriert
 * diese rudimentär vor.
 *
 * Dabei werden im `Dependency Container` der Slim-Applikation unter
 * dem Schlüssel `plugin` das Stud.IP-Plugin vermerkt und außerdem
 * eingestellt, dass Fehler des Slim-Frameworks detailliert angezeigt
 * werden sollen, wenn sich Stud.IP im Modus `development` befindet.
 *
 * Darüber hinaus wird eine Middleware installiert, die alle Requests umleitet,
 * die mit einem Schrägstrich enden (und zwar jeweils auf das Pendant
 * ohne Schrägstrich).
 *
 * @see http://www.slimframework.com/
 * @see \Studip\ENV
 */
class AppFactory
{
    /**
     * Diese Factory-Methode erstellt die Slim-Applikation und
     * konfiguriert diese wie oben angegeben.
     *
     * @param \StudipPlugin $plugin das Plugin, für die die
     *                              Slim-Applikation erstellt werden soll
     *
     * @return \Slim\App die erstellte Slim-Applikation
     */
    public function makeApp(StudipPlugin $plugin)
    {
        SlimApp::setContainer($this->getContainer($plugin));
        $app = SlimApp::create();

        return $app;
    }

    // hier wird der Container konfiguriert
    private function getContainer($plugin)
    {
        $container = new \DI\Container();
        $container->set('plugin', $plugin);
        $container->set('settings', [
            'displayErrorDetails' => defined('\\Studip\\ENV')
                && \Studip\ENV === 'development'
                || $GLOBALS['perm']->have_perm('root')
        ]);

        // error handler
        $container->set('errorHandler', function ($container) {
            return new Errors\ExceptionHandler($container);
        });

        $container->set('notFoundHandler', function ($container) {
            return new Errors\NotFoundHandler($container);
        });

        $container->set('notAllowedHandler', function ($container) {
            return new Errors\NotAllowedHandler($container);
        });

         $container->set('phpErrorHandler', function ($container) {
            return new Errors\PHPErrorHandler($container);
        });

        new Providers\StudipConfig($container);
        new Providers\StudipServices($container);
        new Providers\PluginRoles($container);

        return $container;
    }
}
