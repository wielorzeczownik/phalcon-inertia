<?php

namespace PhalconInertia\Tests;

use PhalconInertia\Routes;

class RoutesTest extends TestCase
{
    public function test_exports_a_ziggy_style_config()
    {
        $config = $this->routes('https://example.com:8080/app')->toArray();

        $this->assertSame('https://example.com:8080/app', $config['url']);
        $this->assertSame(8080, $config['port']);
        $this->assertSame([], $config['defaults']);
        $this->assertSame(['users.show', 'posts.comments', 'old.show', 'admin.users', 'files.show', 'home'], array_keys($config['routes']));
    }

    public function test_route_definitions_carry_uri_methods_parameters_and_wheres()
    {
        $routes = $this->routes()->toArray()['routes'];

        $this->assertSame([
            'uri' => 'users/{id}',
            'methods' => Routes::METHODS,
            'parameters' => ['id'],
        ], $routes['users.show']);
        $this->assertSame([
            'uri' => 'posts/{slug}/comments/{n}',
            'methods' => ['GET', 'POST'],
            'domain' => 'shop.example.com',
            'parameters' => ['slug', 'n'],
            'wheres' => ['slug' => '[a-z-]+', 'n' => '[0-9]+'],
        ], $routes['posts.comments']);
        $this->assertSame(['id' => '[0-9]+'], $routes['old.show']['wheres']);
        $this->assertSame(['uri' => 'admin/users', 'methods' => ['GET']], $routes['admin.users']);
        $this->assertSame(['path' => '.+'], $routes['files.show']['wheres']);
        $this->assertSame('/', $routes['home']['uri']);
    }

    public function test_regex_hostnames_are_left_out()
    {
        $di = $this->di();
        $di->getShared('router')->add('/x', ['controller' => 'x'])->setHostname('([a-z]+).example.com')->setName('x');
        $routes = new Routes(['url' => 'https://example.com']);
        $routes->setDI($di);

        $this->assertArrayNotHasKey('domain', $routes->toArray()['routes']['x']);
    }

    public function test_url_defaults_to_the_current_request_and_base_uri()
    {
        $di = $this->di(['HTTP_HOST' => 'example.com', 'HTTPS' => 'on']);
        $di->getShared('url')->setBaseUri('/app/');
        $routes = new Routes;
        $routes->setDI($di);

        $this->assertSame('https://example.com/app', $routes->toArray()['url']);
    }

    public function test_only_and_except_accept_wildcards()
    {
        $routes = $this->routes();

        $this->assertSame(['admin.users'], array_keys($routes->toArray(['admin.*'])['routes']));
        $this->assertSame(['old.show', 'files.show'], array_keys($routes->toArray(['*.show'], ['users.*'])['routes']));
    }

    public function test_group_names_expand_to_their_patterns()
    {
        $routes = $this->routes(['url' => 'https://example.com/app', 'groups' => ['admin' => ['admin.*'], 'content' => ['posts.*', 'files.*']]]);

        $this->assertSame(['admin.users'], array_keys($routes->toArray(['admin'])['routes']));
        $this->assertSame(['posts.comments', 'admin.users', 'files.show'], array_keys($routes->toArray(['admin', 'content'])['routes']));
        $this->assertSame(['users.show', 'old.show', 'home'], array_keys($routes->toArray([], ['admin', 'content'])['routes']));
        $this->assertHasText('"admin.users"', $routes->types(['admin']));
        $this->assertSame(['admin.users'], array_keys($routes->toArray('admin')['routes']));
    }

    public function test_defaults_are_exported_for_the_route_function()
    {
        $routes = $this->routes(['url' => 'https://example.com/app', 'defaults' => ['locale' => 'en']]);

        $this->assertSame(['locale' => 'en'], $routes->toArray()['defaults']);
        $this->assertHasText('"defaults":{"locale":"en"}', $routes->toJson());
    }

    public function test_configured_filters_apply_until_arguments_override_them()
    {
        $routes = $this->routes(['url' => 'https://example.com/app', 'only' => ['*.show'], 'except' => ['users.*']]);

        $this->assertSame(['old.show', 'files.show'], array_keys($routes->toArray()['routes']));
        $this->assertSame(['admin.users'], array_keys($routes->toArray(['admin.*'], [])['routes']));
        $this->assertCount(6, $routes->toArray([], [])['routes']);
        $this->assertHasText('"files.show"', $routes->script());
        $this->assertLacksText('"users.show"', $routes->types());
    }

    public function test_options_can_come_from_a_phalcon_config()
    {
        $config = class_exists('Phalcon\Config\Config') ? 'Phalcon\Config\Config' : 'Phalcon\Config';
        $routes = $this->routes(new $config([
            'url' => 'https://example.com/app',
            'groups' => ['admin' => ['admin.*']],
            'defaults' => ['locale' => 'en'],
        ]));

        $this->assertSame(['admin.users'], array_keys($routes->toArray('admin')['routes']));
        $this->assertSame(['locale' => 'en'], $routes->toArray()['defaults']);
    }

    public function test_script_is_safe_inside_html()
    {
        $script = $this->routes()->script([], [], '"><x');

        $this->assertHasText('nonce="&quot;&gt;&lt;x"', $script);
        $this->assertHasText('const PhalconRoutes = {"url":"https://example.com/app"', $script);
        $this->assertSame(1, substr_count($script, '</script>'));
    }

    public function test_script_inlines_the_route_function_like_ziggy()
    {
        $this->assertGreaterThan(1, substr_count($this->routes()->script(), 'PhalconRoutes'));
        $this->assertSame(1, substr_count($this->routes()->script([], [], null, false), 'PhalconRoutes'));
    }

    public function test_no_matching_routes_exports_empty_objects()
    {
        $this->assertSame(
            '<script>const PhalconRoutes = {"url":"https://example.com/app","port":null,"defaults":{},"routes":{}};</script>',
            $this->routes()->script(['missing'], [], null, false)
        );
    }

    public function test_types_declare_the_route_list()
    {
        $types = $this->routes()->types(['users.*', 'admin.*']);

        $this->assertHasText("declare module 'phalcon-routes' {", $types);
        $this->assertHasText('"users.show": [{"name":"id","required":true}],', $types);
        $this->assertHasText('"admin.users": [],', $types);
    }

    private function routes($options = 'https://example.com/app')
    {
        $di = $this->di();
        $di->getShared('url')->setBaseUri('/app/');
        $router = $di->getShared('router');
        $router->add('/users/{id}', ['controller' => 'users', 'action' => 'show'])->setName('users.show');
        $router->add('/posts/{slug:[a-z-]+}/comments/{n:[0-9]+}', ['controller' => 'posts', 'action' => 'comments'])
            ->via(['GET', 'POST'])->setHostname('shop.example.com')->setName('posts.comments');
        $router->add('/old/:int', ['controller' => 'old', 'action' => 'show', 'id' => 1])->setName('old.show');
        $router->addGet('/admin/users', ['controller' => 'admin'])->setName('admin.users');
        $router->add('/files/{path:.+}', ['controller' => 'files'])->setName('files.show');
        $router->add('/', ['controller' => 'index'])->setName('home');
        $router->add('/unnamed', ['controller' => 'unnamed']);

        $routes = new Routes(is_string($options) ? ['url' => $options] : $options);
        $routes->setDI($di);

        return $routes;
    }
}
