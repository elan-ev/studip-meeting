<?php

namespace ElanEv\Driver;

use MeetingPlugin;
use GuzzleHttp\ClientInterface;
use ElanEv\Model\Meeting;
use ElanEv\Model\Driver;
use Throwable;

/**
 * Teams driver implementation.
 *
 * @author Adrian Lukas Stein <adrianlukasstein@gmail.com>
 */
class MicrosoftTeams implements DriverInterface
{
    /**
     * @var \GuzzleHttp\ClientInterface The HTTP client
     */
    private
        $client,
        $accesToken;

    public function __construct(ClientInterface $client, array $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    /**
     * Login and return the access token for the MS Graph API
     *
     * @return string the access token
     */
    private function getAccessToken()
    {
        $data = [
            'client_id'     => $this->config['client_id'],
            'resource'         => 'https://graph.microsoft.com',
            'client_secret' => $this->config['client_secret'],
            'grant_type'    => 'client_credentials'
        ];

        $url = 'https://login.microsoftonline.com/'
            . $this->config['tenant_id'] . '/oauth2/token?api-version=1.0';
        $token = json_decode($this->client->post($url, [
            'form_params' => $data
        ])->getBody()->getContents());

        return $token->access_token;
    }

    private function createGroup($meetingName)
    {
        $data = [
            'description'      => 'A group Created by the external studip to Microsoft Teams connection',
            'displayName'      => $meetingName,
            'groupTypes'       => [
                'Unified'
            ],
            'mailEnabled'     => true,
            'mailNickname'    => $meetingName,
            'securityEnabled' => false,
            'visibility'      => 'Private'
        ];

        $result = $this->client->request('POST',
            'https://graph.microsoft.com/v1.0/groups',
            [
                'json' => $data,
                'headers'     => [
                    'Authorization' => 'Bearer ' . $this->accessToken
                ]
            ]
        )->getBody()->getContents();

        $groupData = json_decode($result);

        return $groupData->id;
    }

    private function createTeamsOfGroup($groupId)
    {
        $data = [
            'memberSettings' => [
                'allowCreatePrivateChannels' => true,
                'allowCreateUpdateChannels'  => true
            ],
            'messagingSettings' => [
                'allowUserEditMessages'   => true,
                'allowUserDeleteMessages' => true
            ],
            'funSettings' => [
                'allowGiphy' => true,
                'giphyContentRating' => 'strict'
            ]
        ];

        $result = $this->client->request('PUT',
            'https://graph.microsoft.com/v1.0/groups/' . $groupId . '/team',
            [
                'json' => $data,
                'headers'     => [
                    'Authorization' => 'Bearer ' . $this->accessToken
                ]
            ]
        )->getBody()->getContents();

        $teamData = json_decode($result);

        return $teamData->webUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function createMeeting(MeetingParameters &$parameters)
    {
        $meetingName = $parameters->getMeetingName();
        $meetingId =  $parameters->getMeetingId();

        // these are the only allowed characters for the mailNickname (which has to be unique)
        $meetingName = preg_replace('/[^a-zA-Z0-9-]/', '', $meetingName);
        $meetingName = substr($meetingName, 0, 30)
            . '_' . date('Y-m-d_H.i.s');

        $this->accessToken = $this->getAccessToken();

        $groupId = $this->createGroup($meetingName);

        $features = json_decode($parameters->getMeetingFeatures(), true);

        //var_dump($webUrl);

        $features['teams'] = [
            'groupId' => $groupId,
            'name'    => $meetingName,
            'webUrl'  => ''
        ];

        $parameters->setMeetingFeatures(json_encode($features));

        // exec("cd /git/studip-teams-node/ && node createMeeting.js " . $meetingId . " \"" . $meetingName . "\" > /dev/null &", $output);

        return true;

    }

    /**
     * {@inheritdoc}
     */
    public function deleteMeeting(MeetingParameters $parameters)
    {

        //TODO: Launch script to delete Meeting

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getJoinMeetingUrl(JoinParameters $parameters)
    {
        $meetingId = $parameters->getMeetingId();
        $userId = $parameters->getEmail();
        $userMail = $parameters->getEmail();

        $this->accessToken = $this->getAccessToken();

        // check, if webUrl is present
        $meeting = $parameters->getMeeting();
        $features = json_decode($meeting->features, true);

        if (!$features['teams']['webUrl']) {
            $features['teams']['webUrl'] = $this->createTeamsOfGroup(
                $features['teams']['groupId']
            );

            $meeting->features = json_encode($features);
            $meeting->store();
        }

        var_dump($features);die;

        // if a room has already been created it returns true otherwise it creates the room
        /*$meeting = new Meeting($parameters->getMeetingId());
        $meetingParameters = $meeting->getMeetingParameters();*/


        /*
        if ($parameters->hasModerationPermissions()) {
            exec("cd /git/studip-teams-node/ && node callAddUser.js " . $meetingId . " " . $userMail . " true" , $output);
            print_r($output);

        } else {
            exec("cd /git/studip-teams-node/ && node callAddUser.js " . $meetingId . " " . $userMail . " false" , $output);
            print_r($output);

        }

        exec("cd /git/studip-teams-node/ && node getMeetingUrl.js " . $meetingId, $meetingUrlOutput);


        return $meetingUrlOutput[0];
        */

        //return 'https://www.microsoft.com/de-de/microsoft-365/microsoft-teams/group-chat-software';
    }

    /**
     * {@inheritdoc}
     */
    public function getRecordings(MeetingParameters $parameters)
    {

        // Returning empty array
        return false;
    }

    /**
     * {@inheritdoc}
     */
    function deleteRecordings($recordID)
    {

        return false;
    }

    /**
     * {@inheritdoc}
     */
    function isMeetingRunning(MeetingParameters $parameters)
    {

        return false;

    }

    /**
     * {@inheritdoc}
     */
    function getMeetingInfo(MeetingParameters $parameters)
    {

        // Retrieve the Link to Teams through this maybe. Need to know where this info will go
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public static function getConfigOptions()
    {

        return array(
            new ConfigOption('url', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Microsoft Graph URL'), 'https://graph.microsoft.com'),
            new ConfigOption('tenant_id', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Verzeichnis-ID (Mandant)')),
            new ConfigOption('client_id', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Geheimer Client Schlüssel (ID)')),
            new ConfigOption('client_secret', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Geheimer Client Schlüssel (Wert)'))
        );
    }

    /**
     * {@inheritDoc}
     */
    public static function getCreateFeatures()
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function checkServer() {

        return true;
    }
}
