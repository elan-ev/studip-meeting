<?php

namespace ElanEv\Driver;

/**
 * Interface for server roomsize-presets
 *
 * @author Farbod Zamani <zamani@elan-ev.de>
 */
interface ServerRoomsizePresetInterface
{
    /**
     * Returns a roomsize preset template for the server of a driver.
     *
     * @return array an array of configOptions
     */
    public static function getRoomSizePresets();
}
