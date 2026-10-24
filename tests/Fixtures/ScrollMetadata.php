<?php

namespace PhalconInertia\Tests\Fixtures;

use PhalconInertia\ProvidesScrollMetadata;

class ScrollMetadata implements ProvidesScrollMetadata
{
    private $current;

    public function __construct($current)
    {
        $this->current = $current;
    }

    public function getPageName()
    {
        return 'cursor';
    }

    public function getPreviousPage()
    {
        return null;
    }

    public function getNextPage()
    {
        return $this->current + 1;
    }

    public function getCurrentPage()
    {
        return $this->current;
    }
}
