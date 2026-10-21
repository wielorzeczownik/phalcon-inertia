<?php

namespace PhalconInertia\Tests;

use Phalcon\Paginator\Adapter\NativeArray;
use PhalconInertia\Page;
use PhalconInertia\Prop;

class PageTest extends TestCase
{
    public function test_empty_props_encode_as_an_object()
    {
        $this->assertSame('{}', json_encode($this->build([])['props']));
    }

    public function test_closures_resolve_recursively()
    {
        $page = $this->build(['user' => function () {
            return ['name' => function () {
                return 'Ada';
            }];
        }]);

        $this->assertSame('{"user":{"name":"Ada"}}', json_encode($page['props']));
    }

    public function test_optional_and_deferred_props_are_skipped_on_first_load_without_being_called()
    {
        $calls = 0;
        $expensive = function () use (&$calls) {
            $calls++;

            return 1;
        };

        $page = $this->build([
            'plain' => 1,
            'optional' => Prop::optional($expensive),
            'stats' => Prop::defer($expensive),
            'sidebar' => Prop::defer($expensive, 'aside'),
        ]);

        $this->assertSame(0, $calls);
        $this->assertSame(['plain'], array_keys((array) $page['props']));
        $this->assertSame(['default' => ['stats'], 'aside' => ['sidebar']], $page['deferredProps']);
    }

    public function test_partial_reload_returns_requested_and_always_props_only()
    {
        $calls = 0;
        $page = $this->build([
            'a' => 1,
            'b' => function () use (&$calls) {
                $calls++;

                return 2;
            },
            'c' => Prop::always(3),
            'o' => Prop::optional(function () {
                return 4;
            }),
            'd' => Prop::defer(function () {
                return 5;
            }),
        ], $this->partial(['X-Inertia-Partial-Data' => 'a, o,d']));

        $this->assertSame(['a' => 1, 'c' => 3, 'o' => 4, 'd' => 5], (array) $page['props']);
        $this->assertSame(0, $calls);
        $this->assertArrayNotHasKey('deferredProps', $page);
    }

    public function test_partial_except_removes_keys_even_when_requested()
    {
        $page = $this->build(
            ['a' => 1, 'b' => 2, 'c' => Prop::always(3)],
            $this->partial(['X-Inertia-Partial-Data' => 'a,b,c', 'X-Inertia-Partial-Except' => 'b,c'])
        );

        $this->assertSame(['a' => 1, 'c' => 3], (array) $page['props']);
    }

    public function test_partial_headers_for_another_component_are_ignored()
    {
        $page = $this->build(
            ['a' => 1, 'b' => 2, 'o' => Prop::optional(function () {
                return 3;
            })],
            ['X-Inertia-Partial-Component' => 'Other', 'X-Inertia-Partial-Data' => 'a']
        );

        $this->assertSame(['a' => 1, 'b' => 2], (array) $page['props']);
    }

    public function test_merge_metadata_lists_included_props_and_skips_reset_ones()
    {
        $page = $this->build([
            'posts' => Prop::merge([1]),
            'feed' => Prop::merge([1])->prepend(),
            'settings' => Prop::deepMerge(['a' => 1]),
            'users' => Prop::merge([])->matchOn('id'),
            'reset' => Prop::merge([9]),
            'plain' => [1],
        ], ['X-Inertia-Reset' => 'reset']);

        $this->assertSame(['posts', 'users'], $page['mergeProps']);
        $this->assertSame(['feed'], $page['prependProps']);
        $this->assertSame(['settings'], $page['deepMergeProps']);
        $this->assertSame(['users.id'], $page['matchPropsOn']);
        $this->assertSame([9], $page['props']->reset);
    }

    public function test_deferred_props_can_merge_once_loaded()
    {
        $prop = Prop::defer(function () {
            return [1];
        })->append();

        $first = $this->build(['comments' => $prop]);
        $partial = $this->build(['comments' => $prop], $this->partial(['X-Inertia-Partial-Data' => 'comments']));

        $this->assertArrayNotHasKey('mergeProps', $first);
        $this->assertSame(['comments'], $partial['mergeProps']);
    }

    public function test_history_flags_are_present_only_when_true()
    {
        $default = $this->build([]);
        $flagged = $this->build([], [], ['encryptHistory' => true, 'clearHistory' => true]);

        $this->assertArrayNotHasKey('encryptHistory', $default);
        $this->assertArrayNotHasKey('clearHistory', $default);
        $this->assertTrue($flagged['encryptHistory']);
        $this->assertTrue($flagged['clearHistory']);
    }

    public function test_shared_props_and_flash_are_present_only_when_set()
    {
        $empty = $this->build([], [], ['sharedProps' => [], 'flash' => []]);
        $set = $this->build([], [], ['sharedProps' => ['auth'], 'flash' => ['status' => 'saved']]);

        $this->assertArrayNotHasKey('sharedProps', $empty);
        $this->assertArrayNotHasKey('flash', $empty);
        $this->assertSame(['auth'], $set['sharedProps']);
        $this->assertSame('{"status":"saved"}', json_encode($set['flash']));
    }

