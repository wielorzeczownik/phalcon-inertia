<?php

namespace PhalconInertia;

class ValidationException extends Exception
{
    private $errors;

    private $bag;

    private $precognitive;

    public function __construct(array $errors, $bag = 'default', $precognitive = false)
    {
        parent::__construct('The given data was invalid.');
        $this->errors = $errors;
        $this->bag = (string) $bag;
        $this->precognitive = (bool) $precognitive;
    }

    public function errors()
    {
        return $this->errors;
    }

    public function bag()
    {
        return $this->bag;
    }

    public function isPrecognitive()
    {
        return $this->precognitive;
    }
}
