<?php

namespace ElanEv\Tests\Driver;

use ElanEv\Driver\BigBlueButton;
use ElanEv\Driver\JoinParameters;
use ElanEv\Driver\MeetingParameters;
use GuzzleHttp\ClientInterface;

/**
 * @author Christian Flothmann <christian.flothmann@uos.de>
 * @author Till Glöggler <tgloeggl@uos.de>
 */
class BigBlueButtonDriverTest extends AbstractDriverTest
{
    private $salt = '8eea0fdb387a787b23cbfe98ad942012';

    /**
     * {@inheritdoc}
     */
    public function getCreateMeetingData()
    {
        $parameters = new MeetingParameters();
        $parameters->setMeetingId('meeting-id');
        $parameters->setMeetingName('meeting-name');
        $parameters->setAttendeePassword('attendee-password');
        $parameters->setModeratorPassword('moderator-password');
        $urlParameters = array(
            'name' => 'meeting-name',
            'meetingID' => 'meeting-id',
            'attendeePW' => 'attendee-password',
            'moderatorPW' => 'moderator-password',
            'dialNumber' => '',
            'webVoice' => '',
            'logoutURL' => '',
            'maxParticipants' => '-1',
            'record' => 'true',
            'duration' => '0',
            'checksum' => 'c3b7cde9aacde4f6df6189c97d82304b9c8ccf41',
        );

        return array(
            'create-existing-room' => array(
                $parameters,
                array(array(
                    'method' => 'get',
                    'uri' => 'http://example.com/api/create?'.http_build_query($urlParameters),
                    'response' => $this->getDuplicateWarningMessage(),
                )),
                true,
            ),
            'checksum-check-failed' => array(
                $parameters,
                array(array(
                    'method' => 'get',
                    'uri' => 'http://example.com/api/create?'.http_build_query($urlParameters),
                    'response' => $this->getChecksumCheckFailedMessage(),
                )),
                false,
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDeleteMeetingData()
    {
        return array(
            'delete-meeting-not-possible' => array(
                new MeetingParameters(),
                array(),
                true,
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getGetJoinMeetingUrlData()
    {
        $parameters1 = new JoinParameters();
        $parameters1->setMeetingId('280c0c8c16220807126f8787f24bf949');
        $parameters1->setUsername('the-username');
        $parameters1->setPassword('the-password');

        $urlParameters1 = array(
            'meetingID=280c0c8c16220807126f8787f24bf949',
            'fullName=the-username',
            'password=the-password',
            'userID=',
            'webVoiceConf=',
            'checksum=b04523cca534cfcca964347f4c00f634ba0ae0e4',
        );

        $parameters2 = new JoinParameters();
        $parameters2->setMeetingId('280c0c8c16220807126f8787f24bf949');
        $parameters2->setUsername('the username');
        $parameters2->setPassword('the password');

        $urlParameters2 = array(
            'meetingID=280c0c8c16220807126f8787f24bf949',
            'fullName=the%20username',
            'password=the%20password',
            'userID=',
            'webVoiceConf=',
            'checksum=b211323a8de25b149bea8d304efd48d44434c0ff',
        );

        return array(
            'without-spaces-invalues' => array($parameters1, array(), 'http://example.com/api/join?'.implode('&', $urlParameters1)),
            'with-spaces-invalues' => array($parameters2, array(), 'http://example.com/api/join?'.implode('&', $urlParameters2)),
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function createDriver(ClientInterface $client)
    {
        return new BigBlueButton($client, [
            'api-key' => $this->salt,
            'url' => 'http://example.com'
        ]);
    }

    private function getDuplicateWarningMessage()
    {
        return '
            <response>
                <returncode>SUCCESS</returncode>
                <meetingID>a07535cf2f8a72df33c12ddfa4b53dde</meetingID>
                <attendeePW>8ab424b8ec4fa0a2289740274f812b17</attendeePW>
                <moderatorPW>4265c155d3b13be3244f304042156050</moderatorPW>
                <createTime>1408441030997</createTime>
                <hasBeenForciblyEnded>false</hasBeenForciblyEnded>
                <messageKey>duplicateWarning</messageKey>
                <message>This conference was already in existence and may currently be in progress.</message>
            </response>
            ';
    }

    private function getChecksumCheckFailedMessage()
    {
        return '
            <response>
                <returncode>FAILED</returncode>
                <messageKey>checksumError</messageKey>
                <message>You did not pass the checksum security check</message>
            </response>
            ';
    }
}
