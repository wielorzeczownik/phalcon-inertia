<?php

namespace PhalconInertia;

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

    public static function always($value)
    {
        $prop = new self($value);
        $prop->always = true;

        return $prop;
    }

    public static function optional($callback)
    {
        $prop = new self($callback);
        $prop->optional = true;

        return $prop;
    }

    public static function value($value)
    {
        return new self($value);
    }

    public static function defer($callback, $group = 'default', $rescue = false)
    {
        $prop = new self($callback);
        $prop->group = (string) $group;
        $prop->rescue = (bool) $rescue;

        return $prop;
    }

    public static function scroll($value, $wrapper = 'data', $metadata = null)
    {
        $prop = new self($value);
        $prop->scroll = ['wrapper' => (string) $wrapper, 'metadata' => $metadata];

        return $prop;
    }

    public static function merge($value)
    {
        $prop = new self($value);

        return $prop->append();
    }

    public static function deepMerge($value)
    {
        $prop = new self($value);

        return $prop->deep();
    }

    public function append()
    {
        $this->merge = self::APPEND;

        return $this;
    }

    public function prepend()
    {
        $this->merge = self::PREPEND;

        return $this;
    }

    public function deep()
    {
        $this->merge = self::DEEP;

        return $this;
    }

    public function matchOn($field)
    {
        $this->matchOn[] = (string) $field;

        return $this;
    }

    public function once($once = true)
    {
        $this->once = (bool) $once;

        return $this;
    }

    public function key($key)
    {
        $this->once = true;
        $this->onceKey = (string) $key;

        return $this;
    }

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

    public function fresh($fresh = true)
    {
        $this->fresh = (bool) $fresh;

        return $this;
    }

    public function get()
    {
        return $this->value;
    }

    public function isAlways()
    {
        return $this->always;
    }

    public function isOptional()
    {
        return $this->optional;
    }

    public function group()
    {
        return $this->group;
    }

    public function mergeMode()
    {
        return $this->merge;
    }

    public function matchFields()
    {
        return $this->matchOn;
    }

    public function shouldRescue()
    {
        return $this->rescue;
    }

    public function isOnce()
    {
        return $this->once;
    }

    public function onceKey($default)
    {
        return $this->onceKey !== null ? $this->onceKey : (string) $default;
    }

    public function expiresAt()
    {
        return $this->expiresAt;
    }

    public function isFresh()
    {
        return $this->fresh;
    }

    public function scrollOptions()
    {
        return $this->scroll;
    }
}
