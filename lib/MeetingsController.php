<?php

namespace Meetings;

use Psr\Container\ContainerInterface;
use Meetings\Errors\Error;
use Throwable;

class MeetingsController
{
    /**
     * Der Konstruktor.
     *
     * @param ContainerInterface $container der Dependency Container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * validateFeatureInputs which check inputs againt the original configOptions 
     *  gets the type of configOption value and validate the feature input
     * 
     *  @param array $features input features
     *  @param string $driver_name the name of driver to get the class
     * 
     *  @return array $features (validated -- neccessary for Integers)
     *  @return bool  $is_valid (false) in case something is not right!
     *  @throws 404 Error "Validation failed" reason: Class not found (mostly)
     */
    public function validateFeatureInputs($features, $driver_name) {
        try {
            $is_valid = true;
            $class = 'ElanEv\\Driver\\' . $driver_name;
            if (in_array('ElanEv\Driver\DriverInterface', class_implements($class)) !== false) {
                if ($create_features = $class::getCreateFeatures()) {
                    //loop through create_features 
                    foreach ($create_features as $create_feature_name => $create_feature_contents ) {
                        if (isset($features[$create_feature_name])) {
                            $t = gettype($create_feature_contents->getValue());
                            switch (gettype($create_feature_contents->getValue())) {
                                case "boolean":
                                    $value = filter_var($features[$create_feature_name], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                                    if (is_null($value)) {
                                        $is_valid = false;
                                    }
                                    break;
                                case "integer":
                                    $value = filter_var((int)$features[$create_feature_name], FILTER_VALIDATE_INT);
                                    if (!$value || $value < 1) {
                                        $is_valid = false;
                                    } else {
                                        $features[$create_feature_name] = $value;
                                    }
                                    break;
                                case "array":
                                    if (!array_key_exists($features[$create_feature_name], $create_feature_contents->getValue())) {
                                        $is_valid = false;
                                    }
                                    break;
                                default:
                                    //TOADD: validation on string if neccessary!      
                            }
                        }
                    }
                }
            }
            return $is_valid ? $features : false;
        } catch (Throwable $e) {
            throw new Error(_('Validation failed!'), 404);
        }
    }
}
