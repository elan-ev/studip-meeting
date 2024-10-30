<?php

namespace Meetings;

class RouteMap
{
    public function __construct()
    {
    }

    public function __invoke(\Slim\Routing\RouteCollectorProxy $app)
    {
        $app->group('', [$this, 'authenticatedRoutes'])
            ->add(Middlewares\Authentication::class);

        $app->group('', [$this, 'adminRoutes'])
            ->add(Middlewares\AdminPerms::class)
            ->add(Middlewares\Authentication::class);

        $app->get('/discovery', Routes\DiscoveryIndex::class);

        $app->group('', [$this, 'unauthenticatedRoutes']);
    }

    public function authenticatedRoutes(\Slim\Routing\RouteCollectorProxy $group)
    {
        $group->get('/user', Routes\Users\UsersShow::class);

        //Routes for rooms in seminar
        $group->get('/course/{cid}/rooms', Routes\Rooms\RoomsList::class);
        $group->get('/course/{cid}/config', Routes\Config\ConfigListCourse::class);

        $group->get('/rooms/{room_id}', Routes\Rooms\RoomShow::class);
        $group->get('/rooms/{cid}/{room_id}/status', Routes\Rooms\RoomRunning::class);
        $group->get('/rooms/{cid}/info', Routes\Rooms\RoomInfo::class);

        //Route for joining a meeting
        $group->get('/rooms/join/{cid}/{room_id}', Routes\Rooms\RoomJoin::class);

        //Routes for recordings
        $group->get('/rooms/{cid}/{room_id}/recordings', Routes\Recordings\RecordingList::class);

        //following requests contain validation of permissions
        // rooms with perm
        $group->post('/rooms', Routes\Rooms\RoomAdd::class);
        $group->put('/rooms/{room_id}', Routes\Rooms\RoomEdit::class);
        $group->delete('/rooms/{cid}/{room_id}', Routes\Rooms\RoomDelete::class);

        //generate guest invitation link
        $group->get('/rooms/join/{cid}/{room_id}/{guest_name}/guest', Routes\Rooms\RoomJoinGuest::class);
        $group->get('/rooms/invitationLink/{cid}/{room_id}', Routes\Rooms\RoomInvitationLink::class);

        //generate moderator invitaion link
        $group->get(
            '/rooms/join/{cid}/{room_id}/{moderator_password}/moderator',
            Routes\Rooms\RoomModeratorInvitationLinkCreate::class
        );
        $group->get('/rooms/inviteModerator/{cid}/{room_id}', Routes\Rooms\RoomModeratorInvitationLinkGet::class);

        //generate QR Code
        $group->get('/rooms/qr_code/{cid}/{room_id}', Routes\Rooms\RoomGenerateQRCode::class);

        //recordings with perm
        $group->get('/recordings/{cid}/{room_id}/{recordings_id}', Routes\Recordings\RecordingShow::class);
        $group->delete('/recordings/{cid}/{room_id}/{recordings_id}', Routes\Recordings\RecordingDelete::class);

        //routes for feedback
        $group->post('/feedback', Routes\Feedback\FeedbackSubmit::class);
        $group->post('/feedback/uploadTest', Routes\Feedback\UploadTest::class);

        //routes for folders
        $group->get('/folders/{cid}/{folder_id}', Routes\Folder\FolderList::class);
        $group->post('/folders/new_folder', Routes\Folder\FolderCreate::class);
        $group->post('/folders/upload_file', Routes\Folder\FolderUploadFile::class);
    }

    public function adminRoutes(\Slim\Routing\RouteCollectorProxy $group)
    {
        //configs
        $group->get('/config/list', Routes\Config\ConfigList::class);
        $group->get('/config/{id}', Routes\Config\ConfigShow::class);
        $group->post('/config', Routes\Config\ConfigAdd::class);
        $group->put('/config/{id}', Routes\Config\ConfigEdit::class);
        $group->delete('/config/{id}', Routes\Config\ConfigDelete::class);

        //default slide management
        $group->get('/default_slide/font', Routes\Slides\FontRead::class);
        $group->post('/default_slide/font', Routes\Slides\FontUpload::class);
        $group->delete('/default_slide/font/{font_type}', Routes\Slides\FontDelete::class);

        $group->get('/default_slide/template', Routes\Slides\TemplateRead::class);
        $group->post('/default_slide/template', Routes\Slides\TemplateUpload::class);
        $group->delete('/default_slide/template/{page}/{what}', Routes\Slides\TemplateDelete::class);

        $group->get('/default_slide/template/preview/{page}', Routes\Slides\TemplatePreview::class);
        $group->get('/default_slide/template/sample/{what}', Routes\Slides\TemplateSampleDownload::class);
    }

    public function unauthenticatedRoutes(\Slim\Routing\RouteCollectorProxy $group)
    {
        $group->get('/slides/{meeting_id}/{slide_id}/{token}', Routes\Slides\SlidesShow::class);
        $group->get('/defaultSlide/{meeting_id}/{token}', Routes\Slides\DefaultSlideShow::class);
    }
}
