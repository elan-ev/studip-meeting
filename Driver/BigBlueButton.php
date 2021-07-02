<?php

namespace ElanEv\Driver;

use MeetingPlugin;
use GuzzleHttp\ClientInterface;
use ElanEv\Model\Meeting;
use ElanEv\Model\MeetingToken;
use ElanEv\Model\Driver;
use Throwable;
use GuzzleHttp\Exception\BadResponseException;
use Meetings\Errors\Error;

/**
 * Big Blue Button driver implementation.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 * @author Till Glöggler <tgloeggl@uos.de>
 */
class BigBlueButton implements DriverInterface, RecordingInterface, FolderManagementInterface
{
    /**
     * @var \GuzzleHttp\ClientInterface The HTTP client
     */
    private $client;

    /**
     * @var string A secret salt used to sign request
     */
    private $salt;

    /**
     * @var string Course Type in which the server of this driver should be used "{$semClassId}_{$semClassTypeId}"
     */
    public $course_type;

    /**
     * @var boolean Indication of server activation
     */
    public $active;

    public function __construct(ClientInterface $client, array $config)
    {
        $this->client = $client;

        if (!isset($config['api-key'])) {
            throw new \InvalidArgumentException('Missing api-key in config array!');
        }

        $this->salt = $config['api-key'];
        $this->url  = $config['url'];
        $this->connection_timeout = $config['connection_timeout'];
        $this->request_timeout =  $config['request_timeout'];
        $this->course_type = (isset($config['course_types'])) ? $config['course_types'] : '';
        $this->active = (isset($config['active'])) ? $config['active'] : true;
    }

    /**
     * {@inheritdoc}
     */
    public function createMeeting(MeetingParameters &$parameters)
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

            if (isset($features['giveAccessToRecordings'])) { // keen unwanted params
                unset($features['giveAccessToRecordings']);
            }

            if (isset($features['guestPolicy-ALWAYS_ACCEPT'])) {
                if ($features['guestPolicy-ALWAYS_ACCEPT'] == "true") {
                    $features['guestPolicy'] = 'ALWAYS_ACCEPT';
                } else {
                    $features['guestPolicy'] = 'ALWAYS_DENY';
                }
                unset($features['guestPolicy-ALWAYS_ACCEPT']);
            }

            if (isset($features['guestPolicy-ASK_MODERATOR'])) {
                if ($features['guestPolicy-ASK_MODERATOR'] == "true") {
                    $features['guestPolicy'] = 'ASK_MODERATOR';
                }
                unset($features['guestPolicy-ASK_MODERATOR']);
            }

            // The logic from BBB seems not to work with ALWAYS_DENY only for guests, in fact,
            // it denies both guests and participants.
            if ($features['guestPolicy'] == 'ALWAYS_DENY') {
                unset($features['guestPolicy']);
            }

            if ($features['record'] == 'true') {
                if (self::checkRecordingCapability($features)) {
                    $params['name'] = $params['name'] . ' (' . date('Y-m-d H:i:s') . ')';
                } else {
                    $features['record'] = 'false';
                }
            }

            if (!isset($features['welcome'])) {
                $features['welcome'] = Driver::getConfigValueByDriver((new \ReflectionClass(self::class))->getShortName(), 'welcome');
            }

            if (isset($features['meta_opencast-dc-isPartOf'])) {
                $features['meta_opencast-dc-title'] = htmlspecialchars($params['name']);
            }

