<?php

namespace Meetings\Errors;

use RuntimeException;

class Error extends RuntimeException
{
    private
        $details;

    function __construct($message, $code, $details = null)
    {
        $this->message  = $message;
        $this->code = $code;

        if (!is_null($details)) {
            $this->details = $details;
        } else {
            $this->details = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        }

    }

    public function getDetails()
    {
        return $this->details;
    }

    public function clearDetails()
    {
        $this->details = null;
    }

    public function getDetailedMessage()
    {
        $details = '';
        if (!empty($this->details)) {
            if (is_array($this->details)) {
                $details = ' in: ' . $this->details[0]['file'] . ' line: ' . $this->details[0]['line'];
            } else {
                $details = (string) $this->details;
            }
        }
        return $this->code . ': ' . $this->message . $details;
    }

    public function getJson()
    {
        return json_encode([
            'errors' => [
                [
                    'code'   => $this->code,
                    'title'  => $this->message,
                    'detail' => $this->details
                ]
            ]
        ]);
    }
}
