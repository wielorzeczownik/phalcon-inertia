<?php

namespace PhalconInertia\Tests;

use PhalconInertia\Tests\Fixtures\RecordingCsrf;

class CsrfTest extends TestCase
{
    public function test_safe_requests_pass_and_receive_the_token_cookie_once()
    {
        $csrf = $this->csrf(['REQUEST_METHOD' => 'GET']);

        $this->assertTrue($csrf->beforeExecuteRoute(null, null));
        $this->assertTrue($csrf->beforeExecuteRoute(null, null));
        $this->assertSame([['XSRF-TOKEN', $csrf->token()]], $csrf->cookies);
        $this->assertSame(64, strlen($csrf->token()));
    }

    public function test_unsafe_request_without_token_is_rejected_without_leaking_it()
    {
        $csrf = $this->csrf(['REQUEST_METHOD' => 'POST']);

        $this->assertFalse($csrf->beforeExecuteRoute(null, null));
        $this->assertSame(419, (int) $csrf->response->getStatusCode());
        $this->assertLacksText($csrf->token(), (string) $csrf->response->getContent());
    }

    public function test_wrong_token_is_rejected()
    {
        $csrf = $this->csrf(['REQUEST_METHOD' => 'PUT', 'HTTP_X_XSRF_TOKEN' => 'nope']);

        $this->assertFalse($csrf->beforeExecuteRoute(null, null));
    }

    public function test_token_is_accepted_from_headers_or_form_field()
    {
        foreach (['HTTP_X_XSRF_TOKEN', 'HTTP_X_CSRF_TOKEN', '_token'] as $source) {
            $csrf = $this->csrf(['REQUEST_METHOD' => 'POST']);
            $token = $csrf->token();
            $csrf = $this->csrf(
                $source === '_token' ? ['REQUEST_METHOD' => 'POST'] : ['REQUEST_METHOD' => 'POST', $source => $token],
                $source === '_token' ? ['_token' => $token] : [],
                $csrf->session
            );

            $this->assertTrue($csrf->beforeExecuteRoute(null, null), $source);
        }
    }

    public function test_regenerate_replaces_the_token_and_resends_the_cookie()
    {
        $csrf = $this->csrf(['REQUEST_METHOD' => 'POST']);
        $old = $csrf->token();

        $new = $csrf->regenerate();

        $this->assertNotSame($old, $new);
        $this->assertSame($new, $csrf->token());
        $this->assertSame([['XSRF-TOKEN', $new]], $csrf->cookies);

        $stale = $this->csrf(['REQUEST_METHOD' => 'POST', 'HTTP_X_XSRF_TOKEN' => $old], [], $csrf->session);
        $this->assertFalse($stale->beforeExecuteRoute(null, null));
    }

    public function test_cookie_is_same_site_lax_readable_by_js_and_secure_over_https()
    {
        $plain = self::cookieOptions($this->csrf(['REQUEST_METHOD' => 'GET'])->arguments('XSRF-TOKEN', 't'));
        $https = self::cookieOptions($this->csrf(['REQUEST_METHOD' => 'GET', 'HTTPS' => 'on'])->arguments('XSRF-TOKEN', 't'));

        $this->assertSame(['path' => '/', 'samesite' => 'Lax', 'secure' => false, 'httponly' => false], $plain);
        $this->assertTrue($https['secure']);
    }

    public function test_token_is_stable_within_a_session()
    {
        $csrf = $this->csrf(['REQUEST_METHOD' => 'GET']);

        $this->assertSame($csrf->token(), $csrf->token());
    }

    private static function cookieOptions(array $arguments)
    {
        if (is_array($arguments[2])) {
            return [
                'path' => $arguments[2]['path'],
                'samesite' => $arguments[2]['samesite'],
                'secure' => $arguments[2]['secure'],
                'httponly' => $arguments[2]['httponly'],
            ];
        }

        $path = explode('; samesite=', $arguments[3]);

        return ['path' => $path[0], 'samesite' => $path[1], 'secure' => $arguments[5], 'httponly' => $arguments[6]];
    }

    private function csrf(array $server, array $post = [], $session = null)
    {
        $di = $this->di($server, $post);
        if ($session !== null) {
            $di->setShared('session', $session);
        }
        $csrf = new RecordingCsrf;
        $csrf->setDI($di);

        return $csrf;
    }
}
