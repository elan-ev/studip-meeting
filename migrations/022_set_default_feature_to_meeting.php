<?php

/**
 * Adds columns to track when records are created and modified.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */

class SetDefaultFeatureToMeeting extends Migration
{
    /**
     * {@inheritdoc}
     */
    function description()
    {
        return 'Set default value for features where using BBB driver to have small room size';
    }

    /**
     * {@inheritdoc}
     */
    function up()
    {
        $default_features = [
            'roomSizeProfiles' => 'small',
            'maxParticipants' => 50,
            'muteOnStart' => 'true',
        ];

        $query = 'UPDATE
                        vc_meetings
                    SET
                        features = :default_feature
                    WHERE
                        driver = :driver_name';
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':default_feature', json_encode($default_features));
        $statement->bindValue(':driver_name', 'BigBlueButton');
        $statement->execute();
        
        SimpleORMap::expireTableScheme();
    }

    /**
     * {@inheritdoc}
     */
    function down()
    {
        $db = DBManager::get();
        $db->exec(
            "UPDATE
              vc_meetings
            SET
              features = null
            WHERE
                driver = 'BigBlueButton'"
        );

        SimpleORMap::expireTableScheme();
    }
}
