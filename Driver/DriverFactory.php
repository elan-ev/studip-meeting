<?php

namespace ElanEv\Driver;

use GuzzleHttp\Client;
use ElanEv\Model\Driver;

/**
 * Creates driver instances based on the application configuration.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class DriverFactory
{
    /*
     * @deprecated
     */
    const DEFAULT_DRIVER_CONFIG_ID = '3c6bfcf5dd3157f53ab1143af1acc899';

    /**
     * @var array
     */
    private $config;

    /**
     * @param \Config $config The application configuration
     */
    public function __construct($config)
    {
        foreach ($config as $driver_class => $driver_cfg) {
            $this->config[strtolower($driver_class)] = $driver_cfg;
            $this->config[strtolower($driver_class)]['class'] = $driver_class;
        }
    }

    /**
     * Returns the application's default driver (according to its
     * configuration).
     *
     * @throws \LogicException when the configured default driver is missing
     *                         or when the default driver is not configured
     *                         properly
     */
    public function getDriverList()
    {
        return $this->config;
    }

    /**
     * Returns the driver for the current application configuration.
     *
     * @param string $driver The name of the driver to use (one of the
     *                       DRIVER_ constants)
     *
     * @return DriverInterface The driver instance
     *
     * @throws \InvalidArgumentException if the requested driver doesn't exist
     * @throws \LogicException           if a required configuration option
     *                                   is missing
     */
    public function getDriver($driver)
    {
        $driver = strtolower($driver);

        if (empty($this->config[$driver])) {
            throw new \InvalidArgumentException(sprintf('The driver "%s" does not exist.', $driver));
        }

        $driver_conf = $this->config[$driver];
        if (!$driver_conf['enable']) {
            throw new \InvalidArgumentException(sprintf('The driver "%s" is not enabled.', $driver));
        }

        if (!$driver_conf['url']) {
            throw new \InvalidArgumentException(sprintf('The driver "%s" has not configured the url config option!', $driver));
        }

        $driver_conf['url'] = rtrim($driver_conf['url'], '/');

        $client = $this->createHttpClient();
        $class = 'ElanEv\\Driver\\'. $driver_conf['class'];
        return new $class($client, $driver_conf);
    }

    private function createHttpClient()
    {
        return new Client();
    }
}
