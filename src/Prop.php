<?php

namespace PhalconInertia;

/**
 * @method static Prop defer(callable $callback, string $group = 'default', bool $rescue = false) Load the prop right after the page renders
 * @method Prop defer(string $group = 'default') Load this prop right after the page renders, e.g. Prop::scroll($page)->defer()
 */
class Prop
{
    const APPEND = 'append';

    const PREPEND = 'prepend';

    const DEEP = 'deep';

    private $value;

    private $always = false;

    private $optional = false;

    private $group;

    private $merge;

    private $matchOn = [];

    private $rescue = false;

    private $once = false;

    private $onceKey;

    private $expiresAt;

    private $fresh = false;

    private $scroll;

    private function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @param  mixed  $value
     * @return Prop
     */
    public static function always($value)
    {
        $prop = new self($value);
        $prop->always = true;

        return $prop;
    }

    /**
     * @param  callable  $callback
     * @return Prop
     */
    public static function optional($callback)
    {
        $prop = new self($callback);
        $prop->optional = true;

        return $prop;
    }

    /**
     * @param  mixed  $value
     * @return Prop
     */
    public static function value($value)
    {
        return new self($value);
    }

    /**
     * defer() is both a factory, Prop::defer($callback, $group, $rescue), and a modifier,
     * $prop->defer($group). PHP can't declare a static and an instance method with one name.
     *
     * @param  string  $name
     * @param  array  $arguments
     * @return Prop
     */
    public static function __callStatic($name, $arguments)
    {
        if ($name !== 'defer') {
            throw new \BadMethodCallException('Call to undefined method '.__CLASS__.'::'.$name.'()');
        }

        $prop = new self(isset($arguments[0]) ? $arguments[0] : null);
        $prop->rescue = isset($arguments[2]) && $arguments[2];

        return $prop->__call('defer', array_slice($arguments, 1, 1));
    }

    /**
     * @param  string  $name
     * @param  array  $arguments
     * @return $this
     */
    public function __call($name, $arguments)
    {
        if ($name !== 'defer') {
            throw new \BadMethodCallException('Call to undefined method '.__CLASS__.'::'.$name.'()');
        }

        $this->group = isset($arguments[0]) ? (string) $arguments[0] : 'default';

        return $this;
    }

    /**
     * @param  mixed  $value  Phalcon paginator page, or data with the items under $wrapper
     * @param  string  $wrapper
     * @param  array|ProvidesScrollMetadata|callable|null  $metadata  currentPage, previousPage and nextPage, required
     *                                                                without a paginator. A callable gets the value.
     * @return Prop
     */
    public static function scroll($value, $wrapper = 'data', $metadata = null)
    {
        $prop = new self($value);
        $prop->scroll = ['wrapper' => (string) $wrapper, 'metadata' => $metadata];

        return $prop;
    }

    /**
     * @param  mixed  $value
     * @return Prop
     */
    public static function merge($value)
    {
        $prop = new self($value);

        return $prop->append();
    }

    /**
     * @param  mixed  $value
     * @return Prop
     */
    public static function deepMerge($value)
    {
        $prop = new self($value);

        return $prop->deep();
    }

    /**
     * @return $this
     */
    public function append()
    {
        $this->merge = self::APPEND;

        return $this;
    }

    /**
     * @return $this
     */
    public function prepend()
    {
        $this->merge = self::PREPEND;

        return $this;
    }

    /**
     * @return $this
     */
    public function deep()
    {
        $this->merge = self::DEEP;

        return $this;
    }

    /**
     * Merge items that share this field instead of appending duplicates.
     *
     * @param  string  $field
     * @return $this
     */
    public function matchOn($field)
    {
        $this->matchOn[] = (string) $field;

        return $this;
    }

    /**
     * @param  bool  $once
     * @return $this
     */
    public function once($once = true)
    {
        $this->once = (bool) $once;

        return $this;
    }

    /**
     * Share the once cache between props that use the same key.
     *
     * @param  string  $key
     * @return $this
     */
    public function key($key)
    {
        $this->once = true;
        $this->onceKey = (string) $key;

        return $this;
    }

    /**
     * Expire the once prop at a time, after an interval, or after a number of seconds.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $time
     * @return $this
     */
    public function until($time)
    {
        $this->once = true;
        if ($time instanceof \DateInterval) {
            $now = new \DateTime;
            $time = $now->add($time);
        }
        $this->expiresAt = ($time instanceof \DateTimeInterface ? $time->getTimestamp() : time() + (int) $time) * 1000;

        return $this;
    }

    /**
     * Resolve a once prop again even when the client already has it.
     *
     * @param  bool  $fresh
     * @return $this
     */
    public function fresh($fresh = true)
    {
        $this->fresh = (bool) $fresh;

        return $this;
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * @return bool
     */
    public function isAlways()
    {
        return $this->always;
    }

    /**
     * @return bool
     */
    public function isOptional()
    {
        return $this->optional;
    }

    /**
     * @return string|null
     */
    public function group()
    {
        return $this->group;
    }

    /**
     * @return string|null One of APPEND, PREPEND or DEEP
     */
    public function mergeMode()
    {
        return $this->merge;
    }

    /**
     * @return string[]
     */
    public function matchFields()
    {
        return $this->matchOn;
    }

    /**
     * @return bool
     */
    public function shouldRescue()
    {
        return $this->rescue;
    }

    /**
     * @return bool
     */
    public function isOnce()
    {
        return $this->once;
    }

    /**
     * @param  string  $default
     * @return string
     */
    public function onceKey($default)
    {
        return $this->onceKey !== null ? $this->onceKey : (string) $default;
    }

    /**
     * @return int|null Milliseconds since the epoch
     */
    public function expiresAt()
    {
        return $this->expiresAt;
    }

    /**
     * @return bool
     */
    public function isFresh()
    {
        return $this->fresh;
    }

    /**
     * @return array|null
     */
    public function scrollOptions()
    {
        return $this->scroll;
    }
}
