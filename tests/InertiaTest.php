<?php

namespace PhalconInertia\Tests;

use Phalcon\Http\Response;
use Phalcon\Mvc\View;
use PhalconInertia\Inertia;
use PhalconInertia\Tests\Fixtures\CurrentUser;
use PhalconInertia\Tests\Fixtures\Message;
use PhalconInertia\Tests\Fixtures\RecordingLogger;
use PhalconInertia\Tests\Fixtures\StubSsr;
use PhalconInertia\Tests\Fixtures\StubValidation;
use PhalconInertia\ValidationException;

class InertiaTest extends TestCase
{
    public function test_first_visit_embeds_the_page_in_a_json_script_tag()
    {
        $html = $this->inertia()->render('Users/Index', ['count' => 2])->getContent();
        $page = self::pageFromHtml($html);

        $this->assertHasText('<div id="app"></div>', $html);
        $this->assertHasText('"errors":{}', $html);
        $this->assertSame('Users/Index', $page['component']);
        $this->assertSame(2, $page['props']['count']);
        $this->assertSame('/users', $page['url']);
        $this->assertSame('v1', $page['version']);
    }

    public function test_props_cannot_break_out_of_the_script_tag()
    {
        $payload = '</script><script>alert(1)</script>';
        $html = $this->inertia()->render('Users/Index', ['bio' => $payload])->getContent();

        $this->assertLacksText('<script>alert(1)', $html);
        $this->assertSame(1, substr_count($html, '</script>'));
        $this->assertSame($payload, self::pageFromHtml($html)['props']['bio']);
    }

    public function test_inertia_visit_returns_the_page_as_json()
    {
        $response = $this->inertia(self::inertiaServer())->render('Users/Index', ['count' => 2]);
        $page = json_decode($response->getContent(), true);

        $this->assertSame('true', $response->getHeaders()->get('X-Inertia'));
        $this->assertSame('X-Inertia', $response->getHeaders()->get('Vary'));
        $this->assertHasText('application/json', $response->getHeaders()->get('Content-Type'));
        $this->assertSame(['errors' => [], 'count' => 2], $page['props']);
    }

    public function test_html_response_varies_on_the_inertia_header()
    {
        $response = $this->inertia()->render('Users/Index');

        $this->assertSame('X-Inertia', $response->getHeaders()->get('Vary'));
        $this->assertFalse($response->getHeaders()->get('X-Inertia'));
    }

    public function test_stale_asset_version_forces_a_full_reload_and_keeps_session_data()
    {
        $inertia = $this->inertia(self::inertiaServer(['HTTP_X_INERTIA_VERSION' => 'old']));
        $inertia->withErrors(['email' => 'Required']);

        $response = $inertia->render('Users/Index');

        $this->assertSame(409, (int) $response->getStatusCode());
        $this->assertSame('/users', $response->getHeaders()->get('X-Inertia-Location'));
        $this->assertSame('v1', $response->getHeaders()->get('X-Inertia-Version'));
        $this->assertTrue($inertia->session->has('_inertia_errors'));
    }

    public function test_stale_asset_version_is_ignored_for_non_get_requests()
    {
        $response = $this->inertia(self::inertiaServer(['HTTP_X_INERTIA_VERSION' => 'old', 'REQUEST_METHOD' => 'POST']))
            ->render('Users/Index');

        $this->assertNotSame(409, (int) $response->getStatusCode());
        $this->assertSame('true', $response->getHeaders()->get('X-Inertia'));
    }

    public function test_location_is_a_conflict_for_inertia_and_a_redirect_otherwise()
    {
        $inertiaResponse = $this->inertia(self::inertiaServer())->location('https://example.com/pay');
        $plainResponse = $this->inertia()->location('https://example.com/pay');

        $this->assertSame(409, (int) $inertiaResponse->getStatusCode());
        $this->assertSame('https://example.com/pay', $inertiaResponse->getHeaders()->get('X-Inertia-Location'));
        $this->assertSame(302, (int) $plainResponse->getStatusCode());
        $this->assertSame('https://example.com/pay', $plainResponse->getHeaders()->get('Location'));
    }

    public function test_redirects_after_put_patch_and_delete_become_see_other()
    {
        foreach (['PUT' => 303, 'PATCH' => 303, 'DELETE' => 303, 'POST' => 302] as $method => $expected) {
            $this->assertSame($expected, $this->redirectStatus(self::inertiaServer(['REQUEST_METHOD' => $method])), $method);
        }

        $this->assertSame(302, $this->redirectStatus(['REQUEST_METHOD' => 'PUT']));
    }

