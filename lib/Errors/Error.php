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
}
