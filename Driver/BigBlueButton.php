<?php

namespace ElanEv\Driver;

use MeetingPlugin;
use GuzzleHttp\ClientInterface;
use ElanEv\Model\Meeting;
use ElanEv\Model\Driver;

/**
 * Big Blue Button driver implementation.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 * @author Till Glöggler <tgloeggl@uos.de>
 */
class BigBlueButton implements DriverInterface, RecordingInterface
{
    /**
     * @var \GuzzleHttp\ClientInterface The HTTP client
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
        $this->url  = $config['url'];
    }

    /**
     * {@inheritdoc}
     */
    public function createMeeting(MeetingParameters $parameters)
    {
        $params = array(
            'name' => $parameters->getMeetingName(),
            'meetingID' => $parameters->getRemoteId() ?: $parameters->getMeetingId(),
            'attendeePW' => $parameters->getAttendeePassword(),
            'moderatorPW' => $parameters->getModeratorPassword(),
            'dialNumber' => '',
            'webVoice' => '',
        );
        if ($features = json_decode($parameters->getMeetingFeatures(), true)) {
            if (isset($features['roomSizeProfiles'])) { // keen unwanted params
                unset($features['roomSizeProfiles']);
            }
            $params = array_merge($params, $features);
        }
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
        // if a room has already been created it returns true otherwise it creates the room
        $meeting = new Meeting($parameters->getMeetingId());
        $meetingParameters = $meeting->getMeetingParameters();
        $this->createMeeting($meetingParameters);

        if ( $parameters->getUsername() == 'guest') {
            $params = array(
                'meetingID' => $parameters->getRemoteId() ?: $parameters->getMeetingId(),
                'fullName' => $parameters->getFirstName(),
                'password' => $parameters->getPassword(),
                'webVoiceConf' => '',
                'guest' => 'true'
            );
        } else {
            $params = array(
                'meetingID' => $parameters->getRemoteId() ?: $parameters->getMeetingId(),
                'fullName' => sprintf('%s %s', $parameters->getFirstName(), $parameters->getLastName()),
                'password' => $parameters->getPassword(),
                'userID' => '',
                'webVoiceConf' => '',
            );
        }

        $params['checksum'] = $this->createSignature('join', $params);

        return sprintf('%s/api/join?%s', rtrim($this->url, '/'), $this->buildQueryString($params));
    }

    /**
     * {@inheritdoc}
     */
    public function getRecordings(MeetingParameters $parameters)
    {
        $params = array(
            'meetingID' => $parameters->getRemoteId() ?: $parameters->getMeetingId()
        );

        $response = $this->performRequest('getRecordings', $params);

        $xml = new \SimpleXMLElement($response);

        if (!$xml instanceof \SimpleXMLElement) {
            return false;
        }

        return $xml->recordings->recording;
    }

    /**
     * {@inheritdoc}
     */
    function deleteRecordings($recordID)
    {
        $params = [
            'recordID' => is_array($recordID) ? implode(',', $recordID) : $recordID
        ];

        $response = $this->performRequest('deleteRecordings', $params);

        $xml = new \SimpleXMLElement($response);

        if (!$xml instanceof \SimpleXMLElement) {
            return false;
        }

        return (string) $xml->returncode == 'SUCCESS';
    }

    /**
     * {@inheritdoc}
     */
    function isMeetingRunning(MeetingParameters $parameters)
    {
        $params = array(
            'meetingID' => $parameters->getRemoteId() ?: $parameters->getMeetingId()
        );

        $response = $this->performRequest('isMeetingRunning', $params);

        $xml = new \SimpleXMLElement($response);

        if (!$xml instanceof \SimpleXMLElement) {
            return false;
        }

        return (string)$xml->running;

    }

    /**
     * {@inheritdoc}
     */
    function getMeetingInfo(MeetingParameters $parameters)
    {
        $params = array(
            'meetingID' => $parameters->getRemoteId() ?: $parameters->getMeetingId()
        );

        $response = $this->performRequest('getMeetingInfo', $params);

        $xml = new \SimpleXMLElement($response);

        if (!$xml instanceof \SimpleXMLElement) {
            return false;
        }

        return $xml;

    }

