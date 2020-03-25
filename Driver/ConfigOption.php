<?php
/**
 * File - description
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Affero General Public License
 * version 3 as published by the Free Software Foundation.
 *
 * @author      Till Glöggler <tgloeggl@uos.de>
 * @license     https://www.gnu.org/licenses/agpl-3.0.html AGPL version 3
 */
namespace ElanEv\Driver;

class ConfigOption
{
    private
            $name, $display_name, $value, $default_value;

    public function __construct($name, $display_name, $default_value = null) {
        $this->name          = $name;
        $this->display_name  = $display_name;
        $this->default_value = $default_value;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDisplayName()
    {
        return $this->display_name;
    }

    public function getValue()
    {
        if (!$this->value) {
            return $this->default_value;
        }

        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function toArray() {
        return [
            'name' => $this->getName(),
            'display_name' => $this->getDisplayName(),
            'value' => $this->getValue()
        ];
    }
}
