<?php

namespace PhalconInertia;

use Phalcon\Http\RequestInterface;

class PropertyContext
{
    /**
     * @var string Dot path of the prop, e.g. "user.avatar"
     */
    public $key;

    /**
     * @var array The props on the same level
     */
    public $props;

    /**
     * @var RequestInterface|null
     */
    public $request;

    public function __construct($key, array $props, $request = null)
    {
        $this->key = $key;
        $this->props = $props;
        $this->request = $request;
    }
}
