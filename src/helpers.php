<?php

use Phalcon\Di;

if (! function_exists('inertia')) {
    function inertia($component = null, array $props = [])
    {
        $di = class_exists('Phalcon\Di\Di') ? Di\Di::getDefault() : Di::getDefault();
        $inertia = $di->getShared('inertia');

        return $component === null ? $inertia : $inertia->render($component, $props);
    }
}
