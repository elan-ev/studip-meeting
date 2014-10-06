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
        $response = $this->performRequest(null, array('action' => 'common-info'));
        $xml = new \SimpleXMLElement($response);
        $sessionCookie = $xml->common->cookie;

        // login using the LMS credentials
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

        // request the folder id
        $response = $this->performRequest(null, array(
            'action' => 'sco-shortcuts',
            'session' => $sessionCookie,
        ));
        $xml = new \SimpleXMLElement($response);
        $folderShortcutElement = $xml->xpath('./shortcuts/sco[@type="my-meetings"]/@sco-id');

        if (count($folderShortcutElement) === 0) {
            return false;
        }

        // create the meeting
        $folderId = (string) $folderShortcutElement[0];
        $this->performRequest(null, array(
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
    public function isMeetingRunning($meetingId)
    {
        // TODO: Implement isMeetingRunning() method.
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
}
