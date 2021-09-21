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
            $name, $display_name, $value, $default_value, $info, $attr;

    public function __construct($name, $display_name, $default_value = null, $info = null, $attr = null) {
        $this->name          = $name;
        $this->display_name  = $display_name;
        $this->default_value = $default_value;
        $this->info          = $info;
        $this->attr          = $attr;
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

    public function getInfo() {
        return $this->info;
    }

    public function setInfo($info) {
        $this->info = $info;
    }

    public function getAttribute() {
        return $this->attr;
    }

    public function setAttribute($attr) {
        $this->attr = $attr;
    }

    public function toArray() {
        $values = [];
        if (is_array($this->getValue())) {
            foreach ($this->getValue() as $key => $val) {
                if ( $val instanceof \ElanEv\Driver\ConfigOption) {
                    $values[$key] = $val->toArray();
                } else {
                    $values[$key] = $val;
                }
            }
        } else {
            $values = $this->getValue();
        }
        $result_arr = [];
        $result_arr['name'] = $this->getName();
        $result_arr['display_name'] = $this->getDisplayName();
        $result_arr['value'] = $values;
        !$this->getInfo() ?: $result_arr['info'] = $this->getInfo();
        !$this->getAttribute() ?: $result_arr['attr'] = $this->getAttribute();
        return $result_arr;
    }
}
