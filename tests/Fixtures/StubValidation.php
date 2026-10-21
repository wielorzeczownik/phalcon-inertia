<?php

namespace PhalconInertia\Tests\Fixtures;

class StubValidation
{
    public $validated;

    private $messages;

    public function __construct(array $messages = [])
    {
        $this->messages = $messages;
    }

    public function validate($data)
    {
        $this->validated = $data;

        return $this->messages;
    }
}
