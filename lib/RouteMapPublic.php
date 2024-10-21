<?php

namespace Meetings;

use Meetings\Providers\StudipServices;
use Psr\Container\ContainerInterface;

class RouteMapPublic
{
    public function _construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke(\Slim\Routing\RouteCollectorProxy $app)
    {
        $pp->get('/discovery', Routes\DiscoveryIndex::class);

        $app->group('', [$this, 'unauthenticatedRoutes'])
            ->add(new TrailingSlash(trailingSlash: false));
    }

    public function unauthenticatedRoutes(\Slim\Routing\RouteCollectorProxy $group)
    {
        $group->get('/slides/{meeting_id}/{slide_id}/{token}', Routes\Slides\SlidesShow::class);
        $group->get('/defaultSlide/{meeting_id}/{token}', Routes\Slides\DefaultSlideShow::class);

        //Routes for rooms in seminar
        $group->get('/course/{cid}/rooms', Routes\Rooms\RoomsList::class);
        $group->get('/course/{cid}/config', Routes\Config\ConfigListCourse::class);

        $group->get('/rooms/{room_id}', Routes\Rooms\RoomShow::class);
        $group->get('/rooms/{cid}/{room_id}/status', Routes\Rooms\RoomRunning::class);
        $group->get('/rooms/{cid}/info', Routes\Rooms\RoomInfo::class);

        //Route for joining a meeting
        $group->get('/rooms/join/{cid}/{room_id}', Routes\Rooms\RoomJoinPublic::class);

        //Routes for recordings
        $group->get('/rooms/{cid}/{room_id}/recordings', Routes\Recordings\RecordingList::class);

        //generate QR Code
        $group->get('/rooms/qr_code/{cid}/{room_id}',  Routes\Rooms\RoomGenerateQRCodePublic::class);

        //recordings with perm
        $group->get('/recordings/{cid}/{room_id}/{recordings_id}', Routes\Recordings\RecordingShow::class);

        //routes for feedback
        $group->post('/feedback', Routes\Feedback\FeedbackSubmitPublic::class);
        $group->post('/feedback/uploadTest', Routes\Feedback\UploadTest::class);
    }
}
