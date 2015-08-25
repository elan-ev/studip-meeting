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
            'BigBlueButton' => array(
                'BigBlueButton',
                array(
                    'BigBlueButton' => array(
                        'enable' => true,
                        'url' => 'http://example.com',
                        'api-key' => md5(uniqid())
                    )
                ),
                'ElanEv\Driver\BigBlueButton',
            ),
            'DfnVc' => array(
                'DfnVc',
                array(
                    'DfnVc' => array(
                        'enable' => true,
                        'url' => 'http://example.com',
                        'login' => 'johndoe',
                        'password' => 'password'
                    )
                ),
                'ElanEv\Driver\DfnVc',
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
                'BigBlueButton',
                'BigBlueButton' => array(
                    array('api-key' => md5(uniqid()))
                )
            ),
            'big-blue-button-without-salt' => array(
                'BigBlueButton',
                'BigBlueButton' => array(
                    array(
                        'url' => 'http://example.com',
                    )
                )
            ),
            'dfn-vc-without-url' => array(
                'DfnVc',
                'DfnVc' => array(
                    array(
                        'login' => 'johndoe',
                        'password' => 'password',
                    )
                )
            ),
            'dfn-vc-without-login' => array(
                'DfnVc',
                'DfnVc' => array(
                    array(
                        'url' => 'http://example.com',
                        'password' => 'password',
                    )
                )
            ),
            'dfn-vc-without-password' => array(
                'DfnVc',
                'DfnVc' => array(
                    array(
                        'url' => 'http://example.com',
                        'login' => 'johndoe',
                    )
                )
            ),
        );
    }

    private function getDriverFactory(array $configuredValues = array())
    {
        return new DriverFactory($configuredValues);
    }

    /**
     * @param array $configuredValues
     *
     * @return \Config
     */
    private function createConfig(array $configuredValues = array())
    {
    }
}
