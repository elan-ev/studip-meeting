<?php

namespace Meetings;

use Meetings\Providers\StudipServices;

class RouteMap
{
    public function __construct(\Slim\App $app)
    {
        $this->app = $app;
    }

    public function __invoke()
    {
        $container = $this->app->getContainer();

        $this->app->group('', [$this, 'authenticatedRoutes'])
            ->add(new Middlewares\Authentication($container[StudipServices::AUTHENTICATOR]))
            ->add(new Middlewares\RemoveTrailingSlashes);

        $this->app->group('', [$this, 'adminRoutes'])
            ->add(new Middlewares\AdminPerms($container))
            ->add(new Middlewares\Authentication($container[StudipServices::AUTHENTICATOR]))
            ->add(new Middlewares\RemoveTrailingSlashes);

        $this->app->get('/discovery', Routes\DiscoveryIndex::class);
    }

    public function authenticatedRoutes()
    {
        $this->app->get('/user', Routes\Users\UsersShow::class);

        $this->app->get('/config', Routes\Config\ConfigList::class);
        $this->app->get('/config/{id}', Routes\Config\ConfigShow::class);
        $this->app->post('/config', Routes\Config\ConfigAdd::class);
        $this->app->post('/config/check', Routes\Config\ConfigCheck::class);
        $this->app->put('/config/{id}', Routes\Config\ConfigEdit::class);
        $this->app->delete('/config/{id}', Routes\Config\ConfigDelete::class);
    }

    public function adminRoutes()
    {
    }
}
