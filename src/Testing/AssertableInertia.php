<?php

namespace PhalconInertia\Testing;

class AssertableInertia
{
    private $page;

    private $props;

    private $path;

    private $interacted = [];

    private $etc = false;

    private function __construct(array $page, $props = null, $path = null)
    {
        $this->page = $page;
        $this->props = $props === null ? $page['props'] : $props;
        $this->path = $path;
    }

    public static function fromResponse($response)
    {
        $content = (string) $response->getContent();

        if ($response->getHeaders()->get('X-Inertia') === 'true') {
            $page = json_decode($content, true);
        } else {
            $page = null;
            if (preg_match('#<script data-page="app" type="application/json">(.*?)</script>#s', $content, $match)) {
                $page = json_decode($match[1], true);
            }
        }

        self::assert('assertTrue', [is_array($page) && isset($page['component']), 'Not an Inertia response: '.substr($content, 0, 200)]);

        return new self($page);
    }

    public function component($name)
    {
        self::assert('assertSame', [$name, $this->page['component'], 'Unexpected Inertia component.']);

        return $this;
    }

    public function url($url)
    {
        self::assert('assertSame', [$url, $this->page['url'], 'Unexpected Inertia page URL.']);

        return $this;
    }

    public function version($version)
    {
        self::assert('assertSame', [(string) $version, (string) $this->page['version'], 'Unexpected Inertia asset version.']);

        return $this;
    }

    public function has($key, $count = null, $callback = null)
    {
        if ($count instanceof \Closure) {
            $callback = $count;
            $count = null;
        }

        $value = $this->find($key, $found);
        self::assert('assertTrue', [$found, 'Inertia prop ['.$this->dotted($key).'] does not exist.']);

        if ($count !== null) {
            self::assert('assertTrue', [is_array($value), 'Inertia prop ['.$this->dotted($key).'] is not countable.']);
            self::assert('assertSame', [$count, count($value), 'Inertia prop ['.$this->dotted($key).'] does not have the expected size.']);
        }

        if ($callback !== null) {
            if ($count !== null) {
                self::assert('assertNotEmpty', [$value, 'Inertia prop ['.$this->dotted($key).'] is empty, so it has no first item to scope.']);
                $first = key($value);
                $key .= '.'.$first;
                $value = $value[$first];
            }
            $this->scope($key, $value, $callback);
        }

        return $this;
    }

    public function etc()
    {
        $this->etc = true;

        return $this;
    }

    public function where($key, $expected)
    {
        $value = $this->find($key, $found);
        self::assert('assertTrue', [$found, 'Inertia prop ['.$this->dotted($key).'] does not exist.']);

        if ($expected instanceof \Closure) {
            self::assert('assertTrue', [call_user_func($expected, $value) === true, 'Inertia prop ['.$this->dotted($key).'] was marked as invalid by the callback.']);
        } else {
            self::assert('assertSame', [$expected, $value, 'Inertia prop ['.$this->dotted($key).'] does not match the expected value.']);
        }

        return $this;
    }

    public function missing($key)
    {
        $this->find($key, $found);
        self::assert('assertFalse', [$found, 'Inertia prop ['.$this->dotted($key).'] was found while it was expected to be missing.']);

        return $this;
    }

    public function prop($key = null)
    {
        if ($key === null) {
            return $this->props;
        }

        return $this->find($key, $found);
    }

    public function toArray()
    {
        return $this->page;
    }

    private function scope($key, $value, $callback)
    {
        self::assert('assertTrue', [is_array($value), 'Inertia prop ['.$this->dotted($key).'] is not an array or object, so it cannot be scoped.']);

        $scope = new self($this->page, $value, $this->dotted($key));
        call_user_func($callback, $scope);

        $unexpected = array_diff(array_map('strval', array_keys($value)), $scope->interacted);
        self::assert('assertTrue', [
            $scope->etc || ! $unexpected,
            'Unexpected Inertia props were found in scope ['.$scope->path.']: '.implode(', ', $unexpected).'. Assert them or call etc().',
        ]);
    }

    private function dotted($key)
    {
        return $this->path === null ? $key : $this->path.'.'.$key;
    }

    private function find($key, &$found)
    {
        $segments = explode('.', $key);
        $this->interacted[] = $segments[0];
        $value = $this->props;
        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                $found = false;

                return null;
            }
            $value = $value[$segment];
        }
        $found = true;

        return $value;
    }

    private static function assert($method, array $arguments)
    {
        $class = class_exists('PHPUnit_Framework_Assert') ? 'PHPUnit_Framework_Assert' : 'PHPUnit\Framework\Assert';
        call_user_func_array([$class, $method], $arguments);
    }
}
