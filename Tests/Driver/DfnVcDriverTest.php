<?php

namespace ElanEv\Tests;

use ElanEv\Driver\DfnVcDriver;
use ElanEv\Driver\JoinParameters;
use ElanEv\Driver\MeetingParameters;
use Guzzle\Http\ClientInterface;

/**
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class DfnVcDriverTest extends AbstractDriverTest
{
    private $login = 'user@example.com';
    private $password = 'password';

    /**
     * {@inheritdoc}
     */
    public function getCreateMeetingData()
    {
        $identifier = md5(uniqid());
        $parameters = new MeetingParameters();
        $parameters->setIdentifier($identifier);
        $parameters->setMeetingName('meeting-name');
        $sessionCookie = md5(uniqid());

        return array(
            array(
                $parameters,
                array(
                    array(
                        'method' => 'get',
                        'uri' => '/lmsapi/xml?action=common-info',
                        'response' => trim($this->createSessionCookieResponse($sessionCookie)),
                    ),
                    array(
                        'method' => 'get',
                        'uri' => '/lmsapi/xml?action=login&login=user%40example.com&password=password&session='.$sessionCookie,
                        'response' => '<?xml version="1.0" encoding="utf-8"?> <results><status code="ok"/></results>',
                    ),
                    array(
                        'method' => 'get',
                        'uri' => '/lmsapi/xml?action=sco-shortcuts&session='.$sessionCookie,
                        'response' => trim($this->createScoShortcutsResponse()),
                    ),
                    array(
                        'method' => 'get',
                        'uri' => '/lmsapi/xml?action=sco-update&type=meeting&name='.$identifier.'&folder-id=383324&session='.$sessionCookie,
                        'response' => trim($this->createScoUpdateResponse()),
                    ),
                    array(
                        'method' => 'get',
                        'uri' => '/lmsapi/xml?action=permissions-update&acl-id=412297&principal-id=public-access&permission-id=denied&session='.$sessionCookie,
                        'reponse' => '',
                    )
                ),
                true,
            ),
            array(
                $parameters,
                array(
                    array(
                        'method' => 'get',
                        'uri' => '/lmsapi/xml?action=common-info',
                        'response' => trim($this->createSessionCookieResponse($sessionCookie)),
                    ),
                    array(
                        'method' => 'get',
                        'uri' => '/lmsapi/xml?action=login&login=user%40example.com&password=password&session='.$sessionCookie,
                        'response' => '<?xml version="1.0" encoding="utf-8"?> <results><status code="no-data"/></results>',
                    ),
                ),
                false,
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getIsMeetingRunningData()
    {
        return array(
            array(null, array(), null),
        );
    }

    public function testGetJoinMeetingUrl()
    {
        $this->markTestSkipped();
    }

    /**
     * {@inheritdoc}
     */
    public function getGetJoinMeetingUrlData()
    {
        $parameters = new JoinParameters();

        return array(
            array($parameters, null),
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function createDriver(ClientInterface $client)
    {
        return new DfnVcDriver($this->client, $this->login, $this->password);
    }

    private function createSessionCookieResponse($sessionCookie)
    {
        return <<<EOT
            <?xml version="1.0" encoding="utf-8"?>
            <results>
                <status code="ok"/>
                <common locale="en" time-zone-id="85" time-zone-java-id="UTC">
                    <cookie>$sessionCookie</cookie>
                    <date>2014-09-23T13:31:36.283+00:00</date>
                    <host>$this->apiUrl</host>
                    <local-host>local.example.com</local-host>
                    <admin-host>remote.example.com</admin-host>
                    <url>/api/xml</url>
                    <version>9.2.2</version>
                    <product-notification>true</product-notification>
                    <user-agent>Guzzle/3.9.2 curl/7.35.0 PHP/5.5.9-1ubuntu4.4</user-agent>
                    <mobile-app-package>air.com.adobe.connectpro</mobile-app-package>
                </common>
                <reg-user><is-reg-user>false</is-reg-user></reg-user>
            </results>
EOT;
    }

    private function createScoUpdateResponse()
    {
        return <<<EOT
        <?xml version="1.0" encoding="utf-8"?>
        <results>
          <status code="ok"/>
          <sco account-id="7" disabled="" display-seq="0" folder-id="383324" icon="content" lang="de" max-retries="" sco-id="412297" source-sco-id="" type="content" version="0">
            <date-created>2014-10-07T11:55:34.443+02:00</date-created>
            <date-modified>2014-10-07T11:55:34.443+02:00</date-modified>
            <name>8f5fc3c612edc4a872f39a5ca9a1b485</name>
            <url-path>/p1z8y56vpp8/</url-path>
          </sco>
        </results>
EOT;
    }

    private function createScoShortcutsResponse()
    {
        return <<<EOT
            <?xml version="1.0" encoding="utf-8"?>
            <results>
                <status code="ok"/>
                <shortcuts>
                    <sco tree-id="69504" sco-id="69512" type="shared-training-templates"><domain-name>$this->apiUrl</domain-name></sco>
                    <sco tree-id="11002" sco-id="11012" type="shared-meeting-templates"><domain-name>$this->apiUrl</domain-name></sco>
                    <sco tree-id="11003" sco-id="383325" type="my-meeting-templates"><domain-name>$this->apiUrl</domain-name></sco>
                    <sco tree-id="11003" sco-id="383324" type="my-meetings"><domain-name>$this->apiUrl</domain-name></sco>
                    <sco tree-id="11001" sco-id="383323" type="my-content"><domain-name>$this->apiUrl</domain-name></sco>
                    <sco tree-id="11000" sco-id="11000" type="content"><domain-name>$this->apiUrl</domain-name></sco>
                    <sco tree-id="69504" sco-id="69504" type="courses"><domain-name>$this->apiUrl</domain-name></sco>
                    <sco tree-id="11001" sco-id="11001" type="user-content"><domain-name>$this->apiUrl</domain-name></sco>
                    <sco tree-id="11002" sco-id="11002" type="meetings"><domain-name>$this->apiUrl</domain-name></sco>
                    <sco tree-id="11003" sco-id="11003" type="user-meetings"><domain-name>$this->apiUrl</domain-name></sco>
                    <sco tree-id="11004" sco-id="11004" type="account-custom"><domain-name>$this->apiUrl</domain-name></sco>
                    <sco tree-id="69503" sco-id="69503" type="user-courses"><domain-name>$this->apiUrl</domain-name></sco>
                    <sco tree-id="69502" sco-id="69502" type="events"><domain-name>$this->apiUrl</domain-name></sco>
                    <sco tree-id="69501" sco-id="69501" type="user-events"><domain-name>$this->apiUrl</domain-name></sco>
                    <sco tree-id="69500" sco-id="69500" type="training-catalog"><domain-name>$this->apiUrl</domain-name></sco>
                    <sco tree-id="11005" sco-id="11005" type="forced-archives"><domain-name>$this->apiUrl</domain-name></sco>
                    <sco tree-id="11006" sco-id="11006" type="chat-transcripts"><domain-name>$this->apiUrl</domain-name></sco>
                </shortcuts>
            </results>
EOT;
    }
}
