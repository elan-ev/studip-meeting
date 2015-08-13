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

class Driver
{
    static
        $config;

    static function discover()
    {
        $drivers = array();

        foreach (glob(__DIR__ . '/../../Driver/*.php') as $filename) {
            $class = 'ElanEv\\Driver\\' . substr(basename($filename), 0, -4);
            if (in_array('ElanEv\Driver\DriverInterface', class_implements($class)) !== false) {
                
                $title          = substr(basename($filename), 0, -10);
                $config_options = $class::getConfigOptions();

                array_unshift($config_options, new \ElanEv\Driver\ConfigOption(
                        'display_name', _('Anzeigename'), $title)
                );

                $config_options[] = new \ElanEv\Driver\ConfigOption('enable', '');

                $drivers[$title] = array(
                    'title'  => $title,
                    'config' => self::getConfigByDriver($title, $config_options)
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

    static function getConfigByDriver($driver_name, $config_options)
    {
        self::loadConfig();

        $new_config = array();

        foreach ($config_options as $config) {
            if ($value = self::$config[$driver_name][$config->getName()]) {
                $config->setValue($value);
            }

            $new_config[$config->getName()] = $config;
        }

        return $new_config;
    }

    static function setConfigByDriver($driver_name, $config_options)
    {
        self::loadConfig();

        foreach ($config_options as $config) {
            self::$config[$driver_name][$config->getName()] = $config->getValue();
        }

        \Config::get()->store('VC_CONFIG', json_encode(self::$config));
    }

    static function getConfig()
    {
        self::loadConfig();

        return self::$config;
    }
}
