<?php

namespace PhalconInertia\Tests\Fixtures;

use PhalconInertia\ProvidesInertiaProperties;
use PhalconInertia\RenderContext;

class CurrentUser implements ProvidesInertiaProperties
{
    public function toInertiaProperties(RenderContext $context)
    {
        return ['user' => 'Ada', 'page' => $context->component, 'method' => $context->request->getMethod()];
    }
}
