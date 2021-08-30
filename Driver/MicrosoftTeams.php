<?php

namespace ElanEv\Driver;

use MeetingPlugin;
use Meetings\Errors\DriverError;
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

    private function sanitazeForMS($text)
    {
        return preg_replace('/[^a-zA-Z0-9-]/', '', $text);
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

    private function createUser($username, $mail, $password)
    {
        $user = \User::findByUsername($username);

        $data = [
            'accountEnabled'    => true,
            'displayName'       => $user->getFullname(),
            'userPrincipalName' => $this->sanitazeForMS($username) . '@' . $this->config['domain'],
            'mailNickname'      => $this->sanitazeForMS($user->getFullname()),
            'passwordProfile'   => [
                'forceChangePasswordNextSignIn' => false,
                'password'                      => $password
            ],
            'passwordPolicies'  => 'DisablePasswordExpiration'
        ];

        $result = $this->client->request('POST',
            'https://graph.microsoft.com/v1.0/users/',
            [
                'json' => $data,
                'headers'     => [
                    'Authorization' => 'Bearer ' . $this->accessToken
                ]
            ]
        )->getBody()->getContents();

        return json_decode($result);
    }

    private function getUser(JoinParameters $parameters)
    {

        $upn = $this->sanitazeForMS($parameters->getUsername()) . '@' . $this->config['domain'];

        $result = $this->client->request('GET',
            'https://graph.microsoft.com/v1.0/users?$filter=startswith(userPrincipalName, \'' . $upn . '\')',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken
                ]
            ]
        )->getBody()->getContents();

        $users = json_decode($result);
        $user = reset($users->value);

        if (empty($user)) {
            $user = $this->createUser(
                $parameters->getUsername(),
                $parameters->getEmail(),
                $parameters->getPassword()
            );
        }

       if (empty($user)) {
           throw new DriverError('could not find user in Microsoft Graph with userPrincipalName: '. $upn, 500);
       }

        return $user->id;
    }

    private function groupAddStudent($group_id, JoinParameters $parameters)
    {
        $user_id = $this->getUser($parameters);

        $data = [
            '@odata.id' => 'https://graph.microsoft.com/v1.0/directoryObjects/' . $user_id
        ];

        $result = $this->client->request('PATCH',
            'https://graph.microsoft.com/v1.0/groups/' . $group_id,
            [
                'json' => $data,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken
                ]
            ]
        );

        //
    }

    private function groupAddModerator($group_id, JoinParameters $parameters)
    {
        $user_id = $this->getUser($parameters);

        // check if user is alread added as owner
        $result = $this->client->request('GET',
            'https://graph.microsoft.com/v1.0/groups/' . $group_id . '/owners/' . $user_id,
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken
                ]
            ]
        )->getBody()->getContents();

        $user = json_decode($result);

        if (!empty($user) && $user->id == $user_id) {
            return;
        }

        // add user as group owner
        $data = [
            '@odata.id' => 'https://graph.microsoft.com/v1.0/users/' . $user_id
        ];

        $result = $this->client->request('POST',
            'https://graph.microsoft.com/v1.0/groups/' . $group_id . '/owners/$ref',
            [
                'json' => $data,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createMeeting(MeetingParameters &$parameters)
    {
        $meetingName = $parameters->getMeetingName();
        $meetingId =  $parameters->getMeetingId();

        // these are the only allowed characters for the mailNickname (which has to be unique)
        $meetingName = $this->sanitazeForMS($meetingName);
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


        // add (missing) users to group, this has to happen before creating a teams-session from a group
        if ($parameters->hasModerationPermissions()) {
            $this->groupAddModerator($features['teams']['groupId'], $parameters);
        } else {
            $this->groupAddStudent($features['teams']['groupId'], $parameters);
        }

        if (!$features['teams']['webUrl']) {
            $features['teams']['webUrl'] = $this->createTeamsOfGroup(
                $features['teams']['groupId']
            );

            $meeting->features = json_encode($features);
            $meeting->store();
        }

        return $features['teams']['webUrl'];
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
            new ConfigOption('url',
                dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Microsoft Graph URL'),
                'https://graph.microsoft.com'
            ),
            new ConfigOption('client_id',
                dgettext(MeetingPlugin::GETTEXT_DOMAIN,
                'Anwendungs-ID (Client)')
            ),
            new ConfigOption('tenant_id',
                dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Verzeichnis-ID (Mandant)')
            ),
            new ConfigOption('client_secret',
                dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Geheimer Clientschlüssel (Wert)')
            ),
            new ConfigOption('domain',
                dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Domänenteil des userPrincipalName (ohne @ Zeichen!)'),
                'z.B.: studipmeetings.onmicrosoft.com'
            )
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
