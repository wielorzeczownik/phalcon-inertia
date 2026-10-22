<?php

use Phalcon\Di;
use Phalcon\Http\Response;
use PhalconInertia\Inertia;

if (! function_exists('inertia')) {
    /**
     * @param  string|null  $component
     * @return ($component is null ? Inertia : Response)
     */
    function inertia($component = null, array $props = [])
    {
        $di = class_exists('Phalcon\Di\Di') ? Di\Di::getDefault() : Di::getDefault();
        $inertia = $di->getShared('inertia');

        return $component === null ? $inertia : $inertia->render($component, $props);
    }
}
