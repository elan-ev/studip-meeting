<?php

/**
 * Driver.php - class to manage the registerable drivers
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Affero General Public License
 * version 3 as published by the Free Software Foundation.
 *
 * @author      Till Glöggler <tgloeggl@uos.de>
 * @license     https://www.gnu.org/licenses/agpl-3.0.html AGPL version 3
 */

namespace ElanEv\Model;

use MeetingPlugin;

class Driver
{
    static
        $config;

    static function discover($toArray = false)
    {
        $drivers = array();

        foreach (glob(__DIR__ . '/../../Driver/*.php') as $filename) {
            $class = 'ElanEv\\Driver\\' . substr(basename($filename), 0, -4);
            if (in_array('ElanEv\Driver\DriverInterface', class_implements($class)) !== false) {

                $title          = substr(basename($filename), 0, -4);
                $config_options = $class::getConfigOptions();

                // $config_options[] = new \ElanEv\Driver\ConfigOption('enable', '');

                $drivers[$title] = array(
                    'title'  => $title,
                    'config' => $toArray ? self::convertDriverConfigToArray($config_options) : $config_options
                );
            }
        }

        return $drivers;
    }

    static function loadConfig()
    {
        if (!self::$config) {
            self::$config = json_decode(\Config::get()->getValue('VC_CONFIG'), true);
        }
    }

    static function convertDriverConfigToArray($config_options)
    {
        $array = [];
        foreach ($config_options as $option) {
            $array[] = $option->toArray();
        }
        return $array;
    }

    static function getConfigByDriver($driver_name, $config_options)
    {
        self::loadConfig();

        $new_config = array();

        foreach ($config_options as $config) {
            if ($value = self::$config[$driver_name][0][$config->getName()]) {
                $config->setValue($value);
            }

            $new_config[$config->getName()] = $config;
        }

        return $new_config;
    }

    static function setConfigByDriver($driver_name, $config_options)
    {
        self::loadConfig();

        foreach ($config_options as $key => $value) {
            self::$config[$driver_name][$key] = $value;
        }

        \Config::get()->store('VC_CONFIG', json_encode(self::$config));
    }

    static function getConfig()
    {
        self::loadConfig();

        return self::$config;
    }
}
