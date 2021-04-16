<?php

namespace Meetings;

use Slim\App;
use StudipPlugin;
use Meetings\Middlewares\RemoveTrailingSlashes;

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
 * @see \Meetings\Middlewares\RemoveTrailingSlashes
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
        $app = new App();
        $app = $this->configureContainer($app, $plugin);
        $app->add(new RemoveTrailingSlashes());

        return $app;
    }

    // hier wird der Container konfiguriert
    private function configureContainer($app, $plugin)
    {
        $container = $app->getContainer();
        $container['plugin'] = $plugin;
        $container['settings']['displayErrorDetails'] = defined('\\Studip\\ENV') && \Studip\ENV === 'development' || $GLOBALS['perm']->have_perm('root');

        // error handler
        $container['errorHandler'] = function ($container) {
            return new Errors\ExceptionHandler($container);
        };

        if (isset($container['notFoundHandler'])) {
            unset($container['notFoundHandler']);
        }
        $container['notFoundHandler'] = function ($container) {
            return new Errors\NotFoundHandler($container);
        };

        if (isset($container['notAllowedHandler'])) {
            unset($container['notAllowedHandler']);
        }
        $container['notAllowedHandler'] = function ($container) {
            return new Errors\NotAllowedHandler($container);
        };

        if (isset($container['phpErrorHandler'])) {
            unset($container['phpErrorHandler']);
        }
        $container['phpErrorHandler'] = function ($container) {
            return new Errors\PHPErrorHandler($container);
        };

        $container->register(new Providers\StudipConfig());
        $container->register(new Providers\StudipServices());
        $container->register(new Providers\PluginRoles());

        return $app;
    }
}
