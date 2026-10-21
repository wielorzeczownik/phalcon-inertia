<?php

namespace PhalconInertia;

use Phalcon\Di\Injectable;

class Csrf extends Injectable
{
    const SESSION_KEY = '_inertia_xsrf';

    const COOKIE = 'XSRF-TOKEN';

    private $cookieSent = false;

    public function beforeExecuteRoute($event, $dispatcher)
    {
        $token = $this->token();

        if (! $this->cookieSent) {
            $this->sendCookie(self::COOKIE, $token);
            $this->cookieSent = true;
        }

        if (in_array($this->request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true) || $this->matches($token)) {
            return true;
        }

        $this->response->setStatusCode(419, 'Page Expired');

        return false;
    }

    public function token()
    {
        $token = (string) $this->session->get(self::SESSION_KEY, '');
        if ($token === '') {
            $token = bin2hex(function_exists('random_bytes') ? random_bytes(32) : openssl_random_pseudo_bytes(32));
            $this->session->set(self::SESSION_KEY, $token);
        }

        return $token;
    }

    public function regenerate()
    {
        $this->session->set(self::SESSION_KEY, '');
        $token = $this->token();
        $this->sendCookie(self::COOKIE, $token);
        $this->cookieSent = true;

        return $token;
    }

    protected function sendCookie($name, $value)
    {
        call_user_func_array('setcookie', $this->cookieArguments($name, $value));
    }

    protected function cookieArguments($name, $value)
    {
        $secure = $this->request->isSecure();
        if (PHP_VERSION_ID >= 70300) {
            return [$name, $value, ['expires' => 0, 'path' => '/', 'secure' => $secure, 'httponly' => false, 'samesite' => 'Lax']];
        }

        return [$name, $value, 0, '/; samesite=Lax', '', $secure, false];
    }

    private function matches($token)
    {
        $candidates = [
            $this->request->getHeader('X-XSRF-TOKEN'),
            $this->request->getHeader('X-CSRF-TOKEN'),
            $this->request->getPost('_token'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '' && hash_equals(hash('sha256', $token), hash('sha256', $candidate))) {
                return true;
            }
        }

        return false;
    }
}
