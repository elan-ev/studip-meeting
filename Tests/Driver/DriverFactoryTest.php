<?php

namespace ElanEv\Tests;

use ElanEv\Driver\DriverFactory;

/**
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class DriverFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetDriverThrowsExceptionForInvalidDriver()
    {
        $this->getDriverFactory()->getDriver('foo');
    }

    /**
     * @dataProvider getProperlyConfiguredDrivers
     */
    public function testGetDriverReturnsDriverIfProperlyConfigured($driver, array $configuration, $expectedClass)
    {
        $this->assertInstanceOf($expectedClass, $this->getDriverFactory($configuration)->getDriver($driver));
    }

    public function getProperlyConfiguredDrivers()
    {
        return array(
            'big-blue-button' => array(
                DriverFactory::DRIVER_BIG_BLUE_BUTTON,
                array(
                    'BBB_URL' => 'http://example.com',
                    'BBB_SALT' => md5(uniqid()),
                ),
                'ElanEv\Driver\BigBlueButtonDriver',
            ),
            'dfn-vc' => array(
                DriverFactory::DRIVER_DFN_VC,
                array(
                    'DFN_VC_URL' => 'http://example.com',
                    'DFN_VC_LOGIN' => 'johndoe',
                    'DFN_VC_PASSWORD' => 'password',
                ),
                'ElanEv\Driver\DfnVcDriver',
            ),
        );
    }

    /**
     * @dataProvider getNotProperlyConfiguredDrivers
     * @expectedException \LogicException
     */
    public function testGetDriverThrowsExceptionIfNotProperlyConfigured($driver, array $configuration)
    {
        $this->getDriverFactory($configuration)->getDriver($driver);
    }

    public function getNotProperlyConfiguredDrivers()
    {
        return array(
            'big-blue-button-without-url' => array(
                DriverFactory::DRIVER_BIG_BLUE_BUTTON,
                array('BBB_URL' => null),
            ),
            'big-blue-button-without-salt' => array(
                DriverFactory::DRIVER_BIG_BLUE_BUTTON,
                array(
                    'BBB_URL' => 'http://example.com',
                    'BBB_SALT' => null,
                ),
            ),
            'dfn-vc-without-url' => array(
                DriverFactory::DRIVER_DFN_VC,
                array('DFN_VC_URL' => null),
            ),
            'dfn-vc-without-login' => array(
                DriverFactory::DRIVER_DFN_VC,
                array(
                    'DFN_VC_URL' => 'http://example.com',
                    'DFN_VC_LOGIN' => null,
                ),
            ),
            'dfn-vc-without-password' => array(
                DriverFactory::DRIVER_DFN_VC,
                array(
                    'DFN_VC_URL' => 'http://example.com',
                    'DFN_VC_LOGIN' => 'johndoe',
                    'DFN_VC_PASSWORD' => null,
                ),
            ),
        );
    }

    private function getDriverFactory(array $configuredValues = array())
    {
        return new DriverFactory($this->createConfig($configuredValues));
    }

    /**
     * @param array $configuredValues
     *
     * @return \Config
     */
    private function createConfig(array $configuredValues = array())
    {
        $config = $this->getMock('\Config', array('getValue'));

        $i = 0;
        foreach ($configuredValues as $option => $value) {
            $config->expects($this->at($i))
                ->method('getValue')
                ->with($this->equalTo($option))
                ->will($this->returnValue($value));
            $i++;
        }

        return $config;
    }
}
