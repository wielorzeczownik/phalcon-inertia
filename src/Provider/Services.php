<?php

namespace PhalconInertia\Provider;

use Phalcon\Events\Manager;
use PhalconInertia\Csrf;
use PhalconInertia\Exception;
use PhalconInertia\Inertia;
use PhalconInertia\Routes;
use PhalconInertia\SsrGateway;
use PhalconInertia\Vite;

abstract class Services
{
    private $options = [
        'rootView' => null,
        'publicPath' => null,
        'version' => null,
        'buildDirectory' => 'build',
        'assetUrl' => '/',
        'routes' => [],
        'csrf' => true,
        'ssr' => null,
        'ssrTimeout' => 2,
    ];

    public function __construct(array $options)
    {
        foreach (['rootView', 'publicPath'] as $required) {
            if (empty($options[$required])) {
                throw new Exception('The Inertia service provider needs the "'.$required.'" option.');
            }
        }

        $this->options = array_merge($this->options, $options);
    }

    protected function registerServices($di)
    {
        $options = $this->options;

        $di->setShared('vite', function () use ($options) {
            return new Vite($options['publicPath'], $options['buildDirectory'], $options['assetUrl']);
        });
        $di->setShared('routes', function () use ($options) {
            return new Routes($options['routes']);
        });
        $di->setShared('csrf', Csrf::class);
        $di->setShared('inertia', function () use ($di, $options) {
            $version = $options['version'] !== null ? $options['version'] : $di->getShared('vite')->version();
            $inertia = new Inertia($options['rootView'], $version);
            if ($options['ssr']) {
                $inertia->setSsr(new SsrGateway($options['ssr'], $options['ssrTimeout']));
            }

            return $inertia;
        });

        if (! $di->has('eventsManager')) {
            $di->setShared('eventsManager', Manager::class);
        }
        $events = $di->getShared('eventsManager');
        $events->attach('application:beforeSendResponse', function ($event, $application, $response) use ($di) {
            return $di->getShared('inertia')->beforeSendResponse($event, $application, $response);
        });
        $events->attach('dispatch:beforeException', function ($event, $dispatcher, $exception) use ($di) {
            return $di->getShared('inertia')->beforeException($event, $dispatcher, $exception);
        });
        if ($options['csrf']) {
            $events->attach('dispatch:beforeExecuteRoute', function ($event, $dispatcher) use ($di) {
                return $di->getShared('csrf')->beforeExecuteRoute($event, $dispatcher);
            });
        }
        $di->getShared('dispatcher')->setEventsManager($events);
    }
}
