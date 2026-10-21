<?php

namespace PhalconInertia\Tests\Fixtures;

class RecordingLogger
{
    public $errors = [];

    public function error($message, array $context = [])
    {
        $this->errors[] = [$message, $context];
    }
}
