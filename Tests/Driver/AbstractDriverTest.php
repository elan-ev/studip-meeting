<?php

namespace ElanEv\Tests\Driver;

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
    private $requestsCount = 0;

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

        $this->assertSame($expectedResult, $this->driver->createMeeting($parameters));
        $this->assertSame(count($expectedRequests), $this->requestsCount);
    }

    public abstract function getCreateMeetingData();

    /**
     * @dataProvider getDeleteMeetingData
     */
    public function testDeleteMeeting(MeetingParameters $parameters, array $expectedRequests, $expectedResult)
    {
        foreach ($expectedRequests as $request) {
            $this->validateRequest($request);
        }

        $this->assertSame($expectedResult, $this->driver->deleteMeeting($parameters));
        $this->assertSame(count($expectedRequests), $this->requestsCount);
    }

    public abstract function getDeleteMeetingData();

    /**
     * @dataProvider getGetJoinMeetingUrlData
     */
    public function testGetJoinMeetingUrl(JoinParameters $parameters, array $expectedRequests, $expectedUrl)
    {
        foreach ($expectedRequests as $request) {
            $this->validateRequest($request);
        }

        $this->assertSame($expectedUrl, $this->driver->getJoinMeetingUrl($parameters));
        $this->assertSame(count($expectedRequests), $this->requestsCount);
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

        $requestsCount = &$this->requestsCount;
        $response = $this->createResponseMock(isset($requestData['response']) ? $requestData['response'] : '');
        $request
            ->expects($this->once())
            ->method('send')
            ->will($this->returnCallback(function () use ($response, &$requestsCount) {
                $requestsCount++;

                return $response;
            }));
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
