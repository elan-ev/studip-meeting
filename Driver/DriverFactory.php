<?php

namespace ElanEv\Driver;

use GuzzleHttp\Client;
use ElanEv\Model\Driver;
use Meetings\Errors\Error;
use Meetings\Errors\DriverError;

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
     * @param string $server_index The index of the server to look for
     * @param boolean $checking To indicate the use of the method, whether it is for
     *                      background check for connectivity or it is for real connection
     *
     * @return DriverInterface The driver instance
     *
     * @throws \InvalidArgumentException if the requested driver doesn't exist
     * @throws \LogicException           if a required configuration option
     *                                   is missing
     */
    public function getDriver($driver, $server_index, $checking = false)
    {
        $driver = strtolower($driver);

        if (empty($this->config[$driver])) {
            throw new DriverError(sprintf('The driver "%s" does not exist.', $driver), 404);
        }

        $driver_conf = $this->config[$driver];
        if (!$driver_conf['enable']) {
            throw new DriverError(sprintf('The driver "%s" is not enabled.', $driver), 404);
        }

        //resolve selected server
        $server_num = 0;
        if (isset($driver_conf['servers']) && isset($driver_conf['servers'][$server_index])) {
            $server_num = count($driver_conf['servers']);
            foreach ($driver_conf['servers'][$server_index] as $key => $val) {
                $driver_conf[$key] = $val;
            }
            unset($driver_conf['servers']);
        }

        if (!$driver_conf['active'] && !$checking) {
            $message = ($server_num > 1) ? sprintf('The server %s of "%s" is deactivated.', ($server_index + 1), $driver)
                    : sprintf('The driver "%s" is deactivated.', $driver);
            throw new DriverError($message, 404);
        }

        if (!$driver_conf['url']) {
            throw new DriverError(sprintf('The driver "%s" has not configured the url config option!', $driver), 404);
        }

        $driver_conf['url'] = trim(rtrim($driver_conf['url'], '/'));
        $client_options = [];
        if (isset($driver_conf['proxy'])) {
            $client_options['proxy'] = $driver_conf['proxy'];
        }
        $client = $this->createHttpClient($client_options);
        $class = 'ElanEv\\Driver\\'. $driver_conf['class'];
        return new $class($client, $driver_conf);
    }

    private function createHttpClient($client_options = [])
    {
        return new Client($client_options);
    }
}
