<?php

namespace PhalconInertia;

use Phalcon\Di\Injectable;
use Phalcon\Http\Response;

class Inertia extends Injectable
{
    const ERRORS = '_inertia_errors';

    const FLASH = '_inertia_flash';

    const CLEAR_HISTORY = '_inertia_clear_history';

    private $rootView;

    private $version;

    private $shared = [];

    private $encryptHistory = false;

    private $ssr;

    public function __construct($rootView, $version = '')
    {
        $this->rootView = $rootView;
        $this->version = (string) $version;
    }

    public static function always($value)
    {
        return Prop::always($value);
    }

    public static function optional($callback)
    {
        return Prop::optional($callback);
    }

    public static function defer($callback, $group = 'default', $rescue = false)
    {
        return Prop::defer($callback, $group, $rescue);
    }

    public static function once($callback)
    {
        return Prop::value($callback)->once();
    }

    public static function scroll($value, $wrapper = 'data', $metadata = null)
    {
        return Prop::scroll($value, $wrapper, $metadata);
    }

    public static function merge($value)
    {
        return Prop::merge($value);
    }

    public static function deepMerge($value)
    {
        return Prop::deepMerge($value);
    }

    public function share($key, $value = null)
    {
        $this->shared = array_merge($this->shared, is_array($key) ? $key : [$key => $value]);

        return $this;
    }

    public function input($name = null, $filters = null, $default = null, $notAllowEmpty = false)
    {
        $request = $this->request;

        if (stripos((string) $request->getContentType(), 'json') !== false) {
            $data = json_decode((string) $request->getRawBody(), true);
        } elseif ($request->isPost()) {
            $data = $request->getPost();
        } else {
            $data = $request->getPut();
        }
        $data = is_array($data) ? $data : [];

        if ($name === null) {
            return $data;
        }

        if (! array_key_exists($name, $data)) {
            return $default;
        }

        $value = $filters === null ? $data[$name] : $this->filter->sanitize($data[$name], $filters);

        return $notAllowEmpty && empty($value) ? $default : $value;
    }

    public function setRootView($rootView)
    {
        $this->rootView = $rootView;

        return $this;
    }

    public function back($fallback = '/')
    {
        $response = new Response;

        return $response->redirect($this->backUrl($fallback), true);
    }

    public function encryptHistory($encrypt = true)
    {
        $this->encryptHistory = (bool) $encrypt;

        return $this;
    }

    public function clearHistory()
    {
        $this->requireSession()->set(self::CLEAR_HISTORY, true);

        return $this;
    }

    public function setSsr($ssr)
    {
        if ($ssr !== null && ! $ssr instanceof SsrGateway) {
            throw new Exception('setSsr() expects an SsrGateway or null.');
        }
        $this->ssr = $ssr;

        return $this;
    }

    public function withErrors($errors, $bag = 'default')
    {
        $session = $this->requireSession();
        $bags = $session->get(self::ERRORS, []);
        $bags[(string) $bag] = self::normalizeErrors($errors);
        $session->set(self::ERRORS, $bags);

        return $this;
    }

    public function validate($validation, $data = null, $bag = 'default')
    {
        $data = $data === null ? $this->input() : $data;
        $errors = self::normalizeErrors($validation->validate($data));

        if ((string) $this->request->getHeader('Precognition') === 'true') {
            $only = array_filter(array_map('trim', explode(',', (string) $this->request->getHeader('Precognition-Validate-Only'))), 'strlen');
            if ($only) {
                $errors = array_intersect_key($errors, array_flip($only));
            }

            throw new ValidationException($errors, $bag, true);
        }

        if ($errors) {
            $this->withErrors($errors, $bag);

            throw new ValidationException($errors, $bag);
        }

        return $data;
    }

    public function flash($key, $value = null)
    {
        $session = $this->requireSession();
        $session->set(self::FLASH, array_merge(
            $session->get(self::FLASH, []),
            is_array($key) ? $key : [$key => $value]
        ));

        return $this;
    }

    public function render($component, array $props = [])
    {
        $request = $this->request;
        $url = $request->getURI();
        $isInertia = $this->isInertiaRequest();

        $response = new Response;
        $response->setHeader('Vary', 'X-Inertia');

        if ($isInertia && $request->isGet() && (string) $request->getHeader('X-Inertia-Version') !== $this->version) {
            $response->setStatusCode(409, 'Conflict');
            $response->setHeader('X-Inertia-Location', $url);
            $response->setHeader('X-Inertia-Version', $this->version);

            return $response;
        }

        $session = $this->session();
        $errors = self::errorsFor(
            $session ? $session->get(self::ERRORS, [], true) : [],
            (string) $request->getHeader('X-Inertia-Error-Bag')
        );

        $headers = [];
        foreach (['X-Inertia-Partial-Component', 'X-Inertia-Partial-Data', 'X-Inertia-Partial-Except', 'X-Inertia-Reset', 'X-Inertia-Except-Once-Props', 'X-Inertia-Infinite-Scroll-Merge-Intent'] as $name) {
            $headers[$name] = (string) $request->getHeader($name);
        }

        $page = Page::build(
            $component,
            array_merge(['errors' => Prop::always($errors)], $this->shared, $props),
            $url,
            $this->version,
            $headers,
            [
                'encryptHistory' => $this->encryptHistory,
                'clearHistory' => $session ? $session->get(self::CLEAR_HISTORY, false, true) : false,
                'sharedProps' => array_map('strval', array_keys($this->shared)),
                'flash' => $session ? $session->get(self::FLASH, [], true) : [],
                'report' => function ($exception) {
                    $this->report($exception);
                },
            ]
        );
        $json = self::encode($page);

        if ($isInertia) {
            $response->setHeader('X-Inertia', 'true');
            $response->setContentType('application/json', 'UTF-8');
            $response->setContent($json);

            return $response;
        }

        $ssr = $this->ssr ? $this->ssr->render($page) : null;
        $response->setContent($this->renderRootView(
            $page,
            $ssr ? $ssr['head'] : '',
            $ssr ? $ssr['body'] : '<script data-page="app" type="application/json">'.$json.'</script><div id="app"></div>'
        ));

        return $response;
    }