            $params = array_merge($params, $features);
        }

        //additional information using meta_
        if ($manifest = MeetingPlugin::getMeetingManifestInfo()) {
            !isset($manifest["pluginname"]) ?: $params['meta_bbb-origin'] = 'Stud.IP - ' . $manifest["pluginname"] .
                                                (strpos(strtolower($manifest["pluginname"]), 'plugin') !== FALSE ?: ' Plugin');
            !isset($manifest['version']) ?: $params['meta_bbb-origin-version'] = $manifest['version'];
        }
        !$GLOBALS['ABSOLUTE_URI_STUDIP'] ?: $params['meta_bbb-origin-server-name'] = $GLOBALS['ABSOLUTE_URI_STUDIP'];


        $options = $this->prepareSlides($parameters->getMeetingId());
        $response = $this->performRequest('create', $params, $options);
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

        //Handle Meeting Token if the user is moderator!
        if ($parameters->hasModerationPermissions()) {
            $meeting_token = $meeting->meeting_token;
            //make sure it exists (only for those pre-defined rooms)
            if (!$meeting_token) {
                $meeting_token = new MeetingToken();
                $meeting_token->meeting_id = $meeting->id;
                $meeting_token->token = MeetingToken::generate_token();
                $meeting_token->expiration = strtotime("+1 day");
                $meeting_token->store();
            }
            //make sure it is valid - if not renew everything
            if ($meeting_token->is_expired()) {
                $meeting_token->token = MeetingToken::generate_token();
                $meeting_token->expiration = strtotime("+1 day");
                $meeting_token->store();
            }
        }

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
                'fullName' => sprintf('%s, %s', $parameters->getLastName(), $parameters->getFirstName()),
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

    private function performRequest($endpoint, array $params = array(), array $options = [])
    {
        $params['checksum'] = $this->createSignature($endpoint, $params);
        $uri = 'api/'.$endpoint.'?'.$this->buildQueryString($params);

        if (preg_match("/^[\d\.]+$/", $this->connection_timeout)) {
            $options['connect_timeout'] = floatval($this->connection_timeout);
        }

        if (preg_match("/^[\d\.]+$/", $this->request_timeout)) {
            $options['timeout'] = floatval($this->request_timeout);
        }

        try {
            $method = (is_array($options) && count($options)) ? 'POST' : 'GET';
            $request = $this->client->request($method, $this->url .'/'. $uri, $options);
            return $request->getBody(true);
        } catch (BadResponseException $e) {
            $response = $e->getResponse()->getBody(true);
            $xml = new \SimpleXMLElement($response);
            $status_code = 500;
            $error = _('Internal Error');
            $message = _('Please contact a system administrator!');
            if ($xml instanceof \SimpleXMLElement) {
                $message = (string) $xml->message ? (string) $xml->message : $message;
                $error = (string) $xml->error ? (string) $xml->error : $error;
                $status_code = (string) $xml->status ? (string) $xml->status : $status_code;
            }
            throw new Error(_($error) . ': ' . _($message), $status_code);
        }

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
            if (filter_var($value, FILTER_VALIDATE_BOOLEAN) && $key != 'duration') {
                $encoded_value = $value == true ? 'true' : 'false';
            } else {
                $encoded_value = rawurlencode($value);
            }
            $segments[] = rawurlencode($key).'='.$encoded_value;
        }

        return implode('&', $segments);
    }

    /**
     * {@inheritDoc}
     */
    public static function getConfigOptions()
    {
        return array(
            new ConfigOption('active', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Aktiv?'), true),
            new ConfigOption('label', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Label'), 'Server #'),
            new ConfigOption('url',     dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'URL des BBB-Servers')),
            new ConfigOption('api-key', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Api-Key (Salt)')),
            new ConfigOption('proxy', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Zugriff über Proxy')),
            new ConfigOption('connection_timeout', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Connection Timeout (e.g. 0.5)')),
            new ConfigOption('request_timeout', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Request Timeout (e.g. 3.4)')),
            new ConfigOption('maxParticipants', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Maximale Teilnehmer')),
            new ConfigOption('course_types', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Veranstaltungstyp'), MeetingPlugin::getSemClasses(), _('Nur in folgenden Veranstaltungskategorien nutzbar')),
            new ConfigOption('description', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Beschreibung'), '', _('Ein Beschreibungstext wird angezeigt, um den Server zu führen oder zu beschreiben.')),
            new ConfigOption('roomsize-presets', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Raumgrößenvoreinstellungen'), self::getRoomSizePresets()),
        );
    }

    private static function getRoomSizePresets() {
        return array(
            new ConfigOption('small', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Kleiner Raum'), self::getRoomSizeFeature(0)),
            new ConfigOption('medium', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Mittlerer Raum'), self::getRoomSizeFeature(50)),
            new ConfigOption('large', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Großer Raum'), self::getRoomSizeFeature(150)),
        );
    }

    private static function getRoomSizeFeature($minParticipants = 0) {
        $roomsize_features = array_filter(self::getCreateFeatures(), function ($configOption) {
            return in_array($configOption->getName(),
                            [
                                'lockSettingsDisableNote',
                                'webcamsOnlyForModerator',
                                'lockSettingsDisableCam',
                                'lockSettingsDisableMic',
                                'muteOnStart',
                            ]);
        });
        $roomsize_features['minParticipants'] = new ConfigOption('minParticipants', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Min. Teilnehmerzahl'), $minParticipants);
        return array_reverse($roomsize_features);
    }

    /**
     * {@inheritDoc}
     */
    public static function getCreateFeatures()
    {
        $res['welcome'] = new ConfigOption('welcome', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Willkommensnachricht'),
                    Driver::getConfigValueByDriver((new \ReflectionClass(self::class))->getShortName(), 'welcome'),
                    self::getFeatureInfo('welcome'));

        $res['duration'] = new ConfigOption('duration', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Minuten Konferenzdauer'),
                    240,
                    _('Die maximale Länge (in Minuten) für das Meeting. Nach Ablauf der eingestellen Dauer wird das Meeting automatisch beendet, d.h. der Raum wird geschlossen. Falls bereits vor Ablauf der Zeit alle Teilnehmenden das Meeting verlassen haben, oder ein Moderator das Meeting aktiv beendet wird der Raum ebenfalls geschlossen.'));

        $res['maxParticipants'] = new ConfigOption('maxParticipants', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Maximale Teilnehmerzahl'), 50, self::getFeatureInfo('maxParticipants'));

        $res['guestPolicy-ALWAYS_ACCEPT'] = new ConfigOption('guestPolicy-ALWAYS_ACCEPT', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Zugang via Link'), false,
                 _('Legen Sie fest, ob Benutzer mit Einladungslink als Gäste an der Besprechung teilnehmen dürfen.'));

        $res['guestPolicy-ASK_MODERATOR'] = new ConfigOption('guestPolicy-ASK_MODERATOR', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Moderatoren vor Teilnehmendenzutritt fragen'), false,
                 _('Legen Sie fest, ob Gäste und Teilnehmer dem Meeting direkt beitreten können oder ihre Teilnahme von einem Moderator bestätigt werden muss.'));

        $res['privateChat'] = new ConfigOption('lockSettingsDisablePrivateChat', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Private Chats deaktivieren'),
                    false, null);


        $res['lockSettingsDisableNote'] = new ConfigOption('lockSettingsDisableNote', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Gemeinsame Notizen deaktivieren'), false, self::getFeatureInfo('lockSettingsDisableNote'));

        $res['lockSettingsDisableMic'] = new ConfigOption('lockSettingsDisableMic', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Nur Moderatoren können Audio teilen'), false, self::getFeatureInfo('lockSettingsDisableMic'));

        $res['lockSettingsDisableCam'] = new ConfigOption('lockSettingsDisableCam', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Nur Moderatoren können Webcams teilen'), false, self::getFeatureInfo('lockSettingsDisableCam'));

        $res['webcamsOnlyForModerator'] = new ConfigOption('webcamsOnlyForModerator', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Nur Moderatoren können Webcams sehen'), false, self::getFeatureInfo('webcamsOnlyForModerator'));
        $res['room_anyone_can_start'] = new ConfigOption('room_anyone_can_start', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Jeder Teilnehmer kann die Konferenz starten'), true, self::getFeatureInfo('room_anyone_can_start'));
        $res['muteOnStart'] = new ConfigOption('muteOnStart', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Alle Teilnehmenden initial stumm schalten'), false, self::getFeatureInfo('muteOnStart'));

        return array_reverse($res);
    }

    /**
     * {@inheritDoc}
     */
    public static function getRecordFeature()
    {
        $res = [];
        $record_config = filter_var(Driver::getConfigValueByDriver((new \ReflectionClass(self::class))->getShortName(), 'record'), FILTER_VALIDATE_BOOLEAN);
        $opencast_config = filter_var(Driver::getConfigValueByDriver((new \ReflectionClass(self::class))->getShortName(), 'opencast'), FILTER_VALIDATE_BOOLEAN);
        $info = '';
        if ($opencast_config) {
            $info = _('Opencast wird als Aufzeichnungsserver verwendet. Diese Funktion ist im Testbetrieb und es kann noch zu Fehlern kommen.');
        } else if ($record_config) {
            $info = _('Erlaubt es Moderatoren, die Medien und Ereignisse in der Sitzung für die spätere Wiedergabe aufzuzeichnen. Die Aufzeichnung muss innerhalb der Sitzung von einem Moderator gestartet werden.');
        }
        if ($info) {
            $res[] = new ConfigOption('record', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Sitzungen können aufgezeichnet werden.'),
            false, $info);
        }

        //independent from config record
        $res[] = new ConfigOption('giveAccessToRecordings', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Aufzeichnungen für Teilnehmende sichtbar schalten'),
                true, _('Legen Sie fest, ob neben Lehrenden auch Teilnehmende Zugriff auf die Aufzeichnungen haben sollen.'));
        return $res;
    }

    /**
     * {@inheritDoc}
     */
    public static function useOpenCastForRecording()
    {
        $res = false;
        !MeetingPlugin::checkOpenCast() ?: $res = new ConfigOption('opencast', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Opencast für Aufzeichnungen verwenden')
                                                , false, _('Wenn diese Option aktiviert ist, ist die Aufzeichnung nur mit einer gültigen Serien-ID für den Kurs zulässig.'));
        return $res;
    }

    /**
     * Return the info text of frequently used features
     *
     * @return string info text to be displayed as tooltip
    */
    static private function getFeatureInfo($name)  {
        switch ($name) {
            case 'webcamsOnlyForModerator':
                return _('Bei Aktivierung dieser Option können ausschließlich Moderatoren die von Teilnehmenden freigegebenen Webcams sehen.');
            break;
            case 'welcome':
                return _('Wenn leer, wird die Standardnachricht angezeigt. Sie können folgende Schlüsselwörter einfügen, die automatisch ersetzt werden:
                %% CONFNAME %% (Sitzungsname), %% DIALNUM %% (Sitzungswahlnummer)');
            break;
            case 'maxParticipants':
                // return _('Die maximale Anzahl von Benutzern, die gleichzeitig an der Konferenz teilnehmen dürfen.');
                // break;
            case 'lockSettingsDisableNote':
            //     return _('Notizen in dieser Besprechung deaktivieren.');
            // break;
            case 'lockSettingsDisableMic':
            //     return _('Benutzer können in dieser Besprechung nur zuhören.');
            // break;
            case 'lockSettingsDisableCam':
            //     return _('Benutzer können ihre Kamera in dieser Besprechung nicht freigeben.');
            // break;
            case 'muteOnStart':
                // return _('Alle Benutzer starten die Besprechung stummgeschaltet, können ihre Stummschaltung aber jederzeit aufheben.');
                // break;
            case 'room_anyone_can_start':
                // return _('Jeder Teilnehmer kann die Konferenz starten.');
                // break;
            default:
                return '';
                break;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function checkServer() {
        try {
            $response = $this->performRequest('getMeetings');

            $xml = new \SimpleXMLElement($response);

            if (!$xml instanceof \SimpleXMLElement) {
                return false;
            }

            return isset($xml->returncode) && strtolower((string)$xml->returncode) === 'success';
        } catch (Throwable $th) {
           return false;
        }
    }

    /**
     * {@inheritDoc}
    */
    public function prepareSlides($meetingId)
    {
        $options = [];

        if (Driver::getConfigValueByDriver((new \ReflectionClass(self::class))->getShortName(), 'preupload') == false) {
            return $options;
        }

        $meeting = new Meeting($meetingId);

        if ($meeting->isNew() || empty($meeting->folder_id)) {
            return [];
        }

        $documents = [];
        $folder = \Folder::find($meeting->folder_id);
        //generate or get the token
        $token = ($meeting->meeting_token) ? $meeting->meeting_token->get_token() : null;
        if (!$token) {
            $token = MeetingToken::generate_token();
            $meeting_token = new MeetingToken();
            $meeting_token->meeting_id = $meetingId;
            $meeting_token->token = $token;
            $meeting_token->expiration = strtotime("+1 day");
            $meeting_token->store();
        }

        if ($folder) {
            foreach ($folder->getTypedFolder()->getFiles() as $file_ref) {
                if ($file_ref->id && $file_ref->name) {
                    $document_url = \PluginEngine::getURL('meetingplugin', [], "api/slides/$meetingId/{$file_ref->id}/$token");
                    if (isset($_SERVER['SERVER_NAME']) && strpos($document_url, $_SERVER['SERVER_NAME']) === FALSE) {
                        $base_url = sprintf(
                            "%s://%s",
                            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
                            $_SERVER['SERVER_NAME']
                        );
                        $document_url = $base_url . $document_url;
                    }
                    $documents[] = "<document url='$document_url' filename='{$file_ref->name}' />";
                }
            }
        }
        if (count($documents)) {
            $modules = " <modules>	<module name='presentation'> ";
            foreach ($documents as $document) {
                $modules .= $document;
            }
            $modules .= "</module></modules>";
            $options['body'] = "<?xml version='1.0' encoding='UTF-8'?>" . $modules;
        }

        return $options;
    }

    /**
     * {@inheritDoc}
    */
    public static function checkRecordingCapability($features)
    {
        $record_config = filter_var(Driver::getConfigValueByDriver((new \ReflectionClass(self::class))->getShortName(), 'record'), FILTER_VALIDATE_BOOLEAN);
        $opencast_config = filter_var(Driver::getConfigValueByDriver((new \ReflectionClass(self::class))->getShortName(), 'opencast'), FILTER_VALIDATE_BOOLEAN);
        if ($opencast_config && !empty($features['meta_opencast-dc-isPartOf'])) {
           return true;
        } else if ($record_config) {
            return true;
        }
        return false;
    }
}
