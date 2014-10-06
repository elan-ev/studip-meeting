<?php

namespace ElanEv\Tests;

use ElanEv\Driver\JoinParameters;
use ElanEv\Driver\MeetingParameters;
use Guzzle\Http\ClientInterface;

/**
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
abstract class AbstractDriverTest extends \PHPUnit_Framework_TestCase
{
    protected $apiUrl = 'http://example.com';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Guzzle\Http\Client
     */
    protected $client;

    /**
     * @var \ElanEv\Driver\DriverInterface
     */
    protected $driver;

    private $getIndex = 0;
    private $postIndex = 0;

    protected function setUp()
    {
        $this->client = $this->createClientMock();
        $this->driver = $this->createDriver($this->client);
    }

    /**
     * @dataProvider getCreateMeetingData
     */
    public function testCreateMeeting(MeetingParameters $parameters, array $expectedRequests, $expectedResult)
    {
        foreach ($expectedRequests as $request) {
            $this->validateRequest($request);
        }

        $this->assertEquals($expectedResult, $this->driver->createMeeting($parameters));
    }

    public abstract function getCreateMeetingData();

    /**
     * @dataProvider getIsMeetingRunningData
     */
    public function testIsMeetingRunning($meetingId, array $expectedRequests, $expectedResult)
    {
        foreach ($expectedRequests as $request) {
            $this->validateRequest($request);
        }

        $this->assertEquals($expectedResult, $this->driver->isMeetingRunning($meetingId));
    }

    public abstract function getIsMeetingRunningData();

    /**
     * @dataProvider getGetJoinMeetingUrlData
     */
    public function testGetJoinMeetingUrl(JoinParameters $parameters, $expectedUrl)
    {
        $this->assertEquals($expectedUrl, $this->driver->getJoinMeetingUrl($parameters));
    }

    public abstract function getGetJoinMeetingUrlData();

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Guzzle\Http\ClientInterface
     */
    protected function createClientMock()
    {
        $client = $this->getMock('\Guzzle\Http\Client');
        $client
            ->expects($this->any())
            ->method('getBaseUrl')
            ->will($this->returnValue($this->apiUrl));

        return $client;
    }

    abstract protected function createDriver(ClientInterface $client);

    private function validateRequest(array $requestData)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->createRequestMock();

        switch ($requestData['method']) {
            case 'get':
                $this
                    ->client
                    ->expects($this->at($this->getIndex++))
                    ->method('get')
                    ->with($requestData['uri'])
                    ->will($this->returnValue($request));
                break;
            case 'post':
                $this
                    ->client
                    ->expects($this->at($this->postIndex++))
                    ->method('post')
                    ->with($requestData['uri'])
                    ->will($this->returnValue($request));
                break;
        }

        $request
            ->expects($this->once())
            ->method('send')
            ->will($this->returnValue($this->createResponseMock(isset($requestData['response']) ? $requestData['response'] : '')));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Guzzle\Http\Message\RequestInterface
     */
    private function createRequestMock()
    {
        return $this->getMock('\Guzzle\Http\Message\RequestInterface');
    }

    /**
     * @param string $body
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\Guzzle\Http\Message\Response
     */
    private function createResponseMock($body)
    {
        $response = $this
            ->getMockBuilder('\Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();
        $response
            ->expects($this->any())
            ->method('getBody')
            ->will($this->returnValue($body));

        return $response;
    }
}
