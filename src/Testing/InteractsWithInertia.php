<?php

namespace PhalconInertia\Testing;

trait InteractsWithInertia
{
    /**
     * @param \Phalcon\Http\ResponseInterface $response
     * @param callable|null $callback
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
