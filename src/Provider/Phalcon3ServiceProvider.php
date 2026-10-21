<?php

namespace PhalconInertia\Provider;

use Phalcon\Di\ServiceProviderInterface;
use Phalcon\DiInterface;

class Phalcon3ServiceProvider extends Services implements ServiceProviderInterface
{
    public function register(DiInterface $di)
    {
        $this->registerServices($di);
    }
}
