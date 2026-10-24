<?php

use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\Application;
use Phalcon\Mvc\Router;
use Phalcon\Mvc\View;
use Phalcon\Mvc\View\Engine\Volt;
use Phalcon\Session\Adapter\Files;
use Phalcon\Session\Adapter\Stream;
use Phalcon\Session\Manager;
use PhalconInertia\InertiaServiceProvider;
use PhalconInertia\Vite;

foreach (glob(__DIR__.'/controllers/*.php') as $controller) {
    require $controller;
}

$di = new FactoryDefault;
$di->getShared('url')->setBaseUri('/');

$di->setShared('session', function () {
    if (class_exists('Phalcon\Session\Manager')) {
        $session = new Manager;
        $session->setAdapter(new Stream(['savePath' => sys_get_temp_dir()]));
    } else {
        $session = new Files;
    }
    $session->start();

    return $session;
});

$di->setShared('view', function () use ($di) {
    $view = new View;
    $view->setViewsDir(__DIR__.'/views/');
    $view->registerEngines([
        '.volt' => function ($view) use ($di) {
            $volt = new Volt($view, $di);
            // Phalcon 4 renamed compiledPath to path
            $volt->setOptions([interface_exists('Phalcon\DiInterface') ? 'compiledPath' : 'path' => sys_get_temp_dir().'/']);

            return $volt;
        },
    ]);

    return $view;
});

$assetVersion = isset($_COOKIE['asset_version']) ? $_COOKIE['asset_version'] : '1';
$di->register(new InertiaServiceProvider([
    'rootView' => __DIR__.'/views/app.php',
    'publicPath' => __DIR__.'/../public',
    'version' => (new Vite(__DIR__.'/../public'))->version().'-'.$assetVersion,
    'ssr' => getenv('SSR_URL') ?: null,
]));
$di->getShared('inertia')->share([
    'auth' => ['name' => 'Ada'],
    'routes' => function () use ($di) {
        return $di->getShared('routes')->toArray();
    },
]);

$router = new Router(false);
$router->setDefaultNamespace('E2e\Controllers');
$router->add('/', ['controller' => 'users', 'action' => 'index'])->setName('users.index');
$router->addGet('/users/create', ['controller' => 'users', 'action' => 'create'])->setName('users.create');
$router->addPost('/users', ['controller' => 'users', 'action' => 'store'])->setName('users.store');
$router->addGet('/users/{id:[0-9]+}', ['controller' => 'users', 'action' => 'show'])->setName('users.show');
$router->addPut('/users/{id:[0-9]+}', ['controller' => 'users', 'action' => 'update'])->setName('users.update');
$router->addGet('/dashboard', ['controller' => 'demo', 'action' => 'dashboard'])->setName('dashboard');
$router->addPost('/noop', ['controller' => 'demo', 'action' => 'noop'])->setName('noop');
$router->addGet('/admin', ['controller' => 'demo', 'action' => 'admin'])->setName('admin');
$router->addGet('/away', ['controller' => 'demo', 'action' => 'away'])->setName('away');
$router->addGet('/feed', ['controller' => 'demo', 'action' => 'feed'])->setName('feed');
$router->addGet('/jump', ['controller' => 'demo', 'action' => 'jump'])->setName('jump');
$router->notFound(['controller' => 'demo', 'action' => 'notFound']);
$di->setShared('router', $router);

$application = new Application($di);
$application->useImplicitView(false);
$application->setEventsManager($di->getShared('eventsManager'));
$application->handle(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))->send();