    public function location($url)
    {
        $response = new Response;

        if ($this->isInertiaRequest()) {
            $response->setStatusCode(409, 'Conflict');
            $response->setHeader('X-Inertia-Location', $url);

            return $response;
        }

        return $response->redirect($url, true);
    }

    public function beforeException($event, $dispatcher, $exception)
    {
        if (! $exception instanceof ValidationException) {
            return true;
        }

        $dispatcher->setReturnedValue($exception->isPrecognitive() ? self::precognition($exception) : $this->back());

        return false;
    }

    public function beforeSendResponse($event, $app, $response)
    {
        if (! $this->isInertiaRequest()) {
            return;
        }

        $status = (int) $response->getStatusCode();
        $location = (string) $response->getHeaders()->get('Location');
        if (in_array($status, [301, 302, 303, 307, 308], true) && strpos($location, '#') !== false) {
            $response->setStatusCode(409, 'Conflict');
            $response->getHeaders()->remove('Location');
            $response->setHeader('X-Inertia-Redirect', $location);
            $response->setContent('');

            return;
        }

        if (in_array($status, [0, 200], true) && (string) $response->getContent() === '') {
            $response->setStatusCode(302, 'Found');
            $response->setHeader('Location', $this->backUrl('/'));
            $status = 302;
        }

        if ($status === 302 && in_array($this->request->getMethod(), ['PUT', 'PATCH', 'DELETE'], true)) {
            $response->setStatusCode(303, 'See Other');
        }
    }

    private static function precognition(ValidationException $exception)
    {
        $errors = $exception->errors();
        $response = new Response;
        $response->setHeader('Precognition', 'true');
        $response->setHeader('Vary', 'Precognition');

        if (! $errors) {
            $response->setStatusCode(204, 'No Content');
            $response->setHeader('Precognition-Success', 'true');

            return $response;
        }

        $response->setStatusCode(422, 'Unprocessable Entity');
        $response->setContentType('application/json', 'UTF-8');
        $response->setContent(self::encode([
            'message' => $exception->getMessage(),
            'errors' => array_map(function ($message) {
                return [$message];
            }, $errors),
        ]));

        return $response;
    }

    private function report($exception)
    {
        $di = $this->getDI();
        if (! $di->has('logger')) {
            error_log('Inertia rescued a deferred prop: '.$exception);

            return;
        }

        $di->getShared('logger')->error('Inertia rescued a deferred prop: '.$exception->getMessage(), ['exception' => $exception]);
    }

    private function renderRootView($page, $head, $body)
    {
        ob_start();
        try {
            require $this->rootView;

            return ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }

    private function backUrl($fallback)
    {
        $referer = (string) $this->request->getHTTPReferer();
        $parts = parse_url($referer);

        if ($referer === '' || $parts === false) {
            return $fallback;
        }

        if (! isset($parts['host'])) {
            $isLocalPath = ! isset($parts['scheme']) && strpos($referer, '/') === 0 && strpos($referer, '//') !== 0;

            return $isLocalPath ? $referer : $fallback;
        }

        $scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
        $origin = $parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
        $isSameOrigin = in_array($scheme, ['http', 'https'], true) && strcasecmp($origin, (string) $this->request->getHttpHost()) === 0;

        return $isSameOrigin ? $referer : $fallback;
    }

    private function isInertiaRequest()
    {
        return (bool) $this->request->getHeader('X-Inertia');
    }

    private function session()
    {
        $di = $this->getDI();

        return $di->has('session') ? $di->getShared('session') : null;
    }

    private function requireSession()
    {
        $session = $this->session();
        if ($session === null) {
            throw new Exception('Inertia needs a "session" service for errors, flash data and history clearing.');
        }

        return $session;
    }

    private static function normalizeErrors($errors)
    {
        $normalized = [];
        foreach ($errors as $field => $message) {
            if (is_object($message) && method_exists($message, 'getField') && method_exists($message, 'getMessage')) {
                $field = $message->getField();
                $field = is_array($field) ? implode(',', $field) : (string) $field;
                $message = $message->getMessage();
            }
            if (! array_key_exists($field, $normalized)) {
                $normalized[$field] = $message;
            }
        }

        return $normalized;
    }

    private static function errorsFor(array $bags, $bagHeader)
    {
        $bags = array_map(function ($errors) {
            return (object) $errors;
        }, $bags);

        if (isset($bags['default']) && $bagHeader !== '') {
            return (object) [$bagHeader => $bags['default']];
        }

        return isset($bags['default']) ? $bags['default'] : (object) $bags;
    }

    private static function encode(array $data)
    {
        $json = json_encode($data, JSON_HEX_TAG);
        if ($json === false) {
            throw new Exception('Inertia page could not be encoded: '.json_last_error_msg());
        }

        return $json;
    }
}
