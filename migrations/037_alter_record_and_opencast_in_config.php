<?php

class AlterRecordAndOpencastInConfig extends Migration
{

    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return "separate the opencast and record params in config";
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
                foreach ($current_config as $driver_name => $driver_config) {
                    // the driver does not support recording interface
                    if (!isset($driver_config['record'])) {
                       continue;
                    }
                    $record = $driver_config['record'];
                    $opencast = -1;
                    if (isset($driver_config['opencast'])) {
                        $opencast = $driver_config['opencast'];
                    }

                    if ($opencast == '1') {
                        $current_config[$driver_name]['record'] = '0';
                        $current_config[$driver_name]['opencast'] = '1';
                    } else if ($record == '1') {
                        $current_config[$driver_name]['record'] = '1';
                        $opencast == -1 ?: $current_config[$driver_name]['opencast'] = '0';
                    } else {
                        $current_config[$driver_name]['record'] = '0';
                    }
                }
                $encoded_json = json_encode($current_config);
                if ($encoded_json) {
                    Config::get()->store('VC_CONFIG', json_encode($current_config));
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        if (Config::get()->VC_CONFIG) {
            $current_config = Config::get()->getValue('VC_CONFIG');
            $current_config = json_decode($current_config, true);
            if ($current_config) {
                foreach ($current_config as $driver_name => $driver_config) {
                    // the driver does not support recording interface
                    if (!isset($driver_config['record'])) {
                        continue;
                    }
                    $record = $driver_config['record'];
                    $opencast = -1;
                    if (isset($driver_config['opencast'])) {
                        $opencast = $driver_config['opencast'];
                    }

                    if ($opencast == '1') {
                        $current_config[$driver_name]['record'] = '1';
                        $current_config[$driver_name]['opencast'] = '1';
                    } else if ($record == '1') {
                        $current_config[$driver_name]['record'] = '1';
                        $opencast == -1 ?: $current_config[$driver_name]['opencast'] = '0';
                    } else {
                        $current_config[$driver_name]['record'] = '0';
                    }
                    
                }
                $encoded_json = json_encode($current_config);
                if ($encoded_json) {
                    Config::get()->store('VC_CONFIG', json_encode($current_config));
                }
            }
        }
    }
}