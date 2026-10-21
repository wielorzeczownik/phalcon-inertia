<?php

namespace PhalconInertia\Testing;

trait InteractsWithInertia
{
    public function assertInertia($response, $callback = null)
    {
        $page = AssertableInertia::fromResponse($response);

        if ($callback !== null) {
            call_user_func($callback, $page);
        }

        return $page;
    }
}