    public function test_errors_are_read_from_the_session_once()
    {
        $inertia = $this->inertia(self::inertiaServer());
        $inertia->withErrors(['email' => 'Required']);

        $first = json_decode($inertia->render('Users/Index')->getContent(), true);
        $second = json_decode($inertia->render('Users/Index')->getContent(), true);

        $this->assertSame(['email' => 'Required'], $first['props']['errors']);
        $this->assertSame([], $second['props']['errors']);
    }

    public function test_validation_messages_keep_the_first_message_per_field()
    {
        $inertia = $this->inertia(self::inertiaServer());
        $inertia->withErrors([
            new Message('email', 'Required'),
            new Message('email', 'Invalid'),
            new Message(['from', 'to'], 'Range'),
        ]);

        $page = json_decode($inertia->render('Users/Index')->getContent(), true);

        $this->assertSame(['email' => 'Required', 'from,to' => 'Range'], $page['props']['errors']);
    }

    public function test_validate_returns_the_data_when_it_passes()
    {
        $validation = new StubValidation;

        $this->assertSame(['name' => 'Ada'], $this->inertia()->validate($validation, ['name' => 'Ada']));
        $this->assertSame(['name' => 'Ada'], $validation->validated);
    }

    public function test_validate_stores_errors_in_the_bag_and_throws()
    {
        $inertia = $this->inertia();

        try {
            $inertia->validate(new StubValidation([new Message('email', 'Required')]), [], 'login');
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $e) {
            $this->assertSame(['email' => 'Required'], $e->errors());
            $this->assertSame('login', $e->bag());
        }

        $this->assertSame(['login' => ['email' => 'Required']], $inertia->getDI()->getShared('session')->get(Inertia::ERRORS));
    }

    public function test_precognitive_validate_checks_only_the_listed_fields_and_always_stops()
    {
        $inertia = $this->inertia(['HTTP_PRECOGNITION' => 'true', 'HTTP_PRECOGNITION_VALIDATE_ONLY' => 'email']);
        $validation = new StubValidation([new Message('name', 'Name required'), new Message('email', 'Email invalid')]);

        try {
            $inertia->validate($validation, []);
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $e) {
            $this->assertTrue($e->isPrecognitive());
            $this->assertSame(['email' => 'Email invalid'], $e->errors());
        }

        try {
            $inertia->validate(new StubValidation, ['email' => 'ada@example.com']);
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $e) {
            $this->assertSame([], $e->errors());
        }

        $this->assertNull($inertia->getDI()->getShared('session')->get(Inertia::ERRORS));
    }

    public function test_precognitive_failures_and_successes_get_their_own_responses()
    {
        $inertia = $this->inertia();
        $dispatcher = $inertia->getDI()->getShared('dispatcher');

        $inertia->beforeException(null, $dispatcher, new ValidationException(['email' => 'Email invalid'], 'default', true));
        $failure = $dispatcher->getReturnedValue();
        $inertia->beforeException(null, $dispatcher, new ValidationException([], 'default', true));
        $success = $dispatcher->getReturnedValue();

        $this->assertSame(422, (int) $failure->getStatusCode());
        $this->assertSame('true', $failure->getHeaders()->get('Precognition'));
        $this->assertSame('{"message":"The given data was invalid.","errors":{"email":["Email invalid"]}}', $failure->getContent());
        $this->assertSame(204, (int) $success->getStatusCode());
        $this->assertSame('true', $success->getHeaders()->get('Precognition'));
        $this->assertSame('true', $success->getHeaders()->get('Precognition-Success'));
        $this->assertSame('Precognition', $success->getHeaders()->get('Vary'));
    }

    public function test_before_exception_only_handles_validation_errors()
    {
        $inertia = $this->inertia([], 'v1');
        $dispatcher = $inertia->getDI()->getShared('dispatcher');

        $this->assertTrue($inertia->beforeException(null, $dispatcher, new \RuntimeException('boom')));
        $this->assertNull($dispatcher->getReturnedValue());

        $this->assertFalse($inertia->beforeException(null, $dispatcher, new ValidationException([])));
        $this->assertSame(302, (int) $dispatcher->getReturnedValue()->getStatusCode());
    }

