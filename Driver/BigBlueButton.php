<?php

namespace ElanEv\Driver;

use MeetingPlugin;
use GuzzleHttp\ClientInterface;
use ElanEv\Model\Meeting;
use ElanEv\Model\Driver;
use Throwable;

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

            if (isset($features['giveAccessToRecordings'])) { // keen unwanted params
                unset($features['giveAccessToRecordings']);
            }

            if ($features['guestPolicy'] == 'ALWAYS_DENY') {
                unset($features['guestPolicy']);
            }

            if ($features['record'] == 'true') {
                $params['name'] = $params['name'] . ' (' . date('Y-m-d H:i:s') . ')';
            }

            $params = array_merge($params, $features);
        }

        $options = array();
        $options = $this->PrepareSlides($parameters->getMeetingId());
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

    private function performRequest($endpoint, array $params = array(), array $options = [])
    {
        $params['checksum'] = $this->createSignature($endpoint, $params);
        $uri = 'api/'.$endpoint.'?'.$this->buildQueryString($params);
        $request = $this->client->request('GET', $this->url .'/'. $uri, $options);

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
            new ConfigOption('proxy', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Zugriff über Proxy')),
            new ConfigOption('maxParticipants', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Maximale Teilnehmer')),
            new ConfigOption('roomsize-presets', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Raumgrößenvoreinstellungen'), self::getRoomSizePresets()
            ),
        );
    }

    private function getRoomSizePresets() {
        return array(
            new ConfigOption('small', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Kleiner Raum'), self::getRoomSizeFeature(0)),
            new ConfigOption('medium', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Mittlerer Raum'), self::getRoomSizeFeature(50)),
            new ConfigOption('large', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Großer Raum'), self::getRoomSizeFeature(150)),
        );
    }

    private function getRoomSizeFeature($minParticipants = 0) {
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
    public function getCreateFeatures()
    {
        $res['guestPolicy'] =
            new ConfigOption('guestPolicy', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Zugang via Link'),
                 ['ALWAYS_DENY' => _('Nicht gestattet'), 'ASK_MODERATOR' => _('Moderator vor dem Zutritt fragen'), 'ALWAYS_ACCEPT' => _('Gestattet'), ],
                 _('Legen Sie fest, ob Benutzer mit Einladungslink als Gäste an der Besprechung teilnehmen dürfen und ob Gäste dem Meeting direkt beitreten können oder ihre Teilnahme von einem Moderator bestätigt werden muss.'));

        $res['duration'] = new ConfigOption('duration', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Minuten Konferenzdauer'),
                    240,
                    _('Die maximale Länge (in Minuten) für das Meeting. Nach Ablauf der eingestellen Dauer wird das Meeting automatisch beendet, d.h. der Raum wird geschlossen. Falls bereits vor Ablauf der Zeit alle Teilnehmenden das Meeting verlassen haben, oder ein Moderator das Meeting aktiv beendet wird der Raum ebenfalls geschlossen.'));

        $res['maxParticipants'] = new ConfigOption('maxParticipants', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Maximale Teilnehmerzahl'), 50, self::getFeatureInfo('maxParticipants'));


        $res['privateChat'] = new ConfigOption('lockSettingsDisablePrivateChat', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Private Chats deaktivieren'),
                    false, null);


        $res['lockSettingsDisableNote'] = new ConfigOption('lockSettingsDisableNote', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Gemeinsame Notizen deaktivieren'), false, self::getFeatureInfo('lockSettingsDisableNote'));

        $res['lockSettingsDisableMic'] = new ConfigOption('lockSettingsDisableMic', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Nur Moderatoren können Audio teilen'), false, self::getFeatureInfo('lockSettingsDisableMic'));

        $res['lockSettingsDisableCam'] = new ConfigOption('lockSettingsDisableCam', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Nur Moderatoren können Webcams teilen'), false, self::getFeatureInfo('lockSettingsDisableCam'));

        $res['webcamsOnlyForModerator'] = new ConfigOption('webcamsOnlyForModerator', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Nur Moderatoren können Webcams sehen'), false, self::getFeatureInfo('webcamsOnlyForModerator'));

        $res['muteOnStart'] = new ConfigOption('muteOnStart', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Alle Teilnehmenden initial stumm schalten'), false, self::getFeatureInfo('muteOnStart'));

        return array_reverse($res);
    }

    /**
     * {@inheritDoc}
     */
    public function getRecordFeature()
    {
        $res = [];
        if (Driver::getConfigValueByDriver((new \ReflectionClass(self::class))->getShortName(), 'record')) { // dependet on config record
            $res[] = new ConfigOption('record', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Sitzungen können aufgezeichnet werden.'),
                false, _('Erlaubt es Moderatoren, die Medien und Ereignisse in der Sitzung für die spätere Wiedergabe aufzuzeichnen. Die Aufzeichnung muss innerhalb der Sitzung von einem Moderator gestartet werden.'));
        }

        //independent from config record
        $res[] = new ConfigOption('giveAccessToRecordings', dgettext(MeetingPlugin::GETTEXT_DOMAIN, 'Aufzeichnungen für Teilnehmende sichtbar schalten'),
                true, _('Legen Sie fest, ob neben Lehrenden auch Teilnehmende Zugriff auf die Aufzeichnungen haben sollen.'));
        return $res;
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
     * Return the info text of frequently used features
     *
     * @return string info text to be displayed as tooltip
    */
    static private function getFeatureInfo($name)  {
        switch ($name) {
            case 'webcamsOnlyForModerator':
                return _('Bei Aktivierung dieser Option können ausschließlich Moderatoren die von Teilnehmenden freigegebenen Webcams sehen.');
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
    * ::Discription
    *
    * @param (type) (name) (desc)
    * @return (type) (name) (desc)
    */
    private function PrepareSlides ($meetingId) {
        $options = array();
        $meeting = new Meeting($meetingId);
        if ($meeting->isNew()) {
            return '';
        }
        $course = $meeting->courses[0];
        $course_dates = \CourseDate::findBySeminar_id($course->id);
        $today_timestamp = strtotime(date('d.m.Y'));
        $today_date = new \DateTime("@$today_timestamp"); 
        $session_file = array();
        foreach ($course_dates as $course_date) {
            $session_timestamp = strtotime(date('d.m.Y', $course_date->date));
            $session_date = new \DateTime("@$session_timestamp"); 
            if ($today_date == $session_date) {
                $session_files = $course_date->getAccessibleFolderFiles($GLOBALS['user']->id)['files'];
                if (count($session_files) > 0) {
                    foreach ($session_files as $session_file_id => $session_file) {
                        $path_file = $session_file->file->storage == 'disk' ? $session_file->file->path : $session_file->file->url;
                        $filesize = @filesize($path_file);
                        $filename = $session_file->name;
                        $lowerfilename = strtolower($filename);
                        if (strpos($lowerfilename, 'meeting_') !== FALSE && $filesize) {
                            $slide_url = '';
                            if ($session_file->file->url) { // url
                                $slide_url = $session_file->file->url;
                            } else if ($session_file->file->storage == 'disk') {
                                $slide_url = \PluginEngine::getURL('meetingplugin', ['slide_id' => $session_file->id], 'slides');
                                if (isset($_SERVER['SERVER_NAME']) && strpos($slide_url, $_SERVER['SERVER_NAME']) === FALSE) {
                                    $base_url = sprintf(
                                        "%s://%s",
                                        isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
                                        $_SERVER['SERVER_NAME']
                                    );
                                    $slide_url = $base_url . $slide_url;
                                }
                            }
                            $options['body'] = "<?xml version='1.0' encoding='UTF-8'?> <modules>	<module name='presentation'> <document url='$slide_url' filename='$filename'/> </module></modules>";
                            return $options;
                        }
                    }
                }
            }
        }
        return $options;
    }
}
