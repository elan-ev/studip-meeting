<?php

namespace ElanEv\Driver;

use Guzzle\Http\ClientInterface;

/**
 * DFN video conference driver implementation.
 *
 * @see https://git.vc.dfn.de/lmsapi/doc/wikis/home
 *
 * @author Christian Flothmann
 */
class DfnVc implements DriverInterface
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var string LMSAPI user (email address)
     */
    private $login;

    /**
     * @var string
     */
    private $password;

    /**
     * @param ClientInterface $client
     * @param string          $login
     * @param string          $password
     */
    public function __construct(ClientInterface $client, $config)
    {
        $this->client = $client;
        $this->login = $config['login'];
        $this->password = $config['password'];
    }

    /**
     * {@inheritdoc}
     */
    public function createMeeting(MeetingParameters $parameters)
    {
        // request the session cookie
        $sessionCookie = $this->requestSessionCookie();

        // login using the LMS credentials
        if (!$this->authenticate($sessionCookie)) {
            return false;
        }

        // request the folder id
        $folderId = $this->getFolderId($sessionCookie, 'my-meetings');

        if ($folderId === null) {
            return false;
        }

        // create the meeting
        $response = $this->performRequest(array(
            'action' => 'sco-update',
            'type' => 'meeting',
            'name' => $parameters->getMeetingId().' - '.studip_utf8encode($parameters->getMeetingName()),
            'folder-id' => $folderId,
            'session' => $sessionCookie,
        ));
        $xml = new \SimpleXMLElement($response);
        $scoIdAttributes = $xml->xpath('./sco/@sco-id');
        $parameters->setRemoteId((int) $scoIdAttributes[0]);

        // make the meeting private (participants have to be granted
        // view permissions explicitly)
        $this->performRequest(array(
            'action' => 'permissions-update',
            'acl-id' => (int) $scoIdAttributes[0],
            'principal-id' => 'public-access',
            'permission-id' => 'view-hidden',
            'session' => $sessionCookie,
        ));

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMeeting(MeetingParameters $parameters)
    {
        // request the session cookie
        $sessionCookie = $this->requestSessionCookie();

        // login using the LMS credentials
        if (!$this->authenticate($sessionCookie)) {
            return false;
        }

        // send the a delete request to the DFN API
        $response = $this->performRequest(array(
            'action' => 'sco-delete',
            'sco-id' => $parameters->getRemoteId(),
            'session' => $sessionCookie,
        ));
        $xml = new \SimpleXMLElement($response);

        if ((string) $xml->status->attributes()->code !== 'ok') {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getJoinMeetingUrl(JoinParameters $parameters)
    {
        // request the session cookie
        $sessionCookie = $this->requestSessionCookie();

        // login using the LMS credentials
        if (!$this->authenticate($sessionCookie)) {
            return null;
        }

        // request the folder id
        $folderId = $this->getFolderId($sessionCookie, 'my-meetings');

        // retrieve user information
        $user = $this->getUser($sessionCookie, $parameters);

        $this->performRequest(array(
            'action' => 'permissions-update',
            'acl-id' => $parameters->getRemoteId(),
            'principal-id' => $user['id'],
            'permission-id' => $parameters->hasModerationPermissions() ? 'host' : 'view',
            'session' => $sessionCookie,
        ));

        // request a session cookie for the user
        $userSessionCookie = $this->userLogin($sessionCookie, $parameters);

        // request all SCOs in the folder
        $response = $this->performRequest(array(
            'action' => 'sco-contents',
            'sco-id' => $folderId,
            'session' => $sessionCookie,
        ));
        $xml = new \SimpleXMLElement($response);
        $scoElements = $xml->xpath('./scos/sco[@sco-id="'.$parameters->getRemoteId().'"]');

        foreach ($scoElements[0] as $key => $value) {
            if ($key === 'url-path') {
                $urlPath = (string) $value;

                break;
            }
        }

        // use only the base-url, the join-url does not go to the XML-API
        $parsed_url = parse_url($this->client->getBaseUrl());

        return $parsed_url['scheme'] .'://'. $parsed_url['host'] .'/'
               . ltrim($urlPath, '/') .'?session='.$userSessionCookie;
    }

    private function performRequest(array $params = array())
    {
        $request = $this->client->get('/lmsapi/xml?'.$this->buildQueryString($params));
        $response = $request->send();

        return $response->getBody(true);
    }

    private function buildQueryString($params)
    {
        $segments = array();
        foreach ($params as $key => $value) {
            $segments[] = rawurlencode($key).'='.rawurlencode($value);
        }

        return implode('&', $segments);
    }

    /**
     * Requests a session cookie from the API.
     *
     * @return string The session cookie
     */
    private function requestSessionCookie()
    {
        $response = $this->performRequest(array('action' => 'common-info'));
        $xml = new \SimpleXMLElement($response);

        return $xml->common->cookie;
    }

    /**
     * Authenticates the LMS at the API.
     *
     * @param string $sessionCookie The current session cookie
     *
     * @return bool True if the LMS could be authenticated successfully,
     *              false otherwise
     */
    private function authenticate($sessionCookie)
    {
        $response = $this->performRequest(array(
            'action' => 'login',
            'login' => $this->login,
            'password' => $this->password,
            'session' => $sessionCookie,
        ));
        $xml = new \SimpleXMLElement($response);

        if ((string) $xml->status->attributes()->code !== 'ok') {
            return false;
        }

        return true;
    }

    /**
     * Requests the SCO id of a certain folder.
     *
     * @param string $sessionCookie The current session cookie
     * @param string $folderName    The name of the folder to retrieve
     *
     * @return string|null The folder name or null if no folder could be found
     */
    private function getFolderId($sessionCookie, $folderName)
    {
        $response = $this->performRequest(array(
            'action' => 'sco-shortcuts',
            'session' => $sessionCookie,
        ));
        $xml = new \SimpleXMLElement($response);
        $folderShortcutElement = $xml->xpath('./shortcuts/sco[@type="'.$folderName.'"]/@sco-id');

        if (count($folderShortcutElement) === 0) {
            return null;
        }

        return (string) $folderShortcutElement[0];
    }

    /**
     * Returns data for a certain user.
     *
     * @param string         $sessionCookie The current session cookie
     * @param JoinParameters $parameters    Parameters describing the user
     *
     * @return array The user's data
     */
    private function getUser($sessionCookie, $parameters)
    {
        $response = $this->performRequest(array(
            'action' => 'lms-user-exists',
            'login' => $parameters->getEmail(),
            'session' => $sessionCookie,
        ));
        $xml = new \SimpleXMLElement($response);
        $users = $xml->xpath('./principal-list/principal');

        // create the user if they don't exist yet
        if (count($users) == 0) {
            $response = $this->userCreate($sessionCookie, $parameters);
            $xml = new \SimpleXMLElement($response);
            $users = $xml->xpath('./principal');
        }

        $userDetails = $users[0];

        return array(
            'id' => (int) current($userDetails->xpath('./@principal-id')),
            'username' => (string) $userDetails->login,
            'email' => (string) $userDetails->login,
            'name' => (string) $userDetails->name,
        );
    }

    /**
     * Creates a new API user.
     *
     * @param string         $sessionCookie The current session cookie
     * @param JoinParameters $parameters    Parameters describing the user
     *
     * @return string The API response
     */
    private function userCreate($sessionCookie, JoinParameters $parameters)
    {
        return $this->performRequest(array(
            'action' => 'lms-user-create',
            'first-name' => studip_utf8encode($parameters->getFirstName()),
            'last-name' => studip_utf8encode($parameters->getLastName()),
            'login' => $parameters->getEmail(),
            'session' => $sessionCookie,
        ));
    }

    /**
     * Requests a session cookie for a user.
     *
     * @param string         $sessionCookie The current session cookie
     * @param JoinParameters $parameters    Parameters describing the user
     *
     * @return string The user session cookie
     */
    private function userLogin($sessionCookie, JoinParameters $parameters)
    {
        $response = $this->performRequest(array(
            'action' => 'lms-user-login',
            'login' => $parameters->getEmail(),
            'session' => $sessionCookie,
        ));
        $xml = new \SimpleXMLElement($response);

        return (string) $xml->cookie;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigOptions()
    {
        return array(
            new ConfigOption('url', _('API-Endpoint'), 'https://connect.vc.dfn.de'),
            new ConfigOption('login', _('Funktionskennung')),
            new ConfigOption('password', _('Passwort'))
        );
    }
}