    public function test_rescued_props_are_reported_to_the_logger_service()
    {
        $inertia = $this->inertia(self::inertiaServer([
            'HTTP_X_INERTIA_PARTIAL_COMPONENT' => 'Users/Index',
            'HTTP_X_INERTIA_PARTIAL_DATA' => 'permissions',
        ]));
        $logger = new RecordingLogger;
        $inertia->getDI()->setShared('logger', $logger);
        $exception = new \RuntimeException('down');

        $page = json_decode($inertia->render('Users/Index', [
            'permissions' => Inertia::defer(function () use ($exception) {
                throw $exception;
            }, 'default', true),
        ])->getContent(), true);

        $this->assertSame(['permissions'], $page['rescuedProps']);
        $this->assertSame([['Inertia rescued a deferred prop: down', ['exception' => $exception]]], $logger->errors);
    }

    public function test_error_bag_header_namespaces_the_default_bag()
    {
        $inertia = $this->inertia(self::inertiaServer(['HTTP_X_INERTIA_ERROR_BAG' => 'login']));
        $inertia->withErrors(['email' => 'Required']);

        $page = json_decode($inertia->render('Users/Index')->getContent(), true);

        $this->assertSame(['login' => ['email' => 'Required']], $page['props']['errors']);
    }

    public function test_errors_survive_partial_reloads()
    {
        $inertia = $this->inertia(self::inertiaServer([
            'HTTP_X_INERTIA_PARTIAL_COMPONENT' => 'Users/Index',
            'HTTP_X_INERTIA_PARTIAL_DATA' => 'count',
        ]));

        $page = json_decode($inertia->render('Users/Index', ['count' => 1, 'other' => 2])->getContent(), true);

        $this->assertSame(['errors' => [], 'count' => 1], $page['props']);
    }

    public function test_shared_props_are_merged_and_listed()
    {
        $inertia = $this->inertia(self::inertiaServer());
        $inertia->share('auth', function () {
            return ['id' => 7];
        })->share(['app' => 'demo', 'auth.role' => 'admin']);

        $page = json_decode($inertia->render('Users/Index', ['app' => 'override'])->getContent(), true);

        $this->assertSame(['id' => 7, 'role' => 'admin'], $page['props']['auth']);
        $this->assertSame('override', $page['props']['app']);
        $this->assertSame(['auth', 'app'], $page['sharedProps']);
    }

    public function test_objects_provide_several_props_to_share_and_render()
    {
        $inertia = $this->inertia(self::inertiaServer());
        $inertia->share([new CurrentUser]);

        $page = json_decode($inertia->render('Users/Index', ['user' => 'Grace'])->getContent(), true);
        $rendered = json_decode($this->inertia(self::inertiaServer())->render('Users/Index', [new CurrentUser, 'count' => 1])->getContent(), true);

        $this->assertSame('Grace', $page['props']['user']);
        $this->assertSame('Users/Index', $page['props']['page']);
        $this->assertSame('GET', $page['props']['method']);
        $this->assertSame(['user', 'page', 'method'], $page['sharedProps']);
        $this->assertSame(['errors' => [], 'user' => 'Ada', 'page' => 'Users/Index', 'method' => 'GET', 'count' => 1], $rendered['props']);
    }

    public function test_share_once_shares_a_once_prop()
    {
        $inertia = $this->inertia(self::inertiaServer());
        $inertia->shareOnce('plans', function () {
            return [1];
        })->until(60);

        $page = json_decode($inertia->render('Users/Index')->getContent(), true);

        $this->assertSame([1], $page['props']['plans']);
        $this->assertSame('plans', $page['onceProps']['plans']['prop']);
        $this->assertNotNull($page['onceProps']['plans']['expiresAt']);
    }

    public function test_flash_and_clear_history_apply_to_one_render()
    {
        $inertia = $this->inertia(self::inertiaServer());
        $inertia->flash('status', 'saved')->clearHistory()->preserveFragment();

        $first = json_decode($inertia->render('Users/Index')->getContent(), true);
        $second = json_decode($inertia->render('Users/Index')->getContent(), true);

        $this->assertSame(['status' => 'saved'], $first['flash']);
        $this->assertTrue($first['clearHistory']);
        $this->assertTrue($first['preserveFragment']);
        $this->assertArrayNotHasKey('flash', $second);
        $this->assertArrayNotHasKey('clearHistory', $second);
        $this->assertArrayNotHasKey('preserveFragment', $second);
    }

    public function test_encrypt_history_is_configurable()
    {
        $page = json_decode($this->inertia(self::inertiaServer())->encryptHistory()->render('Users/Index')->getContent(), true);

        $this->assertTrue($page['encryptHistory']);

        $page = json_decode($this->inertia(self::inertiaServer())->preserveBigIntegers()->render('Users/Index')->getContent(), true);

        $this->assertTrue($page['preserveBigIntegers']);
    }

