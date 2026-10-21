<?php

namespace PhalconInertia\Provider;

use Phalcon\Di\DiInterface;
use Phalcon\Di\ServiceProviderInterface;

class Phalcon4ServiceProvider extends Services implements ServiceProviderInterface
{
    public function register(DiInterface $di): void
    {
        $this->registerServices($di);
    }
}
