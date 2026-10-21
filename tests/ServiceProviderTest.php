<?php

namespace PhalconInertia\Tests;

use Phalcon\Mvc\Application;
use PhalconInertia\Exception;
use PhalconInertia\InertiaServiceProvider;
use PhalconInertia\Tests\Fixtures\RecordingCsrf;
use PhalconInertia\Tests\Fixtures\UsersController;

class ServiceProviderTest extends TestCase
{
    public function test_registers_the_services_and_their_options()
    {
        $di = $this->di();
        $di->register(new InertiaServiceProvider([
            'rootView' => __DIR__.'/Fixtures/app.php',
            'publicPath' => __DIR__,
            'version' => 'v9',
            'routes' => [
                'url' => 'https://example.com',
                'groups' => ['none' => ['missing.*']],
                'defaults' => ['locale' => 'en'],
            ],
        ]));

        foreach (['inertia', 'vite', 'routes', 'csrf'] as $service) {
            $this->assertTrue($di->has($service), $service);
        }
        $this->assertSame('https://example.com', $di->getShared('routes')->toArray()['url']);
        $this->assertSame([], $di->getShared('routes')->toArray(['none'])['routes']);
        $this->assertSame(['locale' => 'en'], $di->getShared('routes')->toArray()['defaults']);
        $page = self::pageFromHtml($di->getShared('inertia')->render('Users/Index')->getContent());
        $this->assertSame('v9', $page['version']);
        $this->assertArrayNotHasKey('routes', $page['props']);
    }

    public function test_listeners_reach_the_application_and_dispatcher()
    {
        $write = $this->handle(['REQUEST_METHOD' => 'PUT', 'REQUEST_URI' => '/users/update']);
        $read = $this->handle(['REQUEST_URI' => '/users/index']);

        $this->assertFalse(UsersController::$updated);
        $this->assertSame(419, (int) $write->getStatusCode());
        $this->assertSame('Users/Index', json_decode($read->getContent(), true)['component']);
    }

    public function test_csrf_can_be_turned_off()
    {
        $response = $this->handle(['REQUEST_METHOD' => 'PUT', 'REQUEST_URI' => '/users/update'], ['csrf' => false]);

        $this->assertTrue(UsersController::$updated);
        $this->assertSame(303, (int) $response->getStatusCode());
    }

    public function test_root_view_and_public_path_are_required()
    {
        $this->expectException(Exception::class);

        new InertiaServiceProvider(['rootView' => 'app.php']);
    }

    private function handle(array $server, array $options = [])
    {
        UsersController::$updated = false;
        $di = $this->di(self::inertiaServer($server));
        $di->getShared('dispatcher')->setDefaultNamespace('PhalconInertia\Tests\Fixtures');
        $di->register(new InertiaServiceProvider(array_merge([
            'rootView' => __DIR__.'/Fixtures/app.php',
            'publicPath' => __DIR__,
            'version' => 'v1',
        ], $options)));
        $di->setShared('csrf', RecordingCsrf::class);

        $application = new Application($di);
        $application->useImplicitView(false);
        $application->setEventsManager($di->getShared('eventsManager'));

        return $application->handle($server['REQUEST_URI']);
    }
}