    public function test_rescued_deferred_props_are_left_out_and_listed()
    {
        $reported = [];
        $page = $this->build([
            'stats' => Prop::defer(function () {
                return 1;
            }),
            'permissions' => Prop::defer(function () {
                throw new \RuntimeException('down');
            }, 'default', true),
        ], $this->partial(['X-Inertia-Partial-Data' => 'stats,permissions']), ['report' => function ($e) use (&$reported) {
            $reported[] = $e->getMessage();
        }]);

        $this->assertSame('{"stats":1}', json_encode($page['props']));
        $this->assertSame(['permissions'], $page['rescuedProps']);
        $this->assertSame(['down'], $reported);
    }

    public function test_deferred_props_without_rescue_still_throw()
    {
        $this->expectException('RuntimeException');

        $this->build(['stats' => Prop::defer(function () {
            throw new \RuntimeException('down');
        })], $this->partial(['X-Inertia-Partial-Data' => 'stats']));
    }

    public function test_once_props_are_skipped_when_the_client_has_them()
    {
        $calls = 0;
        $plans = function () use (&$calls) {
            $calls++;

            return ['basic'];
        };

        $first = $this->build(['plans' => Prop::value($plans)->once()]);
        $again = $this->build(['plans' => Prop::value($plans)->once()], ['X-Inertia-Except-Once-Props' => 'plans']);
        $fresh = $this->build(['plans' => Prop::value($plans)->once()->fresh()], ['X-Inertia-Except-Once-Props' => 'plans']);

        $this->assertSame('{"plans":["basic"]}', json_encode($first['props']));
        $this->assertSame('{"plans":{"prop":"plans","expiresAt":null}}', json_encode($first['onceProps']));
        $this->assertSame('{}', json_encode($again['props']));
        $this->assertSame('{"plans":{"prop":"plans","expiresAt":null}}', json_encode($again['onceProps']));
        $this->assertSame('{"plans":["basic"]}', json_encode($fresh['props']));
        $this->assertSame(2, $calls);
    }

    public function test_once_props_take_a_custom_key_and_an_expiry()
    {
        $page = $this->build(['plans' => Prop::value(1)->key('billing')->until(60)]);
        $expiresAt = $page['onceProps']->billing['expiresAt'];

        $this->assertSame('plans', $page['onceProps']->billing['prop']);
        $this->assertGreaterThanOrEqual((time() + 59) * 1000, $expiresAt);
        $this->assertLessThanOrEqual((time() + 61) * 1000, $expiresAt);
    }

    public function test_partial_reloads_ignore_loaded_once_props_and_deferred_once_props_load_once()
    {
        $prop = Prop::defer(function () {
            return 1;
        })->once();

        $partial = $this->build(['plans' => $prop], $this->partial(['X-Inertia-Partial-Data' => 'plans', 'X-Inertia-Except-Once-Props' => 'plans']));
        $visit = $this->build(['plans' => $prop], ['X-Inertia-Except-Once-Props' => 'plans']);

        $this->assertSame('{"plans":1}', json_encode($partial['props']));
        $this->assertArrayNotHasKey('deferredProps', $visit);
    }

    public function test_scroll_props_read_a_phalcon_paginator_page()
    {
        $adapter = new NativeArray(['data' => [1, 2, 3, 4, 5], 'limit' => 2, 'page' => 2]);
        $paginated = method_exists($adapter, 'paginate') ? $adapter->paginate() : $adapter->getPaginate();

        $page = $this->build(['posts' => Prop::scroll($paginated)]);

        $this->assertSame('{"posts":{"data":[3,4]}}', json_encode($page['props']));
        $this->assertSame(['posts.data'], $page['mergeProps']);
        $this->assertSame(
            '{"posts":{"pageName":"page","previousPage":1,"nextPage":3,"currentPage":2,"reset":false}}',
            json_encode($page['scrollProps'])
        );
    }

    public function test_scroll_props_follow_the_merge_intent_and_reset()
    {
        $prop = Prop::scroll(['items' => [1]], 'items', ['currentPage' => 3, 'previousPage' => 2]);

        $prepend = $this->build(['feed' => $prop], ['X-Inertia-Infinite-Scroll-Merge-Intent' => 'prepend']);
        $reset = $this->build(['feed' => $prop], ['X-Inertia-Reset' => 'feed']);

        $this->assertSame(['feed.items'], $prepend['prependProps']);
        $this->assertArrayNotHasKey('mergeProps', $prepend);
        $this->assertArrayNotHasKey('mergeProps', $reset);
        $this->assertTrue($reset['scrollProps']->feed['reset']);
        $this->assertSame(['pageName' => 'page', 'previousPage' => 2, 'nextPage' => null, 'currentPage' => 3, 'reset' => false], $prepend['scrollProps']->feed);
    }

    public function test_scroll_props_need_a_paginator_or_metadata()
    {
        $this->expectException('PhalconInertia\Exception');

        $this->build(['feed' => Prop::scroll([1, 2])]);
    }

    private function build(array $props, array $headers = [], array $options = [])
    {
        return Page::build('Users/Index', $props, '/users', 'v1', $headers, $options);
    }

    private function partial(array $headers)
    {
        return array_merge(['X-Inertia-Partial-Component' => 'Users/Index'], $headers);
    }
}
