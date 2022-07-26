<?php

namespace Meetings;

use Meetings\Providers\StudipServices;

class RouteMapPublic
{
    public function __construct(\Slim\App $app)
    {
        $this->app = $app;
    }

    public function __invoke()
    {
        $this->app->get('/discovery', Routes\DiscoveryIndex::class);

        $this->app->group('', [$this, 'unauthenticatedRoutes'])
            ->add(new Middlewares\RemoveTrailingSlashes);
    }

    public function unauthenticatedRoutes()
    {
        $this->app->get('/slides/{meeting_id}/{slide_id}/{token}', Routes\Slides\SlidesShow::class);
        $this->app->get('/defaultSlide/{meeting_id}/{token}', Routes\Slides\DefaultSlideShow::class);

        //Routes for rooms in seminar
        $this->app->get('/course/{cid}/rooms', Routes\Rooms\RoomsList::class);
        $this->app->get('/course/{cid}/config', Routes\Config\ConfigListCourse::class);

        $this->app->get('/rooms/{room_id}', Routes\Rooms\RoomShow::class);
        $this->app->get('/rooms/{cid}/{room_id}/status', Routes\Rooms\RoomRunning::class);
        $this->app->get('/rooms/{cid}/info', Routes\Rooms\RoomInfo::class);

        //Route for joining a meeting
        $this->app->get('/rooms/join/{cid}/{room_id}', Routes\Rooms\RoomJoinPublic::class);

        //Routes for recordings
        $this->app->get('/rooms/{cid}/{room_id}/recordings', Routes\Recordings\RecordingList::class);

        //generate QR Code
        $this->app->get('/rooms/qr_code/{cid}/{room_id}',  Routes\Rooms\RoomGenerateQRCodePublic::class);

        //recordings with perm
        $this->app->get('/recordings/{cid}/{room_id}/{recordings_id}', Routes\Recordings\RecordingShow::class);

        //routes for feedback
        $this->app->post('/feedback', Routes\Feedback\FeedbackSubmitPublic::class);
        $this->app->post('/feedback/uploadTest', Routes\Feedback\UploadTest::class);
    }
}