    private function performRequest($endpoint, array $params = array())
    {
        $params['checksum'] = $this->createSignature($endpoint, $params);
        $uri = 'api/'.$endpoint.'?'.$this->buildQueryString($params);
        $request = $this->client->request('GET', $this->url .'/'. $uri);

        return $request->getBody(true);
    }

    private function createSignature($prefix, array $params = array())
    {
        return sha1($prefix . $this->buildQueryString($params) . $this->salt);
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
            new ConfigOption('url',     dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'URL des BBB-Servers')),
            new ConfigOption('api-key', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Api-Key (Salt)'))
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getCreateFeatures()
    {
        $res = [
            new ConfigOption('guestPolicy', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Zutritt von Gästen'),
                 ['ALWAYS_ACCEPT' => _('Immer akzeptieren'), 'ALWAYS_DENY' => _('Immer ablehnen'), 'ASK_MODERATOR' => _('Moderator fragen')]),
            new ConfigOption('duration', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Dauer der Konferenz (Default: 240 Minuten)'),
                 _('Wenn leer, wird eine Dauer von "240" Minuten eingestellt')),
        ];

        if (Driver::getConfigValueByDriver((new \ReflectionClass(self::class))->getShortName(), 'record')) {
            $res[] = new ConfigOption('record', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Aufzeichnung (Default: Nein)'),
                ['true' => 'Ja', 'false' => 'Nein']);
        }

        $res[] = new ConfigOption('roomSizeProfiles', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Größe des Raumes (Default: Kleiner Raum)'),
                self::roomSizeProfile()
        );

        return array_reverse($res);
    }

    /**
     * {@inheritDoc}
     */
    public function useOpenCastForRecording()
    {
        $res = false;
        !MeetingPlugin::checkOpenCast() ?: $res = new ConfigOption('opencast', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Opencast für Aufzeichnungen verwenden'), false);
        return $res;
    }

    /**
     * Return the list of room size related create features
     *
     * @return array consists of nested list of ConfigOptions
    */
    static private function roomSizeProfile () {
        return [
            new ConfigOption('small', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Kleiner Raum'), [
                new ConfigOption('maxParticipants', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Maximale Teilnehmerzahl'), 50),
                new ConfigOption('muteOnStart', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Beim Start stumm schalten'), true),
            ]),
            new ConfigOption('medium', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Mittlerer Raum'), [
                new ConfigOption('maxParticipants', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Maximale Teilnehmerzahl'), 150),
                new ConfigOption('muteOnStart', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Beim Start stumm schalten'), true),
                new ConfigOption('webcamsOnlyForModerator', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Webcams Nur für Moderatoren'), true),
            ]),
            new ConfigOption('large', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Großer Raum'), [
                new ConfigOption('maxParticipants', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Maximale Teilnehmerzahl'), 300),
                new ConfigOption('lockSettingsDisableCam', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Teilnehmer Webcam deaktivieren'), true),
                new ConfigOption('lockSettingsDisableMic', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Teilnehmer Mikrofon deaktivieren'), true),
                new ConfigOption('lockSettingsDisableNote', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Teilnehmer Notizen deaktivieren'), true),
            ]),
            new ConfigOption('no-limit', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Keine Grenzen'), [
                new ConfigOption('maxParticipants', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Maximale Teilnehmerzahl')),
                new ConfigOption('muteOnStart', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Beim Start stumm schalten'), false),
                new ConfigOption('webcamsOnlyForModerator', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Webcams Nur für Moderatoren'), false),
                new ConfigOption('lockSettingsDisableCam', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Teilnehmer Webcam deaktivieren'), false),
                new ConfigOption('lockSettingsDisableMic', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Teilnehmer Mikrofon deaktivieren'), false),
                new ConfigOption('lockSettingsDisableNote', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Teilnehmer Notizen deaktivieren'), false),
            ]),
        ];
    }
}
