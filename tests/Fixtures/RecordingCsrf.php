<?php

namespace PhalconInertia\Tests\Fixtures;

use PhalconInertia\Csrf;

class RecordingCsrf extends Csrf
{
    public $cookies = [];

    protected function sendCookie($name, $value)
    {
        $this->cookies[] = [$name, $value];
    }

    public function arguments($name, $value)
    {
        return $this->cookieArguments($name, $value);
    }
}
