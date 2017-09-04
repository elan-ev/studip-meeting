<?php

namespace ElanEv\Driver;

use Guzzle\Http\ClientInterface;

/**
 * Big Blue Button driver implementation.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 * @author Till Glöggler <tgloeggl@uos.de>
 */
class BigBlueButton implements DriverInterface
{
    /**
     * @var \Guzzle\Http\ClientInterface The HTTP client
     */
    private $client;

    /**
     * @var string A secret salt used to sign request
     */
    private $salt;

    public function __construct(ClientInterface $client, array $config)
    {
        $this->client = $client;

        if (!isset($config['api-key'])) {
            throw new \InvalidArgumentException('Missing api-key in config array!');
        }

        $this->salt = $config['api-key'];
    }

    /**
     * {@inheritdoc}
     */
    public function createMeeting(MeetingParameters $parameters)
    {
        $params = array(
            'name' => $parameters->getMeetingName(),
            'meetingID' => $parameters->getMeetingId(),
            'attendeePW' => $parameters->getAttendeePassword(),
            'moderatorPW' => $parameters->getModeratorPassword(),
            'dialNumber' => '',
            'webVoice' => '',
            'logoutURL' => '',
            'maxParticipants' => '-1',
            'record' => 'true',
            'duration' => '0',
        );
        $response = $this->performRequest('create', $params);
        $xml = new \SimpleXMLElement($response);

        if (!$xml instanceof \SimpleXMLElement) {
            return false;
        }

        return isset($xml->returncode) && strtolower((string)$xml->returncode) === 'success';
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMeeting(MeetingParameters $parameters)
    {
        // Big Blue Button meetings are not persistent and therefore cannot
        // be removed
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getJoinMeetingUrl(JoinParameters $parameters)
    {
        $params = array(
            'meetingID' => $parameters->getMeetingId(),
            'fullName' => $parameters->getUsername(),
            'password' => $parameters->getPassword(),
            'userID' => '',
            'webVoiceConf' => '',
        );
        $params['checksum'] = $this->createSignature('join', $params);

        return sprintf('%s/api/join?%s', rtrim($this->client->getBaseUrl(), '/'), $this->buildQueryString($params));
    }

    private function performRequest($endpoint, array $params = array())
    {
        $params['checksum'] = $this->createSignature($endpoint, $params);
        $uri = 'api/'.$endpoint.'?'.$this->buildQueryString($params);
        $request = $this->client->get($uri);
        $response = $request->send();

        return $response->getBody(true);
    }

    private function createSignature($prefix, array $params = array())
    {
        return sha1($prefix.$this->buildQueryString($params).$this->salt);
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
     * {@inheritDoc}
     */
    public function getConfigOptions()
    {
        return array(
            new ConfigOption('url',     _('URL des BBB-Servers')),
            new ConfigOption('api-key', _('Api-Key (Salt)'))
        );
    }
}
