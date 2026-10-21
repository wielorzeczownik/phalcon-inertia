<?php

namespace PhalconInertia\Tests\Fixtures;

use PhalconInertia\SsrGateway;

class StubSsr extends SsrGateway
{
    public $result;

    public $pages = [];

    public function __construct($result)
    {
        $this->result = $result;
    }

    public function render(array $page)
    {
        $this->pages[] = $page;

        return $this->result;
    }
}
