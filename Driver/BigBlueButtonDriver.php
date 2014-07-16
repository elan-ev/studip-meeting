<?php

namespace ElanEv\Driver;

/**
 * Big Blue Button driver implementation.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class BigBlueButtonDriver implements DriverInterface
{
    /**
     * @var string The url to access the Big Blue Button server
     */
    private $serverUrl;

    /**
     * @var string A secret salt used to sign request
     */
    private $salt;

    public function __construct($serverUrl, $salt)
    {
        $this->serverUrl = $serverUrl;
        $this->salt = $salt;
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
            'voiceBridge' => rand(10000, 99999),
            'webVoice' => '',
            'logoutURL' => '',
            'maxParticipants' => '-1',
            'record' => 'false',
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
    public function isMeetingRunning($meetingId)
    {
        $response = $this->performRequest('isMeetingRunning', array('meetingID' => $meetingId));
        $xml = new \SimpleXMLElement($response);

        if (!$xml instanceof \SimpleXMLElement) {
            return false;
        }

        return isset($xml->running) && strtolower((string)$xml->running) != 'false';
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

        return $this->createUrl('join', $params);
    }

    private function performRequest($endpoint, array $params = array())
    {
        $params['checksum'] = $this->createSignature($endpoint, $params);
        $url = $this->createUrl($endpoint, $params);
        $response = file_get_contents($url);

        return $response;
    }

    private function createSignature($prefix, array $params = array())
    {
        return sha1($prefix.http_build_query($params).$this->salt);
    }

    private function createUrl($path, array $params = array())
    {
        return sprintf(
            '%s/api/%s?%s',
            rtrim($this->serverUrl, '/'),
            $path,
            http_build_query($params)
        );
    }
}
