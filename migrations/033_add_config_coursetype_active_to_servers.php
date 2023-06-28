<?php

require __DIR__.'/../bootstrap.php';

class AddConfigCoursetypeActiveToServers extends Migration
{

    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return "add new server config params (coursetype & active)";
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        if (Config::get()->VC_CONFIG) {
            $current_config = Config::get()->getValue('VC_CONFIG');
            $current_config = json_decode($current_config, true);
            if ($current_config) {
                foreach ($current_config as $driver_name => $driver_values) {
                    if (isset($driver_values['servers']) && count($driver_values['servers'])) {
                        $driver_servers = [];
                        foreach ($driver_values['servers'] as $server) {
                            if (!isset($server['course_types'])) {
                                $server['course_types'] = "";
                            }
                            if (!isset($server['active'])) {
                                $server['active'] = true;
                            }
                            $driver_servers[] = $server;
                        }
                        $current_config[$driver_name]['servers'] = $driver_servers;
                    }
                }
            }
            $encoded_json = json_encode($current_config);
            if ($encoded_json) {
                Config::get()->store('VC_CONFIG', json_encode($current_config));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        if (Config::get()->VC_CONFIG) {
            $current_config = \Config::get()->getValue('VC_CONFIG');
            $current_config = json_decode($current_config, true);
            if ($current_config) {
                foreach ($current_config as $driver_name => $driver_values) {
                    if (isset($driver_values['servers']) && count($driver_values['servers'])) {
                        $driver_servers = [];
                        foreach ($driver_values['servers'] as $server) {
                            if (isset($server['course_types'])) {
                                unset($server['course_types']);
                            }
                            if (isset($server['active'])) {
                                unset($server['active']);
                            }
                            $driver_servers[] = $server;
                        }
                        $current_config[$driver_name]['servers'] = $driver_servers;
                    }
                }
            }
            $encoded_json = json_encode($current_config);
            if ($encoded_json) {
                Config::get()->store('VC_CONFIG', json_encode($current_config));
            }
        }
    }
}
