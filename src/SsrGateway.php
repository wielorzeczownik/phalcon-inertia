<?php

namespace PhalconInertia;

class SsrGateway
{
    private $url;

    private $timeout;

    public function __construct($url = 'http://127.0.0.1:13714', $timeout = 2)
    {
        $this->url = rtrim($url, '/');
        $this->timeout = (float) $timeout;
    }

    public function render(array $page)
    {
        $context = stream_context_create(['http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($page),
            'timeout' => $this->timeout,
            'ignore_errors' => true,
        ]]);

        $raw = @file_get_contents($this->url.'/render', false, $context);
        if ($raw === false) {
            return null;
        }

        $data = json_decode($raw, true);
        if (! is_array($data) || ! isset($data['body']) || ! is_string($data['body'])) {
            return null;
        }

        return [
            'head' => isset($data['head']) && is_array($data['head']) ? implode("\n", $data['head']) : '',
            'body' => $data['body'],
        ];
    }
}
