<?php

namespace PhalconInertia\Tests\Fixtures;

use PhalconInertia\PropertyContext;
use PhalconInertia\ProvidesInertiaProperty;

class UserResource implements ProvidesInertiaProperty
{
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function toInertiaProperty(PropertyContext $prop)
    {
        return ['name' => $this->name, 'path' => $prop->key, 'siblings' => array_keys($prop->props)];
    }
}
