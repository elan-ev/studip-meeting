<?php

namespace ElanEv\Tests\Driver;

use ElanEv\Driver\BigBlueButtonDriver;
use ElanEv\Driver\DfnVcDriver;
use ElanEv\Driver\DriverFactory;

/**
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class DriverFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \LogicException
     */
    public function testGetDefaultDriverThrowsExceptionForInvalidDefaultDriver()
    {
        $driverFactory = $this->getDriverFactory(array('VC_DRIVER' => 'foo'));
        $driverFactory->getDefaultDriver();
    }

    /**
     * @dataProvider getProperlyConfiguredDrivers
     */
    public function testGetDefaultDriverReturnsDriverIfProperlyConfigured($driver, array $configuration, $expectedClass)
    {
        $configuration['VC_DRIVER'] = $driver;

        $this->assertInstanceOf($expectedClass, $this->getDriverFactory($configuration)->getDefaultDriver());
    }

    /**
     * @dataProvider getNotProperlyConfiguredDrivers
     * @expectedException \LogicException
     */
    public function testGetDefaultDriverThrowsExceptionIfNotProperlyConfigured($driver, array $configuration)
    {
        $configuration['VC_DRIVER'] = $driver;

        $this->getDriverFactory($configuration)->getDefaultDriver();
    }

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
                BigBlueButtonDriver::NAME,
                array(
                    'BBB_URL' => 'http://example.com',
                    'BBB_SALT' => md5(uniqid()),
                ),
                'ElanEv\Driver\BigBlueButtonDriver',
            ),
            'dfn-vc' => array(
                DfnVcDriver::NAME,
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
                BigBlueButtonDriver::NAME,
                array('BBB_SALT' => md5(uniqid())),
            ),
            'big-blue-button-without-salt' => array(
                BigBlueButtonDriver::NAME,
                array(
                    'BBB_URL' => 'http://example.com',
                ),
            ),
            'dfn-vc-without-url' => array(
                DfnVcDriver::NAME,
                array(
                    'DFN_VC_LOGIN' => 'johndoe',
                    'DFN_VC_PASSWORD' => 'password',
                ),
            ),
            'dfn-vc-without-login' => array(
                DfnVcDriver::NAME,
                array(
                    'DFN_VC_URL' => 'http://example.com',
                    'DFN_VC_PASSWORD' => 'password',
                ),
            ),
            'dfn-vc-without-password' => array(
                DfnVcDriver::NAME,
                array(
                    'DFN_VC_URL' => 'http://example.com',
                    'DFN_VC_LOGIN' => 'johndoe',
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
        $config->expects($this->any())
            ->method('getValue')
            ->will($this->returnCallback(function ($option) use ($configuredValues) {
                return isset($configuredValues[$option]) ? $configuredValues[$option] : null;
            }));

        return $config;
    }
}
