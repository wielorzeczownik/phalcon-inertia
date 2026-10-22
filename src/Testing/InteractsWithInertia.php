<?php

namespace PhalconInertia\Testing;

use Phalcon\Http\ResponseInterface;

trait InteractsWithInertia
{
    /**
     * @param  ResponseInterface  $response
     * @param  callable|null  $callback
     * @return AssertableInertia
     */
    public function assertInertia($response, $callback = null)
    {
        $page = AssertableInertia::fromResponse($response);

        if ($callback !== null) {
            call_user_func($callback, $page);
        }

        return $page;
    }
}
