<?php

namespace PhalconInertia\Tests;

use Phalcon\Http\Response;
use PhalconInertia\Inertia;
use PhalconInertia\Testing\InteractsWithInertia;

class AssertableInertiaTest extends TestCase
{
    use InteractsWithInertia;

    public function test_assertions_pass_on_json_and_html_responses()
    {
        $props = ['users' => [['id' => 1, 'name' => 'Ada'], ['id' => 2, 'name' => 'Linus']]];

        foreach ([self::inertiaServer(), []] as $server) {
            $response = $this->inertia($server)->render('Users/Index', $props);

            $this->assertInertia($response, function ($page) {
                $page->component('Users/Index')
                    ->url('/users')
                    ->version('v1')
                    ->has('errors')
                    ->has('users', 2)
                    ->where('users.1.name', 'Linus')
                    ->where('users.0.id', function ($id) {
                        return $id === 1;
                    })
                    ->missing('users.0.password')
                    ->missing('secret');
            });
        }
    }

    public function test_has_scopes_into_objects_and_the_first_item_of_lists()
    {
        $response = $this->inertia(self::inertiaServer())->render('Users/Index', [
            'users' => [['id' => 1, 'name' => 'Ada'], ['id' => 2, 'name' => 'Linus']],
            'meta' => ['total' => 2, 'page' => ['current' => 1, 'last' => 1]],
        ]);

        $this->assertInertia($response, function ($page) {
            $page->has('users', 2, function ($user) {
                $user->where('id', 1)->where('name', 'Ada');
            })->has('users.1', function ($user) {
                $user->where('id', 2)->etc();
            })->has('meta', function ($meta) {
                $meta->where('total', 2)->has('page', function ($current) {
                    $current->where('current', 1)->missing('next')->etc();
                });
            });
        });
    }

    public function test_scopes_fail_on_props_left_unchecked_unless_etc_is_called()
    {
        $page = $this->assertInertia($this->inertia(self::inertiaServer())->render('Users/Index', [
            'users' => [['id' => 1, 'name' => 'Ada']],
        ]));

        $this->assertFails(function () use ($page) {
            $page->has('users.0', function ($user) {
                $user->where('id', 1);
            });
        });
        $this->assertFails(function () use ($page) {
            $page->has('users', 1, function ($user) {
                $user->where('id', 1)->where('name', 'Linus');
            });
        });
        $this->assertFails(function () use ($page) {
            $page->has('users.0.name', function () {});
        });
        $this->assertFails(function () use ($page) {
            $page->has('users', 0, function () {});
        });
    }

    public function test_prop_returns_values_for_further_checks()
    {
        $page = $this->assertInertia($this->inertia(self::inertiaServer())->render('Users/Index', ['count' => 3]));

        $this->assertSame(3, $page->prop('count'));
        $this->assertSame(['errors' => [], 'count' => 3], $page->prop());
        $this->assertSame('Users/Index', $page->toArray()['component']);
    }

    public function test_mismatches_fail_the_test()
    {
        $page = $this->assertInertia($this->inertia(self::inertiaServer())->render('Users/Index', ['count' => 3, 'list' => [1]]));

        $this->assertFails(function () use ($page) {
            $page->component('Other');
        });
        $this->assertFails(function () use ($page) {
            $page->where('count', '3');
        });
        $this->assertFails(function () use ($page) {
            $page->has('list', 2);
        });
        $this->assertFails(function () use ($page) {
            $page->has('nope');
        });
        $this->assertFails(function () use ($page) {
            $page->missing('count');
        });
        $this->assertFails(function () use ($page) {
            $page->where('count', function () {
                return false;
            });
        });
    }

    public function test_non_inertia_responses_fail()
    {
        $response = new Response;
        $response->setContent('<html>plain</html>');

        $this->assertFails(function () use ($response) {
            $this->assertInertia($response);
        });
    }

    public function test_static_aliases_and_helper_match_laravel_style()
    {
        $inertia = $this->inertia(self::inertiaServer());
        $inertia->getDI()->setShared('inertia', $inertia);

        $this->assertInstanceOf('PhalconInertia\Prop', Inertia::defer(function () {
            return 1;
        }, 'aside'));
        $this->assertSame('aside', Inertia::defer(function () {
            return 1;
        }, 'aside')->group());
        $this->assertTrue(Inertia::optional(function () {
            return 1;
        })->isOptional());
        $this->assertTrue(Inertia::always(1)->isAlways());
        $this->assertSame('append', Inertia::merge([])->mergeMode());
        $this->assertSame('deep', Inertia::deepMerge([])->mergeMode());

        $this->assertSame($inertia, inertia());
        $this->assertInertia(inertia('Users/Index', ['count' => 1]), function ($page) {
            $page->component('Users/Index')->where('count', 1);
        });
    }

    private function assertFails($callback)
    {
        $class = class_exists('PHPUnit_Framework_AssertionFailedError')
            ? 'PHPUnit_Framework_AssertionFailedError'
            : 'PHPUnit\Framework\AssertionFailedError';

        try {
            call_user_func($callback);
        } catch (\Exception $e) {
            $this->assertInstanceOf($class, $e);

            return;
        }

        $this->fail('Expected an assertion failure.');
    }
}
