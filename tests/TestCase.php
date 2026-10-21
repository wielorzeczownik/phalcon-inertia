<?php

namespace PhalconInertia\Tests;

use Phalcon\Di;
use Phalcon\Di\FactoryDefault;
use PhalconInertia\Inertia;
use PhalconInertia\Tests\Fixtures\ArraySession;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function di(array $server = [], array $post = [], $session = true)
    {
        $_SERVER = array_merge(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/users'], $server);
        $_POST = $post;

        if (class_exists('Phalcon\Di\Di')) {
            Di\Di::reset();
        } else {
            Di::reset();
        }

        $di = new FactoryDefault;
        $di->getShared('url')->setBaseUri('/');
        if ($session) {
            $di->setShared('session', new ArraySession);
        } else {
            $di->remove('session');
        }

        return $di;
    }

    protected function inertia(array $server = [], $version = 'v1', $session = true)
    {
        $inertia = new Inertia(__DIR__.'/Fixtures/app.php', $version);
        $inertia->setDI($this->di($server, [], $session));

        return $inertia;
    }

    protected static function inertiaServer(array $server = [])
    {
        return array_merge(['HTTP_X_INERTIA' => 'true', 'HTTP_X_INERTIA_VERSION' => 'v1'], $server);
    }

    protected static function pageFromHtml($html)
    {
        $open = '<script data-page="app" type="application/json">';
        $start = strpos($html, $open);
        if ($start === false) {
            return null;
        }
        $start += strlen($open);

        return json_decode(substr($html, $start, strpos($html, '</script>', $start) - $start), true);
    }

    protected function assertHasText($needle, $haystack)
    {
        $this->assertTrue(strpos($haystack, $needle) !== false, 'Missing "'.$needle.'" in: '.$haystack);
    }

    protected function assertLacksText($needle, $haystack)
    {
        $this->assertTrue(strpos($haystack, $needle) === false, 'Unexpected "'.$needle.'" in: '.$haystack);
    }
}