    public function test_ssr_output_replaces_the_client_markup()
    {
        $ssr = new StubSsr(['head' => '<title>Users</title>', 'body' => '<div data-server-rendered="true" id="app">ok</div>']);
        $html = $this->inertia()->setSsr($ssr)->render('Users/Index')->getContent();

        $this->assertHasText('<head><title>Users</title></head>', $html);
        $this->assertHasText('<div data-server-rendered="true" id="app">ok</div>', $html);
        $this->assertLacksText('data-page', $html);
        $this->assertSame('Users/Index', $ssr->pages[0]['component']);
    }

    public function test_failed_ssr_falls_back_to_client_rendering()
    {
        $html = $this->inertia()->setSsr(new StubSsr(null))->render('Users/Index')->getContent();

        $this->assertSame('Users/Index', self::pageFromHtml($html)['component']);
    }

    public function test_inertia_visits_skip_ssr()
    {
        $ssr = new StubSsr(['head' => '', 'body' => '']);
        $this->inertia(self::inertiaServer())->setSsr($ssr)->render('Users/Index');

        $this->assertSame([], $ssr->pages);
    }

    public function test_session_features_require_a_session_service()
    {
        $this->expectException('PhalconInertia\Exception');

        $this->inertia([], 'v1', false)->withErrors(['email' => 'Required']);
    }

    public function test_rendering_works_without_a_session_service()
    {
        $page = self::pageFromHtml($this->inertia([], 'v1', false)->render('Users/Index')->getContent());

        $this->assertSame([], $page['props']['errors']);
    }

    public function test_back_redirects_to_a_same_origin_referer_only()
    {
        $cases = [
            'http://app.test/users/create?x=1' => 'http://app.test/users/create?x=1',
            '/users/create' => '/users/create',
            'https://evil.example/phish' => '/fallback',
            '//evil.example/phish' => '/fallback',
            'javascript:alert(1)' => '/fallback',
            'http://app.test.evil.example/' => '/fallback',
            '' => '/fallback',
        ];

        foreach ($cases as $referer => $expected) {
            $response = $this->inertia(['HTTP_HOST' => 'app.test', 'HTTP_REFERER' => $referer])->back('/fallback');

            $this->assertSame($expected, $response->getHeaders()->get('Location'), 'Referer: '.$referer);
        }
    }

    public function test_empty_response_to_an_inertia_request_redirects_back()
    {
        foreach (['POST' => 302, 'PUT' => 303, 'DELETE' => 303] as $method => $status) {
            $inertia = $this->inertia(self::inertiaServer([
                'REQUEST_METHOD' => $method,
                'HTTP_HOST' => 'app.test',
                'HTTP_REFERER' => 'http://app.test/users',
            ]));
            $response = new Response;
            $inertia->beforeSendResponse(null, null, $response);

            $this->assertSame($status, (int) $response->getStatusCode(), $method);
            $this->assertSame('http://app.test/users', $response->getHeaders()->get('Location'));
        }
    }

    public function test_redirects_with_a_fragment_become_inertia_redirects()
    {
        $response = new Response;
        $response->redirect('/users#top');
        $this->inertia(self::inertiaServer(['REQUEST_METHOD' => 'POST']))->beforeSendResponse(null, null, $response);

        $plain = new Response;
        $plain->redirect('/users#top');
        $this->inertia(['REQUEST_METHOD' => 'POST'])->beforeSendResponse(null, null, $plain);

        $this->assertSame(409, (int) $response->getStatusCode());
        $this->assertSame('/users#top', $response->getHeaders()->get('X-Inertia-Redirect'));
        $this->assertFalse($response->getHeaders()->get('Location'));
        $this->assertSame(302, (int) $plain->getStatusCode());
    }

    public function test_non_empty_or_non_inertia_responses_are_left_alone()
    {
        $page = new Response;
        $page->setContent('{"component":"X"}');
        $this->inertia(self::inertiaServer(['REQUEST_METHOD' => 'POST']))->beforeSendResponse(null, null, $page);

        $plain = new Response;
        $this->inertia(['REQUEST_METHOD' => 'POST'])->beforeSendResponse(null, null, $plain);

        $this->assertSame(0, (int) $page->getStatusCode());
        $this->assertSame(0, (int) $plain->getStatusCode());
        $this->assertFalse($plain->getHeaders()->get('Location'));
    }

    public function test_root_view_can_change_per_request()
    {
        $view = sys_get_temp_dir().'/admin-'.uniqid('', true).'.php';
        file_put_contents($view, '<main data-layout="admin"><?= $body ?></main>');

        $html = $this->inertia()->setRootView($view)->render('Admin/Index')->getContent();
        unlink($view);

        $this->assertHasText('<main data-layout="admin">', $html);
        $this->assertSame('Admin/Index', self::pageFromHtml($html)['component']);
    }

