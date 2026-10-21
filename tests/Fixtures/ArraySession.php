<?php

namespace PhalconInertia\Tests\Fixtures;

class ArraySession
{
    public $data = [];

    public function get($key, $default = null, $remove = false)
    {
        if (! array_key_exists($key, $this->data)) {
            return $default;
        }
        $value = $this->data[$key];
        if ($remove) {
            unset($this->data[$key]);
        }

        return $value;
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function has($key)
    {
        return array_key_exists($key, $this->data);
    }

    public function remove($key)
    {
        unset($this->data[$key]);
    }
}
