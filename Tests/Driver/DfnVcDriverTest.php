<?php

namespace ElanEv\Tests\Driver;

use ElanEv\Driver\DfnVc;
use ElanEv\Driver\JoinParameters;
use ElanEv\Driver\MeetingParameters;
use Guzzle\Http\ClientInterface;

require_once('bootstrap.php');

/**
 * @author Christian Flothmann <christian.flothmann@uos.de>
 * @author Till Glöggler <tgloeggl@uos.de>
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
        $parameters->setMeetingId(3);
        $parameters->setIdentifier($identifier);
        $parameters->setMeetingName('meeting name');
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
                        'uri' => '/lmsapi/xml?action=sco-update&type=meeting&name=3%20-%20meeting%20name&folder-id=383324&session='.$sessionCookie,
                        'response' => trim($this->createScoUpdateResponse()),
                    ),
                    array(
                        'method' => 'get',
                        'uri' => '/lmsapi/xml?action=permissions-update&acl-id=412297&principal-id=public-access&permission-id=view-hidden&session='.$sessionCookie,
                        'reponse' => '',
                    ),
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
    public function getDeleteMeetingData()
    {
        $parameters = new MeetingParameters();
        $parameters->setRemoteId(383324);
        $sessionCookie = md5(uniqid());

        return array(
            'successful-deletion' => array(
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
                        'uri' => '/lmsapi/xml?action=sco-delete&sco-id=383324&session='.$sessionCookie,
                        'response' => '<?xml version="1.0" encoding="utf-8"?> <results><status code="ok"/></results>',
                    ),
                ),
                true,
            ),
            'delete-non-existing-meeting' => array(
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
                        'uri' => '/lmsapi/xml?action=sco-delete&sco-id=383324&session='.$sessionCookie,
                        'response' => '<?xml version="1.0" encoding="utf-8"?> <results><status code="no-data"/></results> ',
                    ),
                ),
                false,
            ),
            'invalid-login' => array(
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
    public function getGetJoinMeetingUrlData()
    {
        $parameters = new JoinParameters();
        $parameters->setRemoteId(383324);
        $parameters->setEmail('user@example.com');
        $sessionCookie = md5(uniqid());

        return array(
            'successfully-join-as-host' => $this->getRequestsForSuccessfulJoinMeetingUrlCall($parameters, true),
            'successfully-join-as-participant' => $this->getRequestsForSuccessfulJoinMeetingUrlCall($parameters, false),
            'login-failed-when-joining' => array(
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
                null,
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function createDriver(ClientInterface $client)
    {
        return new DfnVc($this->client, array(
            'login'    => $this->login,
            'password' => $this->password
        ));
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

    private function createScoContentsResponse()
    {
        return <<<EOT
            <?xml version="1.0" encoding="utf-8"?>
            <results>
                <status code="ok"/>
                <scos>
                    <sco sco-id="383325" source-sco-id="" folder-id="383324" type="folder" icon="folder" display-seq="0" duration="" is-folder="1">
                        <name>Meine Vorlagen</name>
                        <url-path>/f383325/</url-path>
                        <date-created>2014-08-13T02:05:39.423-07:00</date-created>
                        <date-modified>2014-08-13T02:05:39.423-07:00</date-modified>
                        <is-seminar>false</is-seminar>
                    </sco>
                    <sco sco-id="383324" source-sco-id="" folder-id="383324" type="folder" icon="folder" display-seq="0" duration="" is-folder="1">
                        <name>Meine Vorlagen</name>
                        <url-path>/f383324/</url-path>
                        <date-created>2014-08-13T02:05:39.423-07:00</date-created>
                        <date-modified>2014-08-13T02:05:39.423-07:00</date-modified>
                        <is-seminar>false</is-seminar>
                    </sco>
                </scos>
            </results>
EOT;
    }

    private function createUserExistsWithExistingUserResponse($userId, $email)
    {
        return <<<EOT
            <?xml version="1.0" encoding="utf-8"?>
            <results>
                <status code="ok"/>
                <principal-list>
                    <principal principal-id="$userId">
                        <login>$email</login>
                        <name>Jon Doe</name>
                    </principal>
                </principal-list>
            </results>
EOT;
    }

    private function createUserSessionCookieResponse($userCookie)
    {
        return <<<EOT
            <?xml version="1.0" encoding="utf-8"?>
            <results>
                <status code="ok"/>
                <cookie>$userCookie</cookie>
            </results>
EOT;
    }

    private function getRequestsForSuccessfulJoinMeetingUrlCall(JoinParameters $parameters, $hasModerationPermissions)
    {
        $parameters = clone $parameters;
        $parameters->setHasModerationPermissions($hasModerationPermissions);
        $sessionCookie = md5(uniqid());
        $userSessionCookie = md5(uniqid());
        $permissionId = $hasModerationPermissions ? 'host' : 'view';

        return array(
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
                    'uri' => '/lmsapi/xml?action=lms-user-exists&login=user%40example.com&session='.$sessionCookie,
                    'response' => trim($this->createUserExistsWithExistingUserResponse(12345, 'user@example.com')),
                ),
                array(
                    'method' => 'get',
                    'uri' => '/lmsapi/xml?action=permissions-update&acl-id=383324&principal-id=12345&permission-id='.$permissionId.'&session='.$sessionCookie,
                ),
                array(
                    'method' => 'get',
                    'uri' => '/lmsapi/xml?action=lms-user-login&login=user%40example.com&session='.$sessionCookie,
                    'response' => trim($this->createUserSessionCookieResponse($userSessionCookie)),
                ),
                array(
                    'method' => 'get',
                    'uri' => '/lmsapi/xml?action=sco-contents&sco-id=383324&session='.$sessionCookie,
                    'response' => trim($this->createScoContentsResponse()),
                ),
            ),
            $this->apiUrl.'/f383324/?session='.$userSessionCookie,
        );
    }
}
