<?php

namespace PhalconInertia\Tests;

use Phalcon\Events\Manager;
use Phalcon\Mvc\Application;
use PhalconInertia\Inertia;
use PhalconInertia\Tests\Fixtures\RecordingCsrf;
use PhalconInertia\Tests\Fixtures\UsersController;

class ApplicationTest extends TestCase
{
    public function test_controller_renders_an_inertia_page()
    {
        $response = $this->handle('/users/index', self::inertiaServer());
        $page = json_decode($response->getContent(), true);

        $this->assertSame('Users/Index', $page['component']);
        $this->assertSame([['id' => 1]], $page['props']['users']);
    }

    public function test_put_with_token_redirects_with_see_other()
    {
        $response = $this->handle('/users/update', self::inertiaServer(['REQUEST_METHOD' => 'PUT']), true);

        $this->assertTrue(UsersController::$updated);
        $this->assertSame(303, (int) $response->getStatusCode());
    }

    public function test_put_without_token_is_stopped_before_the_controller()
    {
        $response = $this->handle('/users/update', self::inertiaServer(['REQUEST_METHOD' => 'PUT']));

        $this->assertFalse(UsersController::$updated);
        $this->assertSame(419, (int) $response->getStatusCode());
    }

    public function test_failed_validation_redirects_back_with_errors()
    {
        $response = $this->handle('/users/store', self::inertiaServer(['REQUEST_METHOD' => 'POST', 'HTTP_REFERER' => '/users/create']), true, []);

        $this->assertNull(UsersController::$stored);
        $this->assertSame(302, (int) $response->getStatusCode());
        $this->assertSame('/users/create', $response->getHeaders()->get('Location'));
        $this->assertSame(['default' => ['name' => 'The name is required.']], $this->lastDi->getShared('session')->get(Inertia::ERRORS));
    }

    public function test_passing_validation_returns_the_input()
    {
        $response = $this->handle('/users/store', self::inertiaServer(['REQUEST_METHOD' => 'POST']), true, ['name' => 'Ada']);

        $this->assertSame(['name' => 'Ada'], UsersController::$stored);
        $this->assertSame(302, (int) $response->getStatusCode());
    }

    public function test_precognitive_requests_stop_before_the_action_runs()
    {
        $server = ['REQUEST_METHOD' => 'POST', 'HTTP_PRECOGNITION' => 'true', 'HTTP_PRECOGNITION_VALIDATE_ONLY' => 'name'];

        $invalid = $this->handle('/users/store', $server, true, []);
        $valid = $this->handle('/users/store', $server, true, ['name' => 'Ada']);

        $this->assertSame(422, (int) $invalid->getStatusCode());
        $this->assertSame(['name' => ['The name is required.']], json_decode($invalid->getContent(), true)['errors']);
        $this->assertSame(204, (int) $valid->getStatusCode());
        $this->assertNull(UsersController::$stored);
    }

    private $lastDi;

    private function handle($uri, array $server, $withToken = false, array $post = [])
    {
        UsersController::$updated = false;
        UsersController::$stored = null;
        $di = $this->lastDi = $this->di(array_merge($server, ['REQUEST_URI' => $uri]), $post);
        if ($withToken) {
            $di->getShared('session')->set(RecordingCsrf::SESSION_KEY, 'token');
            $_SERVER['HTTP_X_XSRF_TOKEN'] = 'token';
        }

        $di->setShared('inertia', function () {
            return new Inertia(__DIR__.'/Fixtures/app.php', 'v1');
        });
        $di->getShared('dispatcher')->setDefaultNamespace('PhalconInertia\Tests\Fixtures');

        $events = new Manager;
        $events->attach('application:beforeSendResponse', $di->getShared('inertia'));
        $events->attach('dispatch:beforeException', $di->getShared('inertia'));
        $events->attach('dispatch:beforeExecuteRoute', new RecordingCsrf);
        $di->getShared('dispatcher')->setEventsManager($events);

        $application = new Application($di);
        $application->useImplicitView(false);
        $application->setEventsManager($events);

        return $application->handle($uri);
    }
}
