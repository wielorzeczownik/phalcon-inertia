<?php

namespace PhalconInertia;

/**
 * An object used as a prop value that turns itself into that value.
 */
interface ProvidesInertiaProperty
{
    /**
     * @return mixed
     */
    public function toInertiaProperty(PropertyContext $prop);
}
