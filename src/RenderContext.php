<?php

namespace PhalconInertia;

use Phalcon\Http\RequestInterface;

class RenderContext
{
    /**
     * @var string
     */
    public $component;

    /**
     * @var RequestInterface|null
     */
    public $request;

    public function __construct($component, $request = null)
    {
        $this->component = $component;
        $this->request = $request;
    }
}
