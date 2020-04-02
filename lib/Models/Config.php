<?php
namespace Meetings\Models;

use Meetings\RelationshipTrait;
use Meetings\Models\UPMap;
use Meetings\Models\OCSeminarSeries;

class Config extends UPMap
{
    use RelationshipTrait;

    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_config';

        $config['serialized_fields']['config'] = 'JSONArrayObject';

        parent::configure($config);
    }


    /**
     * Return the complete configuration for the passed course
     *
     * @param  string $course_id
     *
     * @return mixed  the configuration data for the passed course
     */
    static function getConfigForCourse($course_id)
    {
        return false;
        static $config;

        if (!$config[$course_id]) {
            $config_id = self::getConfigIdForCourse($course_id);
            if ($config_id) {
                $settings  = \Configuration::instance($config_id);
                $oc_config = self::getBaseServerConf($config_id);

                $config[$course_id] = array_merge($oc_config, $settings->toArray());
            }
        }

        return $config[$course_id];
    }

    /**
     * get id of used config for passed course
     *
     * @param string $course_id
     *
     * @return int
     */
    static function getConfigIdForCourse($course_id)
    {
        return OCSeminarSeries::findOneBySeminar_id($course_id)->config_id;
    }


    /**
     * function getConfigForService  - retries configutation for a given REST-Service-Client
     *
     * @param string $service_type - client label
     *
     * @return array configuration for corresponding client
     *
     */
    public static function getConfigForService($service_type, $config_id = 1)
    {
        if (isset($service_type)) {
            $config = OCEndpoints::findOneBySQL(
                'service_type = ? AND config_id = ?' ,
                [$service_type, $config_id]
            )->toArray();

            if ($config) {
                return $config + self::find($config_id)->toArray();
            } else {
                return [
                    self::empty_config()
                ];
            }

        } else {
            throw new \Exception(_("Es wurde kein Servicetyp angegeben."));
        }
    }

    public function getRelationships()
    {
        return [];
    }
}
