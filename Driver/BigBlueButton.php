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

            if ($features['guestPolicy'] == 'ALWAYS_DENY') {
                unset($features['guestPolicy']);
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
        $recordings = $this->getRecordings($parameters);
        if (!empty($recordings)) {
            foreach ($recordings as $recording) {
                $this->deleteRecordings((string)$recording->recordID);
            }
        }
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
            new ConfigOption('api-key', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Api-Key (Salt)')),
            new ConfigOption('proxy', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Zugriff über Proxy'))
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getCreateFeatures()
    {
        $res = [
            new ConfigOption('guestPolicy', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Zutritt von Gästen'),
                 ['ALWAYS_DENY' => _('Keine Gäste gestattet'), 'ASK_MODERATOR' => _('Moderator vor jedem Gästezutritt fragen'), 'ALWAYS_ACCEPT' => _('Gäste haben freien Zutritt'), ],
                 _('Legen Sie fest, ob Benutzer mit Einladungslink als Gäste an der Besprechung teilnehmen dürfen und ob Gäste dem Meeting direkt beitreten können oder ihre Teilnahme von einem Moderator bestätigt werden muss.')),
            new ConfigOption('duration', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Dauer der Konferenz'),
                 _('Wenn leer, wird eine Dauer von "240" Minuten eingestellt'),
                 _('Die maximale Länge (in Minuten) für das Meeting. Nach Ablauf der eingestellen Dauer wird das Meeting automatisch beendet, d.h. der Raum wird geschlossen. Falls bereits vor Ablauf der Zeit alle Teilnehmenden das Meeting verlassen haben, oder ein Moderator das Meeting aktiv beendet wird der Raum ebenfalls geschlossen.')),
            new ConfigOption('lockSettingsDisablePrivateChat', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Private Chats deaktivieren'),
                false,
                 _('Private Chats in dieser Besprechung deaktivieren.')),
        ];

        if (Driver::getConfigValueByDriver((new \ReflectionClass(self::class))->getShortName(), 'record')) {
            $res[] = new ConfigOption('record', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Aufzeichnung'),
                false, _('Der Server wird angewiesen, die Medien und Ereignisse in der Sitzung für die spätere Wiedergabe aufzuzeichnen.'));
        }

        $res[] = new ConfigOption('roomSizeProfiles', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Größe des Raumes'),
                self::roomSizeProfile(),
                _('Diese Funktion verbessert die Serverleistung. Wählen Sie die Raumprofile entsprechend Ihren Anforderungen aus. '
                                            . 'Nach Auswahl der einzelnen Profile können die Einstellungen weiterhin geändert werden.')
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
    static public function roomSizeProfile() {
        return [
            new ConfigOption('small', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Kleiner Raum'), [
                new ConfigOption('maxParticipants', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Maximale Teilnehmerzahl'), 50, self::getFeatureInfo('maxParticipants')),
                new ConfigOption('muteOnStart', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Beim Start stumm schalten'), true, self::getFeatureInfo('muteOnStart')),
                new ConfigOption('webcamsOnlyForModerator', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Webcams nur für Moderatoren'), false, self::getFeatureInfo('webcamsOnlyForModerator')),
                new ConfigOption('lockSettingsDisableCam', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Teilnehmer Webcam deaktivieren'), false, self::getFeatureInfo('lockSettingsDisableCam')),
                new ConfigOption('lockSettingsDisableMic', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Teilnehmer Mikrofon deaktivieren'), false, self::getFeatureInfo('lockSettingsDisableMic')),
                new ConfigOption('lockSettingsDisableNote', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Gemeinsame Notizen deaktivieren'), false, self::getFeatureInfo('lockSettingsDisableNote')),
            ]),
            new ConfigOption('medium', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Mittlerer Raum'), [
                new ConfigOption('maxParticipants', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Maximale Teilnehmerzahl'), 150, self::getFeatureInfo('maxParticipants')),
                new ConfigOption('muteOnStart', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Beim Start stumm schalten'), true, self::getFeatureInfo('muteOnStart')),
                new ConfigOption('webcamsOnlyForModerator', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Webcams nur für Moderatoren'), true, self::getFeatureInfo('webcamsOnlyForModerator')),
                new ConfigOption('lockSettingsDisableCam', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Teilnehmer Webcam deaktivieren'), false, self::getFeatureInfo('lockSettingsDisableCam')),
                new ConfigOption('lockSettingsDisableMic', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Teilnehmer Mikrofon deaktivieren'), false, self::getFeatureInfo('lockSettingsDisableMic')),
                new ConfigOption('lockSettingsDisableNote', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Gemeinsame Notizen deaktivieren'), false, self::getFeatureInfo('lockSettingsDisableNote')),
            ]),
            new ConfigOption('large', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Großer Raum'), [
                new ConfigOption('maxParticipants', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Maximale Teilnehmerzahl'), 300, self::getFeatureInfo('maxParticipants')),
                new ConfigOption('muteOnStart', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Beim Start stumm schalten'), true, self::getFeatureInfo('muteOnStart')),
                new ConfigOption('webcamsOnlyForModerator', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Webcams nur für Moderatoren'), false, self::getFeatureInfo('webcamsOnlyForModerator')),
                new ConfigOption('lockSettingsDisableCam', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Teilnehmer Webcam deaktivieren'), true, self::getFeatureInfo('lockSettingsDisableCam')),
                new ConfigOption('lockSettingsDisableMic', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Teilnehmer Mikrofon deaktivieren'), true, self::getFeatureInfo('lockSettingsDisableMic')),
                new ConfigOption('lockSettingsDisableNote', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Gemeinsame Notizen deaktivieren'), true, self::getFeatureInfo('lockSettingsDisableNote')),
            ]),
            new ConfigOption('no-limit', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Keine Grenzen'), [
                new ConfigOption('maxParticipants', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Maximale Teilnehmerzahl'), null, self::getFeatureInfo('maxParticipants')),
                new ConfigOption('muteOnStart', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Beim Start stumm schalten'), false, self::getFeatureInfo('muteOnStart')),
                new ConfigOption('webcamsOnlyForModerator', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Webcams nur für Moderatoren'), false, self::getFeatureInfo('webcamsOnlyForModerator')),
                new ConfigOption('lockSettingsDisableCam', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Teilnehmer Webcam deaktivieren'), false, self::getFeatureInfo('lockSettingsDisableCam')),
                new ConfigOption('lockSettingsDisableMic', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Teilnehmer Mikrofon deaktivieren'), false, self::getFeatureInfo('lockSettingsDisableMic')),
                new ConfigOption('lockSettingsDisableNote', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Gemeinsame Notizen deaktivieren'), false, self::getFeatureInfo('lockSettingsDisableNote')),
            ]),
        ];
    }

    /**
     * Return the info text of frequently used features
     *
     * @return string info text to be displayed as tooltip
    */
    static private function getFeatureInfo($name)  {
        switch ($name) {
            case 'maxParticipants':
                return _('Die maximale Anzahl von Benutzern, die gleichzeitig an der Konferenz teilnehmen dürfen.');
                break;
            case 'muteOnStart':
                return _('Alle Benutzer starten die Besprechung stummgeschaltet, können ihre Stummschaltung aber jederzeit aufheben.');
                break;
            case 'webcamsOnlyForModerator':
                return _('Nur Moderatoren können ihre Webcam einschalten.');
                break;
            case 'lockSettingsDisableCam':
                return _('Benutzer können ihre Kamera in dieser Besprechung nicht freigeben.');
                break;
            case 'lockSettingsDisableMic':
                return _('Benutzer können in dieser Besprechung nur zuhören.');
                break;
            case 'lockSettingsDisableNote':
                return _('Notizen in dieser Besprechung deaktivieren.');
                break;
            default:
                return _('');
                break;
        }
    }
}
