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
class DfnVcDriver implements DriverInterface
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
    public function __construct(ClientInterface $client, $login, $password)
    {
        $this->client = $client;
        $this->login = $login;
        $this->password = $password;
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
        $response = $this->performRequest(null, array(
            'action' => 'sco-update',
            'type' => 'meeting',
            'name' => $parameters->getIdentifier(),
            'folder-id' => $folderId,
            'session' => $sessionCookie,
        ));
        $xml = new \SimpleXMLElement($response);
        $scoIdAttributes = $xml->xpath('./sco/@sco-id');
        $parameters->setRemoteId((int) $scoIdAttributes[0]);

        // make the meeting private (participants have to be granted
        // view permissions explicitly)
        $this->performRequest(null, array(
            'action' => 'permissions-update',
            'acl-id' => (int) $scoIdAttributes[0],
            'principal-id' => 'public-access',
            'permission-id' => 'denied',
            'session' => $sessionCookie,
        ));

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isMeetingRunning(MeetingParameters $parameters)
    {
        // request the session cookie
        $sessionCookie = $this->requestSessionCookie();

        // login using the LMS credentials
        if (!$this->authenticate($sessionCookie)) {
            return false;
        }

        // request the folder id
        $folderId = $this->getFolderId($sessionCookie, 'my-meetings');

        // request all SCOs in the folder
        $response = $this->performRequest(null, array(
            'action' => 'sco-contents',
            'sco-id' => $folderId,
            'session' => $sessionCookie,
        ));
        $xml = new \SimpleXMLElement($response);

        // check if there is a meeting with the given id
        return count($xml->xpath('./scos/sco[@sco-id="'.$parameters->getRemoteId().'"]')) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getJoinMeetingUrl(JoinParameters $parameters)
    {
        // TODO: Implement getJoinMeetingUrl() method.
    }

    private function performRequest($endpoint, array $params = array())
    {
        $request = $this->client->get('/lmsapi/xml'.$endpoint.'?'.$this->buildQueryString($params));
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
        $response = $this->performRequest(null, array('action' => 'common-info'));
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
        $response = $this->performRequest(null, array(
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
        $response = $this->performRequest(null, array(
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
}
