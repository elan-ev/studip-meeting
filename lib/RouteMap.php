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

        //Routes for rooms in seminar
        $this->app->get('/course/{cid}/rooms', Routes\Rooms\RoomsList::class);
        $this->app->get('/rooms/{room_id}', Routes\Rooms\RoomShow::class);
        $this->app->get('/rooms/{cid}/{room_id}/status', Routes\Rooms\RoomRunning::class);
        $this->app->post('/rooms', Routes\Rooms\RoomAdd::class);
        $this->app->put('/rooms/{room_id}', Routes\Rooms\RoomEdit::class);
        $this->app->delete('/rooms/{cid}/{room_id}', Routes\Rooms\RoomDelete::class);

        //Route for joining a meeting
        $this->app->get('/rooms/join/{cid}/{room_id}', Routes\Rooms\RoomJoin::class);

        //Routes for recordings
        $this->app->get('/rooms/{cid}/{room_id}/recordings', Routes\Recordings\RecordingList::class);
        $this->app->get('/recordings/{recordings_id}', Routes\Recordings\RecordingShow::class);
        $this->app->delete('/recordings/{cid}/{room_id}/{recordings_id}', Routes\Recordings\RecordingDelete::class);
    }

    public function adminRoutes()
    {
    }
}