    public function test_root_view_name_renders_through_the_view_service()
    {
        $dir = sys_get_temp_dir().'/views-'.uniqid('', true).'/';
        mkdir($dir);
        file_put_contents($dir.'app.volt', '<title>{{ title }}</title>{{ partial(\'nav\') }}<main data-component="{{ page[\'component\'] }}">{{ head }}{{ body }}</main>');
        file_put_contents($dir.'nav.volt', '<nav>{{ request.getMethod() }}</nav>');

        $inertia = $this->inertia();
        $view = new View;
        $view->setViewsDir($dir);
        $view->registerEngines(['.volt' => 'Phalcon\Mvc\View\Engine\Volt']);
        $view->setDI($inertia->getDI());
        $view->setVar('title', 'Users');
        $inertia->getDI()->setShared('view', $view);

        $html = $inertia->setRootView('app')->render('Users/Index')->getContent();
        array_map('unlink', glob($dir.'*'));
        rmdir($dir);

        $this->assertHasText('<title>Users</title><nav>GET</nav><main data-component="Users/Index">', $html);
        $this->assertSame('Users/Index', self::pageFromHtml($html)['component']);
    }

    public function test_root_view_name_needs_a_view_service()
    {
        $this->expectException('PhalconInertia\Exception');

        $this->inertia()->setRootView('app')->render('Users/Index');
    }

    public function test_error_pages_render_as_inertia_pages_with_their_status()
    {
        $response = $this->inertia(self::inertiaServer())->render('Error', ['status' => 404])->setStatusCode(404, 'Not Found');
        $page = json_decode($response->getContent(), true);

        $this->assertSame(404, (int) $response->getStatusCode());
        $this->assertSame('Error', $page['component']);
    }

    public function test_input_reads_json_form_and_put_bodies()
    {
        $json = $this->inertia(['REQUEST_METHOD' => 'PUT', 'CONTENT_TYPE' => 'application/json']);
        $this->setRawBody($json, '{"name":"Ada","tags":["a"]}');
        $this->assertSame(['name' => 'Ada', 'tags' => ['a']], $json->input());
        $this->assertSame('Ada', $json->input('name'));
        $this->assertSame('none', $json->input('missing', null, 'none'));

        $form = $this->inertia(['REQUEST_METHOD' => 'POST', 'CONTENT_TYPE' => 'application/x-www-form-urlencoded']);
        $_POST = ['name' => 'Linus'];
        $this->assertSame('Linus', $form->input('name'));

        $put = $this->inertia(['REQUEST_METHOD' => 'PATCH', 'CONTENT_TYPE' => 'application/x-www-form-urlencoded']);
        $this->setRawBody($put, 'name=Grace');
        $this->assertSame('Grace', $put->input('name'));
    }

    public function test_input_applies_phalcon_filters_like_get_post()
    {
        $inertia = $this->inertia(['REQUEST_METHOD' => 'POST', 'CONTENT_TYPE' => 'application/json']);
        $this->setRawBody($inertia, '{"name":"  Ada  ","page":"5abc","empty":""}');

        $this->assertSame('Ada', $inertia->input('name', 'trim'));
        $this->assertEquals(5, $inertia->input('page', 'int'));
        $this->assertSame('Ada', $inertia->input('name', ['trim', 'striptags']));
        $this->assertSame('', $inertia->input('empty', 'trim', 'fallback'));
        $this->assertSame('fallback', $inertia->input('empty', 'trim', 'fallback', true));
    }

    public function test_invalid_json_body_reads_as_empty_input()
    {
        $inertia = $this->inertia(['REQUEST_METHOD' => 'POST', 'CONTENT_TYPE' => 'application/json']);
        $this->setRawBody($inertia, '{broken');

        $this->assertSame([], $inertia->input());
    }

    private function setRawBody($inertia, $body)
    {
        $request = $inertia->request;
        foreach (['rawBody', '_rawBody'] as $name) {
            if (property_exists($request, $name)) {
                $property = new \ReflectionProperty($request, $name);
                if (PHP_VERSION_ID < 80100) {
                    $property->setAccessible(true);
                }
                $property->setValue($request, $body);

                return;
            }
        }

        $this->fail('Cannot inject a raw body into '.get_class($request));
    }

    private function redirectStatus(array $server)
    {
        $inertia = $this->inertia($server);
        $response = new Response;
        $response->redirect('/users');
        $inertia->beforeSendResponse(null, null, $response);

        return (int) $response->getStatusCode();
    }
}
