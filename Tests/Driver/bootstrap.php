<?php

function studip_utf8encode($data)
{
    if (is_array($data)) {
        $new_data = array();
        foreach ($data as $key => $value) {
            $key = studip_utf8encode($key);
            $new_data[$key] = studip_utf8encode($value);
        }
        return $new_data;
    }

    if (!preg_match('/[\200-\377]/', $data) && !preg_match("'&#[0-9]+;'", $data)) {
        return $data;
    } else {
        return mb_decode_numericentity(
            mb_convert_encoding($data,'UTF-8', 'WINDOWS-1252'),
            array(0x100, 0xffff, 0, 0xffff),
            'UTF-8'
        );
    }
}
