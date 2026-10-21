<?php

namespace PhalconInertia\Tests;

use PhalconInertia\SsrGateway;

class SsrGatewayTest extends TestCase
{
    public function test_successful_render_returns_head_and_body()
    {
        $this->withServer(function ($url) {
            $result = (new SsrGateway($url))->render(['component' => 'Users/Index']);

            $this->assertSame("<title>Users/Index</title>\n<meta name=\"ssr\" content=\"1\">", $result['head']);
            $this->assertSame('<div data-server-rendered="true" id="app">Users/Index</div>', $result['body']);
        });
    }

    public function test_failed_render_returns_null()
    {
        $this->withServer(function ($url) {
            $this->assertNull((new SsrGateway($url))->render(['component' => 'Broken']));
        });
    }

    public function test_unreachable_server_returns_null()
    {
        $this->assertNull((new SsrGateway('http://127.0.0.1:'.self::freePort(), 1))->render(['component' => 'Users/Index']));
    }

    private function withServer($test)
    {
        $port = self::freePort();
        $process = proc_open(
            escapeshellarg(PHP_BINARY).' -S 127.0.0.1:'.$port.' '.escapeshellarg(__DIR__.'/Fixtures/ssr.php'),
            [['pipe', 'r'], ['file', '/dev/null', 'w'], ['file', '/dev/null', 'w']],
            $pipes
        );

        try {
            $deadline = microtime(true) + 5;
            while (! ($socket = @fsockopen('127.0.0.1', $port)) && microtime(true) < $deadline) {
                usleep(50000);
            }
            $this->assertNotFalse($socket, 'SSR test server did not start');
            fclose($socket);

            $test('http://127.0.0.1:'.$port);
        } finally {
            proc_terminate($process);
            proc_close($process);
        }
    }

    private static function freePort()
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $name = stream_socket_get_name($socket, false);
        fclose($socket);

        return (int) substr($name, strrpos($name, ':') + 1);
    }
}
