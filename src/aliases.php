<?php

if (interface_exists('Phalcon\Di\ServiceProviderInterface', false) && ! class_exists('PhalconInertia\InertiaServiceProvider', false)) {
    class_alias(
        interface_exists('Phalcon\DiInterface', false)
            ? 'PhalconInertia\Provider\Phalcon3ServiceProvider'
            : 'PhalconInertia\Provider\Phalcon4ServiceProvider',
        'PhalconInertia\InertiaServiceProvider'
    );
}
