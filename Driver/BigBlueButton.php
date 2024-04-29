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
use Meetings\Models\I18N;

/**
 * Big Blue Button driver implementation.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 * @author Till Glöggler <tgloeggl@uos.de>
 */
class BigBlueButton implements DriverInterface, RecordingInterface, FolderManagementInterface, ServerRoomsizePresetInterface
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
        $this->connection_timeout = $config['connection_timeout'] ?? 0;
        $this->request_timeout =  $config['request_timeout'] ?? 0;
        $this->course_type = (isset($config['course_types'])) ? $config['course_types'] : '';
        $this->active = (isset($config['active'])) ? $config['active'] : true;
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

            // Remove extra feature param (invite_moderator) which is not accaptable by BBB.
            if (isset($features['invite_moderator'])) {
                unset($features['invite_moderator']);
            }

            // Remove extra feature param (room_anyone_can_start) which is not accaptable by BBB.
            if (isset($features['room_anyone_can_start'])) {
                unset($features['room_anyone_can_start']);
            }

            // Remove extra feature param (default_slide_course_news) which is not accaptable by BBB.
            if (isset($features['default_slide_course_news'])) {
                unset($features['default_slide_course_news']);
            }
            // Remove extra feature param (default_slide_studip_news) which is not accaptable by BBB.
            if (isset($features['default_slide_studip_news'])) {
                unset($features['default_slide_studip_news']);
            }

            if ($features['record'] == 'true') {
                if (self::checkRecordingCapability($features)) {
                    $params['name'] = $params['name'] . ' (' . date('Y-m-d H:i:s') . ')';
                } else {
                    $features['duration'] = '';
                    $features['record'] = 'false';
                }
            } else {
                // Applying duration only when recording is on!
                $features['duration'] = '';
            }

            // Handel Auto Start/Stop Recordings.
            $features = self::handelAutoStartStopRecording($features);

            if (!isset($features['welcome']) || empty($features['welcome'])) {
                $features['welcome'] = Driver::getConfigValueByDriver((new \ReflectionClass(self::class))->getShortName(), 'welcome');
            }

            // Handel Opencast Webcam Recording.
            $opencast_webcam_record = false;
            if (isset($features['opencast_webcam_record'])) {
                $opencast_webcam_record = filter_var($features['opencast_webcam_record'], FILTER_VALIDATE_BOOLEAN);
                unset($features['opencast_webcam_record']);
            }

            if (isset($features['meta_opencast-dc-isPartOf'])) {
                $features['meta_opencast-dc-title'] = htmlspecialchars($params['name']);

                // If the Opencast is responsible for recording, then we pass webcam recording flag as well.
                $features['meta_opencast-add-webcams'] = $opencast_webcam_record;

                $creators = [];
                $meeting = new Meeting($parameters->getMeetingId());
                foreach ($meeting->courses as $course) {
                    foreach ($course->getMembersWithStatus('dozent') as $member) {
                        $creators[] = get_fullname($member->user_id);
                    }
                }
                // if we have a series, whe also have a creator
                $features['meta_opencast-dc-creator'] = implode(', ', $creators);
            }

            if (intval($features['maxParticipants']) == 0) {
                $servers = Driver::getConfigValueByDriver((new \ReflectionClass(self::class))->getShortName(), 'servers');
                if ($servers && isset($servers[$parameters->getMeetingServerIndex()]) && !empty($servers[$parameters->getMeetingServerIndex()]['maxParticipants'])) {
                    $features['maxParticipants'] = intval($servers[$parameters->getMeetingServerIndex()]['maxParticipants']);
                } else {
                    unset($features['maxParticipants']);
                }
            }

            // Make sure that pre-defined params' values take priority over features.
            foreach ($params as $param_key => $param_value) {
                // We remove anything that overlaps in features, so be careful with choosing the feature key name!
                if (isset($features[$param_key])) {
                    unset($features[$param_key]);
                }
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
    public function deleteMeeting(Meeting $meeting)
    {
        $parameters = $meeting->getMeetingParameters();

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
            // To apply correct name for guest moderators.
            $fullname = sprintf('%s, %s', $parameters->getLastName(), $parameters->getFirstName());
            if ($parameters->getUsername() == 'guest_moderator') {
                $fullname = $parameters->getFirstName();
            }
            $params = array(
                'meetingID' => $parameters->getRemoteId() ?: $parameters->getMeetingId(),
                'fullName' => $fullname,
                'password' => $parameters->getPassword(),
                'userID' => '',
                'webVoiceConf' => '',
            );

            if ($avatar_url = $parameters->getAvatarUrl()) {
                $params['avatarURL'] = $avatar_url;
            }
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
            $error = I18N::_('Interner Fehler');
            $message = I18N::_('Bitte wenden Sie sich an einen Systemadministrator!');
            if ($xml instanceof \SimpleXMLElement) {
                $message = (string) $xml->message ? (string) $xml->message : $message;
                $error = (string) $xml->error ? (string) $xml->error : $error;
                $status_code = (string) $xml->status ? (string) $xml->status : $status_code;
            }
            throw new Error(I18N::_($error) . ': ' . I18N::_($message), $status_code);
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
            if (is_bool($value) && $key != 'duration') {
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
            new ConfigOption('active', I18N::_('Aktiv'), true),
            new ConfigOption('label', I18N::_('Bezeichnung'), 'Server #'),
            new ConfigOption('url',     I18N::_('URL des BBB-Servers')),
            new ConfigOption('api-key', I18N::_('Api-Key (Salt)'), null, null, 'password'),
            new ConfigOption('proxy', I18N::_('Zugriff über Proxy')),
            new ConfigOption('connection_timeout', I18N::_('Verbindungs-Timeout (e.g. 0.5)')),
            new ConfigOption('request_timeout', I18N::_('Anfrage-Timeout (e.g. 3.4)')),
            new ConfigOption('maxParticipants', I18N::_('Maximale Anzahl von Teilnehmenden')),
            new ConfigOption('course_types', I18N::_('Veranstaltungstyp'), MeetingPlugin::getSemClasses(), I18N::_('Nur in folgenden Veranstaltungskategorien nutzbar')),
            new ConfigOption('description', I18N::_('Beschreibung'), '', I18N::_('Der Beschreibungstext wird Lehrenden angezeigt wenn dieser Server ausgewählt wird.')),
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getRoomSizePresets() {
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
        $roomsize_features['minParticipants'] = new ConfigOption('minParticipants', I18N::_('Mindestanzahl von Teilnehmenden'), 0);
        $roomsize_features['roomsizeSensitive'] = new ConfigOption('roomsizeSensitive', I18N::_('Automatisch Teilnehmendenanzahl berücksichtigen'), true,
            I18N::_('Wenn diese Option aktiviert ist, werden die entsprechenden Konfigurationen basierend auf der maximalen Teilnehmerzahl des Meetings angewendet.'));
        $roomsize_features['presetName'] = new ConfigOption('presetName', I18N::_('Name'), '');
        return array_reverse($roomsize_features);
    }

    /**
     * {@inheritDoc}
     */
    public static function getCreateFeatures()
    {
        $res['welcome'] = new ConfigOption('welcome', I18N::_('Begrüßungstext'),
                    Driver::getConfigValueByDriver((new \ReflectionClass(self::class))->getShortName(), 'welcome'),
                    self::getFeatureInfo('welcome'));

        $res['duration'] = new ConfigOption('duration', I18N::_('Maximale Aufzeichnungsdauer pro Sitzung'), 240, self::getFeatureInfo('duration'));

        $res['maxParticipants'] = new ConfigOption('maxParticipants', I18N::_('Maximale Teilnehmerzahl'), 0, self::getFeatureInfo('maxParticipants'));

        $res['invite_moderator'] = new ConfigOption('invite_moderator', I18N::_('Moderierendenzugang via Link'), false, self::getFeatureInfo('invite_moderator'));

        $res['guestPolicy-ALWAYS_ACCEPT'] = new ConfigOption('guestPolicy-ALWAYS_ACCEPT', I18N::_('Zugang via Link'), false, self::getFeatureInfo('guestPolicy-ALWAYS_ACCEPT'));

        $res['guestPolicy-ASK_MODERATOR'] = new ConfigOption('guestPolicy-ASK_MODERATOR', I18N::_('Moderierende vor Teilnehmendenzutritt fragen'), false, self::getFeatureInfo('guestPolicy-ASK_MODERATOR'));

        $res['privateChat'] = new ConfigOption('lockSettingsDisablePrivateChat', I18N::_('Private Chats deaktivieren'), false, self::getFeatureInfo('lockSettingsDisablePrivateChat'));

        $res['lockSettingsDisableNote'] = new ConfigOption('lockSettingsDisableNote', I18N::_('Gemeinsame Notizen deaktivieren'), false, self::getFeatureInfo('lockSettingsDisableNote'));

        $res['lockSettingsDisableMic'] = new ConfigOption('lockSettingsDisableMic', I18N::_('Nur Moderierende können Audio teilen'), false, self::getFeatureInfo('lockSettingsDisableMic'));

        $res['lockSettingsDisableCam'] = new ConfigOption('lockSettingsDisableCam', I18N::_('Nur Moderierende können Webcams teilen'), false, self::getFeatureInfo('lockSettingsDisableCam'));

        $res['webcamsOnlyForModerator'] = new ConfigOption('webcamsOnlyForModerator', I18N::_('Nur Moderierende können Webcams sehen'), false, self::getFeatureInfo('webcamsOnlyForModerator'));

        $res['room_anyone_can_start'] = new ConfigOption('room_anyone_can_start', I18N::_('Alle Teilnehmenden können die Konferenz starten'), true, self::getFeatureInfo('room_anyone_can_start'));

        $res['muteOnStart'] = new ConfigOption('muteOnStart', I18N::_('Alle Teilnehmenden initial stumm schalten'), false, self::getFeatureInfo('muteOnStart'));

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
        $allowStartStopRecording_config = filter_var(Driver::getConfigValueByDriver((new \ReflectionClass(self::class))->getShortName(), 'allowStartStopRecording'), FILTER_VALIDATE_BOOLEAN);
        $startStopRecording_config = filter_var(Driver::getConfigValueByDriver((new \ReflectionClass(self::class))->getShortName(), 'startStopRecording'), FILTER_VALIDATE_BOOLEAN);

        $info = '';
        if ($opencast_config) {
            $info = I18N::_('Opencast wird als Aufzeichnungsserver verwendet. Diese Funktion ist im Testbetrieb und es kann noch zu Fehlern kommen.');

            $res['opencast_webcam_record'] = new ConfigOption('opencast_webcam_record', I18N::_('Aufzeichnung von Webcams zulassen.'),
                false, I18N::_('Sofern erlaubt, werden auch die Webcams aufgezeichnet. Das Opencast-System muss diese Funktion unterstützen, um diese Einstellung anzuwenden.'));

        } else if ($record_config) {
            $info = I18N::_('Erlaubt es Moderierenden, die Medien und Ereignisse in der Sitzung für die spätere Wiedergabe aufzuzeichnen. Die Aufzeichnung muss innerhalb der Sitzung von einem Moderator gestartet werden.');
        }
        if ($info) {
            $res['record'] = new ConfigOption('record', I18N::_('Aufzeichnungsfunktion aktivieren'),
            false, $info);
        }

        // Show the "autoStartRecording" feature when the "allowStartStopRecording" is enabled by the admin.
        if ($allowStartStopRecording_config) {
            $res['autoStartRecording'] = new ConfigOption('autoStartRecording', I18N::_('Aufzeichnung starten'),
            $startStopRecording_config, I18N::_('Legen Sie fest, ob die Sitzungsaufzeichnung automatisch oder von den Moderierenden manuell gestartet werden soll.'));
        }

        $res['giveAccessToRecordings'] = new ConfigOption('giveAccessToRecordings', I18N::_('Aufzeichnungen direkt für Teilnehmende sichtbar schalten'),
                true, I18N::_('Legen Sie fest, ob neben Lehrenden auch Teilnehmende Zugriff auf die Aufzeichnungen haben sollen.'));
        return $res;
    }

    /**
     * {@inheritDoc}
     */
    public static function getFeatureDisplayArrangement()
    {

        return [
            'create' => [
                'roomsize' => [
                    'maxParticipants',
                    'muteOnStart',
                    'webcamsOnlyForModerator',
                    'lockSettingsDisableCam',
                    'lockSettingsDisableMic',
                    'lockSettingsDisableNote',
                ],
                'privacy' => [
                    'room_anyone_can_start',
                    'invite_moderator',
                    'guestPolicy-ALWAYS_ACCEPT',
                    'guestPolicy-ASK_MODERATOR',
                    'privateChat'
                ],
                'extended_setting' => [
                    'welcome'
                ],
                'presentation_sildes' => [
                    'default_slide_course_news',
                    'default_slide_studip_news'
                ]
            ],
            'record' => [
                'record_setting' => [
                    'record',
                    'opencast_webcam_record',
                    'giveAccessToRecordings',
                    'autoStartRecording',
                    'duration',
                ]
            ]
        ];
    }

    /**
     * {@inheritDoc}
     */
    public static function useOpenCastForRecording()
    {
        $res = false;
        !MeetingPlugin::checkOpenCast() ?: $res = new ConfigOption('opencast', I18N::_('Opencast für Aufzeichnungen verwenden')
                                                , false, I18N::_('Wenn diese Option aktiviert ist, ist die Aufzeichnung nur mit einer gültigen Serien-ID für den Kurs zulässig.'));
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
                return I18N::_('Bei Aktivierung dieser Option können ausschließlich Moderierende die von Teilnehmenden freigegebenen Webcams sehen.');
            break;
            case 'welcome':
                return I18N::_('Wenn leer, wird die Standardnachricht angezeigt. Sie können folgende Schlüsselwörter einfügen, die automatisch ersetzt werden:
                %% CONFNAME %% (Sitzungsname), %% DIALNUM %% (Sitzungswahlnummer)');
            break;
            case 'lockSettingsDisablePrivateChat':
                return I18N::_('Der private Chat zwischen den Teilnehmenden wird eingeschränkt, Teilnehmende können jedoch weiterhin privat mit Moderierenden kommunizieren.');
                break;
            case 'duration':
                return I18N::_('Die maximale Länge (in Minuten) für das Meeting. Nach Ablauf der eingestellen Dauer wird das Meeting automatisch beendet, d.h. der Raum wird geschlossen. Falls bereits vor Ablauf der Zeit alle Teilnehmenden das Meeting verlassen haben, oder ein Moderator das Meeting aktiv beendet wird der Raum ebenfalls geschlossen.');
                break;
            case 'invite_moderator':
                return I18N::_('Legen Sie fest, ob externe Gäste mit Einladungslink als Moderator an der Besprechung teilnehmen dürfen.');
                break;
            case 'guestPolicy-ALWAYS_ACCEPT':
                return I18N::_('Legen Sie fest, ob Benutzer mit Einladungslink als Gäste an der Besprechung teilnehmen dürfen.');
                break;
            case 'guestPolicy-ASK_MODERATOR':
                return I18N::_('Legen Sie fest, ob Gäste und Teilnehmende dem Meeting direkt beitreten können oder die Teilnahme von einem Moderierenden bestätigt werden muss.');
                break;
            case 'maxParticipants':
                return '';
                break;
            case 'lockSettingsDisableNote':
                return '';
                break;
            case 'lockSettingsDisableMic':
                return '';
                break;
            case 'lockSettingsDisableCam':
                return '';
                break;
            case 'muteOnStart':
                return '';
                break;
            case 'room_anyone_can_start':
                return '';
                break;
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

        // Optimizing base url.
        $base_url = sprintf(
            "%s://%s",
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
            $_SERVER['SERVER_NAME']
        );

        $meeting = new Meeting($meetingId);

        if ($meeting->isNew()) {
            return $options;
        }

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

        // Step 1.
        // We prepare the default slide based on the global condig setting to use StudIP to render the default slide.
        $defaults_from = Driver::getGeneralConfigValue('read_default_slides_from');
        $studip_default_sildes = !empty($defaults_from) && $defaults_from == 'studip' ? true : false;
        $documents = [];
        if ($studip_default_sildes) {
            $default_slide_url = \PluginEngine::getURL('meetingplugin', [], "api/defaultSlide/$meetingId/$token");
            $default_slide_url = strtok($default_slide_url, '?');
            if (isset($_SERVER['SERVER_NAME']) && strpos($default_slide_url, $_SERVER['SERVER_NAME']) === FALSE) {
                $default_slide_url = $base_url . $default_slide_url;
            }
            $documents[] = ['filename' => 'default.pdf', 'url' => $default_slide_url];
        }

        // Step 2.
        // We prepare the slides from the course folder when the admin config is set and there is any folder assigned.
        $preupload_allowed = Driver::getConfigValueByDriver((new \ReflectionClass(self::class))->getShortName(), 'preupload');
        if ($preupload_allowed) {
            $folder = \Folder::find($meeting->folder_id);
            $folder_files = $folder ? $folder->getTypedFolder()->getFiles() : [];
            if (!empty($folder_files)) {
                // Reset the document to follow the default behavior of BBB, in which there is no default.pdf when custom slides are uploaded.
                $documents = [];
                foreach ($folder_files as $file_ref) {
                    if ($file_ref->id && $file_ref->name) {
                        $document_url = \PluginEngine::getURL('meetingplugin', [], "api/slides/$meetingId/{$file_ref->id}/$token");
                        $document_url = strtok($document_url, '?');
                        if (isset($_SERVER['SERVER_NAME']) && strpos($document_url, $_SERVER['SERVER_NAME']) === FALSE) {
                            $document_url = $base_url . $document_url;
                        }
                        $documents[] = ['filename' => $file_ref->name, 'url' => $document_url];
                    }
                }
            }
        }

        // Step 3.
        // We prepare the xml from document and put it in the body.
        if (count($documents)) {
            $modules_xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><modules/>');
            $module_xml = $modules_xml->addChild('module');
            $module_xml->addAttribute('name', 'presentation');
            foreach ($documents as $document) {
                $document_xml = $module_xml->addChild('document');
                $document_xml->addAttribute('filename', $document['filename']);
                $document_xml->addAttribute('url', $document['url']);
            }
            $options['body'] = $modules_xml->asXML();
        }

        // Finally, we return the options, either with or without body.
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

    /**
     * {@inheritDoc}
    */
    public static function getPreUploadFeature()
    {
        $res = [];
        $defaults_from = Driver::getGeneralConfigValue('read_default_slides_from');
        // Settings that depend on admin config to upload slides.
        if ($defaults_from == 'studip') {
            $res['default_slide_course_news'] = new ConfigOption('default_slide_course_news', I18N::_('Ankündigungen aus der Veranstaltung auf leerer Begrüßungsfolie'),
                false, '');
            $res['default_slide_studip_news'] = new ConfigOption('default_slide_studip_news', I18N::_('Ankündigungen aus Stud.IP auf leerer Begrüßungsfolie'),
                false, '');
        }

        return $res;
    }

    /**
     * {@inheritDoc}
    */
    public static function getDriverRecordingAdminConfig()
    {
        $res = [];

        $res['allowStartStopRecording'] = new ConfigOption('allowStartStopRecording', I18N::_('Aufzeichnungen konfigurierbar machen'),
                false, I18N::_("Wenn aktiv, so wird den Mentor:innen die Option 'Aufzeichnung automatisch starten' angezeigt und sie haben die Möglichkeit die Aufzeichnung manuell zu starten, pausieren oder stoppen."));
        $res['startStopRecording'] = new ConfigOption('startStopRecording', I18N::_('Aufzeichnungen standardmäßig automatisch starten'),
                true, I18N::_("Standardwert für die Konferenzraum-Einstellung 'Aufzeichnung automatisch starten'."));

        return $res;
    }

    /**
     *  Applies required rules and manages Auto Start/Stop Recording.
     *
     * @param array $features applied features
     *
     * @return array $features managed features
     */
    private static function handelAutoStartStopRecording($features)
    {
        $allowStartStopRecording = filter_var(Driver::getConfigValueByDriver((new \ReflectionClass(self::class))->getShortName(), 'allowStartStopRecording'), FILTER_VALIDATE_BOOLEAN);
        $startStopRecording = filter_var(Driver::getConfigValueByDriver((new \ReflectionClass(self::class))->getShortName(), 'startStopRecording'), FILTER_VALIDATE_BOOLEAN);
        $autoStartRecording = isset($features['autoStartRecording']) ? filter_var($features['autoStartRecording'], FILTER_VALIDATE_BOOLEAN) : $startStopRecording;

        // In case admin does not allow start/stop recording to be selectable, then we should always pass that recording should be auto-started.
        if (!$allowStartStopRecording) {
            $autoStartRecording = true;
        }

        $features['allowStartStopRecording'] = $allowStartStopRecording;
        $features['startStopRecording'] = $startStopRecording;
        $features['autoStartRecording'] = $autoStartRecording;

        return $features;
    }
}
