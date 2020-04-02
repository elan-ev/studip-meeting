<?php

namespace Meetings\Errors;

class ErrorCollection
{
    private $errors;

    function add(Error $error)
    {
        $this->errors[] = $error;
    }

    function json()
    {
        $errors = [];

        foreach ($this->errors as $error) {
            $errors[] = [
                'code'   => $error->getCode(),
                'title'  => $error->getMessage(),
                'detail' => $error->getDetails()
            ];
        }

        return json_encode([
            'errors' => $errors
        ]);
    }
}
