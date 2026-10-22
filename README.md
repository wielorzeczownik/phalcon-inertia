<h1 align="center">phalcon-inertia</h1>

<p align="center">
  <a href="https://github.com/wielorzeczownik/phalcon-inertia/actions/workflows/release.yml"><picture><source media="(prefers-color-scheme: dark)" srcset="https://img.shields.io/github/actions/workflow/status/wielorzeczownik/phalcon-inertia/release.yml?branch=main&style=flat-square&labelColor=2d333b&color=3fb950"/><source media="(prefers-color-scheme: light)" srcset="https://img.shields.io/github/actions/workflow/status/wielorzeczownik/phalcon-inertia/release.yml?branch=main&style=flat-square&color=2ea043"/><img src="https://img.shields.io/github/actions/workflow/status/wielorzeczownik/phalcon-inertia/release.yml?branch=main&style=flat-square&labelColor=2d333b&color=3fb950" alt="Build"/></picture></a> <a href="https://github.com/wielorzeczownik/phalcon-inertia/releases/latest"><picture><source media="(prefers-color-scheme: dark)" srcset="https://img.shields.io/github/v/release/wielorzeczownik/phalcon-inertia?style=flat-square&labelColor=2d333b&color=3fb950"/><source media="(prefers-color-scheme: light)" srcset="https://img.shields.io/github/v/release/wielorzeczownik/phalcon-inertia?style=flat-square&color=2ea043"/><img src="https://img.shields.io/github/v/release/wielorzeczownik/phalcon-inertia?style=flat-square&labelColor=2d333b&color=3fb950" alt="Latest Release"/></picture></a> <a href="https://packagist.org/packages/wielorzeczownik/phalcon-inertia"><picture><source media="(prefers-color-scheme: dark)" srcset="https://img.shields.io/packagist/v/wielorzeczownik/phalcon-inertia?style=flat-square&labelColor=2d333b&color=3fb950"/><source media="(prefers-color-scheme: light)" srcset="https://img.shields.io/packagist/v/wielorzeczownik/phalcon-inertia?style=flat-square&color=2ea043"/><img src="https://img.shields.io/packagist/v/wielorzeczownik/phalcon-inertia?style=flat-square&labelColor=2d333b&color=3fb950" alt="Packagist"/></picture></a> <a href="https://packagist.org/packages/wielorzeczownik/phalcon-inertia"><picture><source media="(prefers-color-scheme: dark)" srcset="https://img.shields.io/packagist/dt/wielorzeczownik/phalcon-inertia?style=flat-square&labelColor=2d333b&color=3fb950"/><source media="(prefers-color-scheme: light)" srcset="https://img.shields.io/packagist/dt/wielorzeczownik/phalcon-inertia?style=flat-square&color=2ea043"/><img src="https://img.shields.io/packagist/dt/wielorzeczownik/phalcon-inertia?style=flat-square&labelColor=2d333b&color=3fb950" alt="Packagist downloads"/></picture></a> <a href="https://github.com/wielorzeczownik/phalcon-inertia/blob/main/LICENSE"><picture><source media="(prefers-color-scheme: dark)" srcset="https://img.shields.io/badge/License-MIT-3fb950?style=flat-square&labelColor=2d333b"/><source media="(prefers-color-scheme: light)" srcset="https://img.shields.io/badge/License-MIT-2ea043?style=flat-square"/><img src="https://img.shields.io/badge/License-MIT-3fb950?style=flat-square&labelColor=2d333b" alt="License: MIT"/></picture></a>
  <br/>
  <img src="https://img.shields.io/badge/PHP-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP"/>
  <img src="https://img.shields.io/badge/Phalcon-76C39B?style=flat-square" alt="Phalcon"/>
  <img src="https://img.shields.io/badge/Inertia.js-9553E9?style=flat-square&logo=inertia&logoColor=white" alt="Inertia.js"/>
  <img src="https://img.shields.io/badge/TypeScript-3178C6?style=flat-square&logo=typescript&logoColor=white" alt="TypeScript"/>
  <img src="https://img.shields.io/badge/Vite-646CFF?style=flat-square&logo=vite&logoColor=white" alt="Vite"/>
</p>

<p align="center">
  <img src="https://raw.githubusercontent.com/wielorzeczownik/phalcon-inertia/main/assets/logo.png" alt="phalcon-inertia logo" width="300" />
</p>

An [Inertia.js](https://inertiajs.com) adapter for Phalcon. Controllers stay on the Phalcon router and return Vue, React or Svelte pages instead of views, with no separate API in between.

It also ships `route()`, a port of [Ziggy](https://github.com/tighten/ziggy) for using named Phalcon routes in JavaScript.

- [**Installation**](#installation)
- [**Setup**](#setup)
- [**Inertia**](#inertia)
  - [Rendering pages](#rendering-pages)
  - [Reading input](#reading-input)
  - [Shared props](#shared-props)
  - [Prop types](#prop-types)
  - [Validation](#validation)
  - [Flash data, redirects and history](#flash-data-redirects-and-history)
  - [Error pages](#error-pages)
  - [Root view per request](#root-view-per-request)
  - [Asset versioning](#asset-versioning)
  - [Vite](#vite)
  - [CSRF](#csrf)
  - [Server side rendering](#server-side-rendering)
- [**Routes in JavaScript**](#routes-in-javascript)
  - [`route()` function](#route-function)
  - [`Router` class](#router-class)
  - [Route-model binding](#route-model-binding)
  - [TypeScript](#typescript)
- [**JavaScript frameworks**](#javascript-frameworks)
  - [Generating and importing the configuration](#generating-and-importing-the-configuration)
  - [Importing the `route()` function](#importing-the-route-function)
  - [Vue](#vue)
  - [React](#react)
  - [Svelte](#svelte)
  - [Under SSR](#under-ssr)
  - [SPAs or separate repos](#spas-or-separate-repos)
- [**Filtering routes**](#filtering-routes)
  - [Including/excluding routes](#includingexcluding-routes)
  - [Filtering with groups](#filtering-with-groups)
- [**Other**](#other)
  - [TLS/SSL termination and trusted proxies](#tlsssl-termination-and-trusted-proxies)
  - [Using `script()` with a Content Security Policy](#using-script-with-a-content-security-policy)
  - [Disabling the `route()` helper](#disabling-the-route-helper)
  - [Retrieving the config from an API endpoint](#retrieving-the-config-from-an-api-endpoint)
  - [Re-generating the types when your routes change](#re-generating-the-types-when-your-routes-change)
  - [Where Phalcon differs from Laravel](#where-phalcon-differs-from-laravel)
- [**Testing**](#testing)
- [**Configuration**](#configuration)
- [**Not covered yet**](#not-covered-yet)

## Installation

```bash
composer require wielorzeczownik/phalcon-inertia
npm install vite laravel-vite-plugin
```

Install the Inertia v3 client for your framework (`@inertiajs/vue3`, `/react` or `/svelte` 3.x) and its Vite plugin. Older clients won't start: they look for the first page in a `data-page` attribute, while v3 puts it in a `<script type="application/json">` element.

`laravel-vite-plugin` works without Laravel. The `Vite` helper reads the `public/hot` file it writes in development and the `public/build` directory it builds to.

```js
// vite.config.js
import path from 'node:path';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
  plugins: [
    laravel({ input: ['resources/js/app.js'], ssr: 'resources/js/ssr.js' }),
    // your framework plugin, e.g. vue(), react() or svelte()
  ],
  resolve: {
    alias: {
      'phalcon-routes': path.resolve(
        'vendor/wielorzeczownik/phalcon-inertia/js'
      ),
    },
  },
});
```

Then create `resources/js/app.js` as in the Inertia [client-side setup](https://inertiajs.com/docs/v3/installation/client-side-setup) and add `route()` for [your framework](#javascript-frameworks). For a complete Vue example with SSR, see the [e2e app](e2e).

## Setup

Register the service provider. It sets up the `inertia`, `vite`, `routes` and `csrf` services and hooks its listeners into the `eventsManager` service and the dispatcher. Give the application the same events manager:

```php
use PhalconInertia\InertiaServiceProvider;

$di->register(new InertiaServiceProvider([
    'rootView' => APP_PATH.'/views/app.php',
    'publicPath' => BASE_PATH.'/public',
]));

$application->setEventsManager($di->getShared('eventsManager'));
```

All options are listed under [Configuration](#configuration). The provider implements `Phalcon\Di\ServiceProviderInterface` on every supported version, from 3.4 to 5.

<details>
<summary>Registering the services by hand</summary>

```php
use Phalcon\Events\Manager;
use PhalconInertia\Csrf;
use PhalconInertia\Inertia;
use PhalconInertia\Routes;
use PhalconInertia\Vite;

$di->setShared('vite', function () {
    return new Vite(BASE_PATH.'/public');
});
$di->setShared('routes', Routes::class);
$di->setShared('csrf', Csrf::class);
$di->setShared('inertia', function () use ($di) {
    return new Inertia(APP_PATH.'/views/app.php', $di->getShared('vite')->version());
});

$events = new Manager;
$events->attach('application:beforeSendResponse', $di->getShared('inertia'));
$events->attach('dispatch:beforeException', $di->getShared('inertia'));
$events->attach('dispatch:beforeExecuteRoute', $di->getShared('csrf'));

$di->getShared('dispatcher')->setEventsManager($events);
$application->setEventsManager($events);
```

</details>

The root view is a plain PHP file. It runs inside the `Inertia` service, with `$this` giving access to the DI, and gets three variables: `$page` (the page array), `$head` (head tags from SSR, empty without it) and `$body` (the mount point with the page data, or the SSR markup). `$this->routes->script()` prints the route config and the `route()` function, much like `@routes` in Laravel.

```php
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <?= $this->routes->script() ?>
    <?= $this->vite->tags('resources/js/app.js') ?>
    <?= $head ?>
</head>
<body>
    <?= $body ?>
</body>
</html>
```

## Inertia

### Rendering pages

```php
class UsersController extends \Phalcon\Mvc\Controller
{
    public function indexAction()
    {
        return $this->inertia->render('Users/Index', [
            'users' => Users::find()->toArray(),
        ]);
    }
}
```

Closures run only when their prop is actually sent. A partial reload that skips a prop skips its query too.

There is also a global `inertia()` helper. `inertia('Users/Index', $props)` renders a page, and `inertia()` with no arguments returns the service.

`$this->inertia` is resolved by the DI container, so IDEs don't know its type. Declare it on a base controller to get completion:

```php
/**
 * @property \PhalconInertia\Inertia $inertia
 */
class ControllerBase extends \Phalcon\Mvc\Controller
{
}
```

### Reading input

The v3 client sends JSON, which `$request->getPost()` can't read on Phalcon 3.4. `input()` reads JSON, form and `PUT` bodies on every version. Its arguments match `getPost()`: name, filters, default and `notAllowEmpty`.

```php
$name = $this->inertia->input('name', 'trim');
$page = $this->inertia->input('page', 'int', 1);
$all = $this->inertia->input();
```

Filters are Phalcon's own and behave as they do in `getPost()`. On 3.4, for example, `int` returns `'5'`, while 4 and 5 return `5`.

### Shared props

```php
$this->inertia->share('auth', function () {
    return ['user' => $this->session->get('user')];
});
```

Shared props are added to every page and listed in `sharedProps`. A prop passed to `render()` overrides a shared one with the same key.

### Prop types

```php
use PhalconInertia\Inertia;

return $this->inertia->render('Dashboard', [
    'stats' => Inertia::defer(function () { return Stats::heavy(); }),
    'sidebar' => Inertia::defer(function () { return Menu::all(); }, 'aside'),
    'audit' => Inertia::optional(function () { return Audit::recent(); }),
    'flags' => Inertia::always(Features::all()),
    'posts' => Inertia::merge($page->items)->matchOn('id'),
    'feed' => Inertia::merge($items)->prepend(),
    'settings' => Inertia::deepMerge($settings),
    'plans' => Inertia::once(function () { return Plan::all(); }),
    'permissions' => Inertia::defer(function () { return Acl::load(); }, 'default', true),
    'timeline' => Inertia::scroll($paginator->paginate()),
]);
```

| Factory                             | Sent on the first visit       | Sent on a partial reload             |
| ----------------------------------- | ----------------------------- | ------------------------------------ |
| plain value or closure              | yes                           | when requested                       |
| `Inertia::always($value)`           | yes                           | always, even when not listed         |
| `Inertia::optional($callback)`      | no                            | when requested                       |
| `Inertia::defer($callback, $group)` | no, fetched right after       | when requested                       |
| `Inertia::merge($value)`            | yes                           | when requested, merged by the client |
| `Inertia::deepMerge($value)`        | yes                           | when requested, deep merged          |
| `Inertia::once($callback)`          | yes, unless the client has it | when requested                       |
| `Inertia::scroll($page)`            | yes                           | when requested, merged by the client |

`append()`, `prepend()`, `deep()` and `matchOn($field)` chain onto `Inertia::defer()` as well. Every factory also exists on `PhalconInertia\Prop`, except `once()`, which there is written as `Prop::value($value)->once()`.

#### Rescued deferred props

Pass `true` as the third argument of `defer()` to rescue a prop whose callback throws. The exception goes to the `logger` service, or to `error_log()` if there isn't one. The prop is dropped and listed in `rescuedProps`, and the rest of the page loads normally. `<Deferred>` then renders its `rescue` slot, and `router.reload({ only: ['permissions'] })` retries. Without the flag, the exception fails the whole request.

#### Once props

A once prop is resolved on the first visit and then kept by the client. On later visits the client lists the props it already has in `X-Inertia-Except-Once-Props`, and their callbacks don't run. The client only keeps props of the current page. To keep one across pages, share it: `share('plans', Inertia::once(...))`.

```php
Inertia::once($callback)->until(3600); // seconds, a DateTimeInterface or a DateInterval
Inertia::once($callback)->fresh(); // resolve it again on this response
Inertia::once($callback)->key('billing'); // Laravel's as(), which is a reserved word on PHP 5.6
Inertia::defer($callback)->once(); // once also chains onto other props
```

#### Infinite scroll

`Inertia::scroll()` takes a page from a Phalcon paginator (any supported version) and gives `<InfiniteScroll>` the items under `data` plus the current, previous and next page numbers. The client requests pages through the `page` query parameter. Pass it on to the paginator:

```php
$paginator = new NativeArray([
    'data' => $posts,
    'limit' => 20,
    'page' => max(1, (int) $this->request->getQuery('page', 'int', 1)),
]);

return $this->inertia->render('Posts/Index', [
    'posts' => Inertia::scroll($paginator->paginate()),
]);
```

```vue
<InfiniteScroll data="posts">
  <article v-for="post in posts.data" :key="post.id">{{ post.title }}</article>
</InfiniteScroll>
```

For data that doesn't come from a Phalcon paginator, pass the wrapper key and the metadata yourself, e.g. `Inertia::scroll(['items' => $items], 'items', ['currentPage' => 2, 'previousPage' => 1, 'nextPage' => 3])`. Whether new items are appended or prepended follows the `X-Inertia-Infinite-Scroll-Merge-Intent` header from the client.

### Validation

`validate()` is the equivalent of Laravel's `$request->validate()`. It validates the request input and returns it. When validation fails, it saves the errors and throws a `ValidationException`, and the `dispatch:beforeException` listener turns that into a redirect back.

```php
public function storeAction()
{
    $data = $this->inertia->validate($validation);
    Users::create($data);

    return $this->response->redirect('users');
}
```

Pass other data as the second argument, or an error bag name as the third (like `validateWithBag()`). To handle errors yourself, use `withErrors()` and `back()`:

```php
$messages = $validation->validate($this->inertia->input());
if (count($messages)) {
    $this->inertia->withErrors($messages);

    return $this->inertia->back();
}
```

`withErrors()` accepts Phalcon validation messages or a `field => message` array and keeps the first message for each field. The next page gets them in the `errors` prop, which is always there (`{}` when empty). The second argument picks a named bag, and the `X-Inertia-Error-Bag` header sent by `useForm({ errorBag })` is respected.

#### Precognition

`validate()` handles [Precognition](https://inertiajs.com/docs/v3/the-basics/forms) requests too. Forms can show server side errors while the user types, without duplicating any rules in JavaScript. When the `Precognition` header is present, only the fields in `Precognition-Validate-Only` are checked, nothing goes into the session, and the action stops with `204` (valid) or `422` with the errors. The controller stays the same:

```js
const form = useForm('post', route('users.store'), { name: '' });
```

```vue
<input v-model="form.name" @change="form.validate('name')" />
<p v-if="form.errors.name">{{ form.errors.name }}</p>
<button @click="form.submit()">Create</button>
```

A Precognition request runs the action up to the `validate()` call. Put the call before anything with side effects.

`back($fallback = '/')` redirects to the referrer if it is on the same host, and to `$fallback` otherwise. A forged `Referer` can't send users off-site.

### Flash data, redirects and history

```php
$this->inertia->flash('status', 'Saved');
$this->inertia->clearHistory();
return $this->response->redirect('users');

return $this->inertia->location('https://payments.example.com/checkout');
```

- Flash data shows up on the next page as `usePage().flash`.
- After `PUT`, `PATCH` or `DELETE`, a redirect becomes `303 See Other`.
- A redirect to a URL with a fragment, like `redirect('posts#comments')`, becomes `409` with `X-Inertia-Redirect`. The client follows it and keeps the fragment.
- An action that returns nothing to an Inertia request redirects back.
- `location()` leaves the app with a full page load.
- `encryptHistory()` on the service turns on history encryption for every page.

### Error pages

```php
$router->notFound(['controller' => 'errors', 'action' => 'notFound']);

public function notFoundAction()
{
    return $this->inertia->render('Error', ['status' => 404])->setStatusCode(404, 'Not Found');
}
```

Handle exceptions the same way in a `dispatch:beforeException` listener. Otherwise the client shows the raw error response in a modal.

Errors from the library itself are thrown as `PhalconInertia\Exception`, for example when the Vite manifest or the `session` service is missing. `ValidationException` extends it, and it extends `\Exception`, like the exceptions of Phalcon's own components.

### Root view per request

```php
return $this->inertia->setRootView(APP_PATH.'/views/admin.php')->render('Admin/Index');
```

### Asset versioning

The asset version is the second constructor argument, and `Vite::version()` returns a hash of the build manifest. If an Inertia `GET` comes in with a different version, the response is `409 Conflict` with `X-Inertia-Location` and the client does a full reload. Errors and flash data in the session survive it.

### Vite

`$this->vite->tags($entries, $nonce)` prints the dev server client and entries while `public/hot` exists. Otherwise it prints the built script, its CSS and `modulepreload` links from the manifest. `asset($path)` returns the URL of any built file. The constructor takes the public directory, the build directory (`build`) and the base URL for assets (`/`).

### CSRF

`Csrf` stores a token in the session, sends it as the `XSRF-TOKEN` cookie and checks it on every request except `GET`, `HEAD` and `OPTIONS`. The Inertia client sends the cookie back in `X-XSRF-TOKEN` on its own, and `X-CSRF-TOKEN` or a `_token` form field work too. A missing or wrong token stops the request before the controller with `419 Page Expired`. The check covers the whole application, which means plain HTML forms need a `_token` field filled with `$this->csrf->token()`.

The cookie is `SameSite=Lax`, and `Secure` over HTTPS. When a user logs in or out, regenerate the token along with the session ID:

```php
$this->session->regenerateId();
$this->csrf->regenerate();
```

`GET`, `HEAD` and `OPTIONS` are never checked, and actions that change data must not respond to them. Register those routes with `addPost()`, `addPut()`, `addPatch()` or `addDelete()` rather than `add()`, which accepts any method.

### Server side rendering

```php
use PhalconInertia\SsrGateway;

$di->getShared('inertia')->setSsr(new SsrGateway('http://127.0.0.1:13714', 2));
```

With the provider, the `ssr` option does the same. Build with `vite build && vite build --ssr`, then keep `node bootstrap/ssr/ssr.js` running under systemd, supervisor or pm2. The first visit is sent to that server, and what it returns goes into `$head` and `$body`. If the server is down, slow or fails, the page falls back to client rendering. Inertia visits never hit it. For `route()` during SSR, see [Under SSR](#under-ssr).

## Routes in JavaScript

`route()` takes a route name and its parameters and returns the URL, with the same API as [Ziggy](https://github.com/tighten/ziggy). Only named routes are exported. Once `<?= $this->routes->script() ?>` is in the root view (see [Setup](#setup)), `route()` is available everywhere in the browser.

If you know Ziggy, this is how the names map:

| Ziggy                                | phalcon-inertia                          |
| ------------------------------------ | ---------------------------------------- |
| `@routes`                            | `<?= $this->routes->script() ?>`         |
| `(new Ziggy)->toArray()`             | `$this->routes->toArray()`               |
| `php artisan ziggy:generate --types` | `$this->routes->types()`                 |
| `ziggy-js`                           | `phalcon-routes`                         |
| `ZiggyVue`                           | `RouteVue`                               |
| `useRoute()`                         | `useRoute()`                             |
| `config/ziggy.php`                   | `routes` option, or a `Phalcon\Config`   |
| `@routes('author')`                  | `<?= $this->routes->script('author') ?>` |
| `URL::defaults()`                    | `defaults` in the `routes` option        |

### `route()` function

#### Basic usage

```php
$router->addGet('/posts', 'Posts::index')->setName('posts.index');
```

```js
route('posts.index'); // 'https://example.com/posts'
```

#### Parameters

```php
$router->addGet('/posts/{post}', 'Posts::show')->setName('posts.show');
```

```js
route('posts.show', 1); // 'https://example.com/posts/1'
route('posts.show', [1]); // 'https://example.com/posts/1'
route('posts.show', { post: 1 }); // 'https://example.com/posts/1'
```

#### Multiple parameters

```php
$router
    ->addGet('/venues/{venue}/events/{event}', 'Events::show')
    ->setName('venues.events.show');
```

```js
route('venues.events.show', [1, 2]); // 'https://example.com/venues/1/events/2'
route('venues.events.show', { venue: 1, event: 2 }); // 'https://example.com/venues/1/events/2'
```

#### Query parameters

Arguments that match no route parameter become query parameters:

```js
route('venues.events.show', {
  venue: 1,
  event: 2,
  page: 5,
  count: 10,
});
// 'https://example.com/venues/1/events/2?page=5&count=10'
```

To send a query parameter with the same name as a route parameter, nest it under the special `_query` key:

```js
route('venues.events.show', {
  venue: 1,
  event: 2,
  _query: {
    event: 3,
    page: 5,
  },
});
// 'https://example.com/venues/1/events/2?event=3&page=5'
```

Booleans become `1` and `0`, `null` values are dropped, and arrays and objects use PHP-style brackets:

```js
route('posts.index', {
  _query: { draft: false, tags: ['php', 'vue'], author: null },
});
// 'https://example.com/posts?draft=0&tags[0]=php&tags[1]=vue'
```

#### Default parameter values

Values shared by every URL, like a locale, can be set once under `defaults` in the [route options](#route-options). This replaces Laravel's `URL::defaults()`:

```php
$router->addGet('/{locale}/posts/{post}', 'Posts::show')->setName('posts.show');

$di->register(new InertiaServiceProvider([
    // ...
    'routes' => ['defaults' => ['locale' => 'en']],
]));
```

```js
route('posts.show', 1); // 'https://example.com/en/posts/1'
route('posts.show', { locale: 'pl', post: 1 }); // 'https://example.com/pl/posts/1'
```

#### Relative URLs

URLs are absolute by default. Pass `false` as the third argument for a path. The Phalcon base URI stays in it:

```js
route('posts.show', 1, false); // '/posts/1', or '/app/posts/1' with the base URI '/app/'
```

#### Constraints

Constraints from the Phalcon route carry over. `route()` throws when a value wouldn't match:

```php
$router->addGet('/users/{id:[0-9]+}', 'Users::show')->setName('users.show');
$router->addGet('/legacy/:int', ['controller' => 'legacy', 'id' => 1])->setName('legacy.show');
```

```js
route('users.show', 5); // 'https://example.com/users/5'
route('users.show', 'ada');
// Error: Route error: 'id' parameter does not match required format '[0-9]+' for route 'users.show'.
```

A constraint that allows slashes, such as `{path:.+}`, keeps them in the URL.

#### Examples

With the Inertia client:

```js
import { router, useForm } from '@inertiajs/vue3';

const form = useForm({ title: '' });
form.post(route('posts.store'));

router.delete(route('posts.destroy', post));
```

With `fetch`:

```js
const response = await fetch(route('posts.index', { _query: { page: 2 } }));
```

### `Router` class

Called with no arguments, `route()` returns a `Router`.

#### Check the current route: `route().current()`

```js
// at https://example.com/posts/4 with 'posts.show' at '/posts/{post}'

route().current(); // 'posts.show'
route().current('posts.index'); // false
route().current('posts.show'); // true
route().current('posts.*'); // true

route().current('posts.show', { post: 1 }); // false
route().current('posts.show', { post: 4 }); // true
```

If two routes match the URL, the one added last wins, as in the Phalcon router. Constraints like `{post:[0-9]+}` keep `/posts/create` and `/posts/{post}` apart.

#### Check if a route exists: `route().has()`

```js
route().has('posts.show'); // true
route().has('payments.index'); // false
```

#### Retrieve the current route params: `route().params`

```js
// at https://example.com/venues/1/events/2?lang=en

route().params; // { venue: '1', event: '2', lang: 'en' }
```

> Values in `params` are always strings, because they come from the URL.

### Route-model binding

Phalcon has no route-model binding. An object passed as a parameter resolves to its `id`, which covers `toArray()` models and most API payloads:

```js
const post = { id: 3, title: 'Hello', slug: 'hello' };

route('posts.show', post); // 'https://example.com/posts/3'
route('posts.comments.show', [post, { id: 7 }]); // 'https://example.com/posts/3/comments/7'
```

To use another key, pass it yourself, for example `route('posts.show', post.slug)`.

### TypeScript

`types()` generates a declaration of every route and its parameters (Ziggy's `ziggy:generate --types`). Save it with your sources to get autocompletion and type checks for route names and parameters:

```php
file_put_contents(BASE_PATH.'/resources/js/routes.d.ts', $di->getShared('routes')->types());
```

Types for `route()` itself ship with the package. Map the alias in `tsconfig.json`:

```json
{
  "compilerOptions": {
    "paths": {
      "phalcon-routes": ["./vendor/wielorzeczownik/phalcon-inertia/js"]
    }
  }
}
```

For the global `route()` from `script()`, declare it once:

```ts
import type { route as routeFn } from 'phalcon-routes';

declare global {
  var route: typeof routeFn;
}
```

## JavaScript frameworks

### Generating and importing the configuration

For most apps, `script()` in the root view is enough. It prints the route config and inlines `route()`, which then works in any script, with or without a bundler:

```php
<?= $this->routes->script() ?>
```

To bundle the config instead, write it to a file at build time and import it:

```php
file_put_contents(BASE_PATH.'/resources/js/routes.json', $di->getShared('routes')->toJson());
```

```js
import { route } from 'phalcon-routes';
import routes from './routes.json';

route('posts.show', post, undefined, routes);
```

The framework helpers below take the same config once, so you don't pass it on every call.

With SSR, the server needs the config as well. See [Under SSR](#under-ssr).

> [!IMPORTANT]
> Either way, every named route reaches the browser unless you [filter them](#filtering-routes).

### Importing the `route()` function

`route()` ships prebuilt in the Composer package, so there is nothing to install from npm. Point an alias at it, the same way Ziggy is set up from `vendor/tightenco/ziggy`:

```js
// vite.config.js
resolve: {
  alias: {
    'phalcon-routes': path.resolve('vendor/wielorzeczownik/phalcon-inertia/js'),
  },
},
```

```js
import { route } from 'phalcon-routes';
```

### Vue

`RouteVue` adds `route()` to templates and to `inject('route')`. With `script()` in the root view it needs no config:

```js
import { createApp } from 'vue';
import { RouteVue } from 'phalcon-routes';
import App from './App.vue';

createApp(App).use(RouteVue);
```

```vue
<script setup>
import { Link } from '@inertiajs/vue3';
import { inject } from 'vue';

defineProps({ post: Object });

const route = inject('route');
</script>

<template>
  <Link :href="route('posts.show', post)">{{ post.title }}</Link>
</template>
```

With a [generated config](#generating-and-importing-the-configuration), pass it to the plugin:

```js
import routes from './routes.json';

createApp(App).use(RouteVue, routes);
```

With TypeScript, declare `route()` for templates:

```ts
import type { route as routeFn } from 'phalcon-routes';

declare module 'vue' {
  interface ComponentCustomProperties {
    route: typeof routeFn;
  }
}
```

### React

`useRoute()` returns `route()`. With `script()` in the root view it takes no arguments:

```jsx
import { Link } from '@inertiajs/react';
import { useRoute } from 'phalcon-routes';

export default function Post({ post }) {
  const route = useRoute();

  return <Link href={route('posts.show', post)}>{post.title}</Link>;
}
```

With a generated config, pass it: `useRoute(routes)`.

### Svelte

```svelte
<script>
  import { Link } from '@inertiajs/svelte';
  import { useRoute } from 'phalcon-routes';

  let { post } = $props();

  const route = useRoute();
</script>

<Link href={route('posts.show', post)}>{post.title}</Link>
```

### Under SSR

The SSR server is a separate Node process. It has no `window` and never sees the `script()` output, which is why it gets the route config as a shared prop:

```php
$di->getShared('inertia')->share('routes', function () use ($di) {
    return $di->getShared('routes')->toArray();
});
```

In the SSR entry, pass it on together with a `location`, which `current()` and `params` need. With Vue, the client entry keeps `.use(RouteVue)`:

```js
// resources/js/ssr.js
setup({ App, props, plugin }) {
  const routes = props.initialPage.props.routes;

  return createSSRApp({ render: () => h(App, props) })
    .use(plugin)
    .use(RouteVue, { ...routes, location: new URL(props.initialPage.url, routes.url) });
},
```

For React and Svelte, pass the same object to `useRoute()` in the SSR render.

### SPAs or separate repos

A frontend that Phalcon doesn't render, or that lives in another repository, has no `vendor/` directory to alias. Copy `js/dist` from the Composer package into it (plain ES modules with types), load the config from [an API endpoint](#retrieving-the-config-from-an-api-endpoint) or a generated `routes.json`, and pass it to `useRoute()` or `RouteVue`.

## Filtering routes

> [!IMPORTANT]
> Filtering hides route names from the page, it does not protect them. Every endpoint still needs its own authorization.

### Including/excluding routes

Without filters, every named route ends up in the browser, admin ones included. Set `only` or `except` in the [route options](#route-options), with `*` as a wildcard. They apply to every call of `script()`, `toArray()` and `types()`:

```php
$di->register(new InertiaServiceProvider([
    // ...
    'routes' => [
        'except' => ['admin.*', 'debug.*'],
    ],
]));
```

The options can come from your Phalcon config too, as an array or a `Phalcon\Config`:

```php
// app/config/config.php
'routes' => [
    'only' => ['posts.*', 'home'],
],

// services
'routes' => $di->getShared('config')->routes,
```

A route is exported if it matches `only` (or `only` is empty) and doesn't match `except`. Patterns passed to a call override the options, and an empty array disables a filter:

```php
$this->routes->toArray(['posts.*'], ['posts.drafts.*']);
$this->routes->toArray([], []); // every named route
```

### Filtering with groups

Named sets of patterns go under `groups` in the [route options](#route-options). A group name works anywhere a pattern does:

```php
$di->register(new InertiaServiceProvider([
    // ...
    'routes' => [
        'groups' => [
            'admin' => ['admin.*', 'users.*'],
            'author' => ['posts.*'],
        ],
    ],
]));
```

```php
<?= $this->routes->script('author') ?>
<?= $this->routes->script(['admin', 'author']) ?>
<?= $this->routes->script([], 'admin') ?>
```

If a group and a route share a name, the group wins. Avoid naming a group after a route.

## Other

### TLS/SSL termination and trusted proxies

The config URL is built from the request scheme, host and base URI. Behind a proxy that terminates TLS, Phalcon may see `http` and generate `http` URLs. Set the public URL in the [route options](#route-options):

```php
$di->register(new InertiaServiceProvider([
    // ...
    'routes' => ['url' => 'https://example.com'],
]));
```

### Using `script()` with a Content Security Policy

The third argument of `script()` is a nonce, as with `$this->vite->tags()`:

```php
<?= $this->routes->script([], [], $cspNonce) ?>
```

### Disabling the `route()` helper

If you import `route()` from your bundle, the copy inlined by `script()` isn't needed. Pass `false` as the fourth argument to print only the config:

```php
<?= $this->routes->script([], [], null, false) ?>
```

### Retrieving the config from an API endpoint

For a frontend that Phalcon doesn't render, serve the config as JSON:

```php
public function routesAction()
{
    return $this->response
        ->setContentType('application/json', 'UTF-8')
        ->setContent($this->routes->toJson(['api.*']));
}
```

```js
const config = await fetch('/routes').then((response) => response.json());
const route = useRoute(config);
```

### Re-generating the types when your routes change

`types()` reads the router, which means it has to run where your routes are registered. A CLI task called from your deploy script works well:

```php
class RoutesTask extends \Phalcon\Cli\Task
{
    public function typesAction()
    {
        file_put_contents(BASE_PATH.'/resources/js/routes.d.ts', $this->routes->types());
    }
}
```

The CLI application needs the same router and `url` services as the web one.

### Where Phalcon differs from Laravel

- Constraints like `{id:[0-9]+}` or `:int` become `wheres`. `route()` rejects values that wouldn't match, and `current()` can tell `/users/create` from `/users/{id}`.
- `current()` checks routes from last added to first, the same order the Phalcon router uses.
- The base URI stays in relative URLs (`/app/users/5`). Plain hostnames from `setHostname()` become the route domain, regex hostnames are skipped.
- There is no route-model binding or route middleware. Objects resolve to their `id`, and URL defaults come from `defaults` in the route options.

## Testing

Add the `InteractsWithInertia` trait to a PHPUnit test case. It works with both JSON and HTML responses.

```php
use PhalconInertia\Testing\InteractsWithInertia;

class UsersTest extends \PHPUnit\Framework\TestCase
{
    use InteractsWithInertia;

    public function test_index()
    {
        $this->assertInertia($response, function ($page) {
            $page->component('Users/Index')
                ->has('users', 3)
                ->where('users.0.name', 'Ada')
                ->missing('users.0.password');
        });
    }
}
```

`component()`, `url()`, `version()`, `has($key, $count)`, `where($key, $valueOrCallback)` and `missing($key)` take dot paths into the props. `prop($key)` returns the raw value for your own assertions.

A callback scopes into a prop. `has('users.0', $callback)` scopes into that object, and `has('users', 3, $callback)` checks the count first and then scopes into the first item. Inside a scope, every prop must be asserted or the test fails. Call `etc()` to allow the rest.

```php
$page->has('users', 3, function ($user) {
    $user->where('id', 1)
        ->where('name', 'Ada')
        ->missing('password')
        ->etc();
});
```

## Configuration

`InertiaServiceProvider` options:

| Option           | Required | Default                   | Meaning                                                     |
| ---------------- | -------- | ------------------------- | ----------------------------------------------------------- |
| `rootView`       | yes      |                           | Path to the root view PHP file                              |
| `publicPath`     | yes      |                           | Public directory holding `hot` and the build directory      |
| `version`        | no       | hash of the Vite manifest | Asset version, compared with `X-Inertia-Version`            |
| `buildDirectory` | no       | `build`                   | Build directory inside the public directory                 |
| `assetUrl`       | no       | `/`                       | Base URL for built files, for example a CDN                 |
| `routes`         | no       | `[]`                      | [Route options](#route-options)                             |
| `csrf`           | no       | `true`                    | Attaches the `Csrf` listener                                |
| `ssr`            | no       | off                       | URL of the SSR server, for example `http://127.0.0.1:13714` |
| `ssrTimeout`     | no       | `2`                       | Seconds to wait before falling back to client rendering     |

Class settings, for setups without the provider:

| Setting                     | Required | Default                  | Meaning                                                               |
| --------------------------- | -------- | ------------------------ | --------------------------------------------------------------------- |
| `Inertia` `$rootView`       | yes      |                          | Path to the root view PHP file                                        |
| `Inertia` `$version`        | no       | `''`                     | Asset version, compared with `X-Inertia-Version`                      |
| `Inertia::setRootView()`    | no       | the constructor value    | Root view for the current request                                     |
| `Inertia::encryptHistory()` | no       | off                      | Sets `encryptHistory` on every page                                   |
| `Inertia::setSsr()`         | no       | off                      | `SsrGateway` used for the first visit                                 |
| `SsrGateway` `$url`         | no       | `http://127.0.0.1:13714` | Base URL of the SSR server, `/render` is appended                     |
| `SsrGateway` `$timeout`     | no       | `2`                      | Seconds to wait before falling back to client rendering               |
| `Vite` `$publicPath`        | yes      |                          | Public directory holding `hot` and the build directory                |
| `Vite` `$buildDirectory`    | no       | `build`                  | Build directory inside the public directory                           |
| `Vite` `$assetUrl`          | no       | `/`                      | Base URL for built files, for example a CDN                           |
| `Routes` `$options`         | no       | `[]`                     | [Route options](#route-options), as an array or a `Phalcon\Config`    |
| `session` service           | no       |                          | Needed by `withErrors()`, `flash()`, `clearHistory()` and `Csrf`      |
| `logger` service            | no       |                          | Receives rescued deferred props through `error()`, else `error_log()` |
| `allow_url_fopen`           | no       | PHP default              | Needed by `SsrGateway`                                                |

### Route options

The provider's `routes` option and the `Routes` constructor take the same keys:

| Key        | Default                                | Meaning                                                        |
| ---------- | -------------------------------------- | -------------------------------------------------------------- |
| `url`      | scheme, host and base URI of a request | Base URL of absolute routes                                    |
| `only`     | `[]`                                   | Patterns or group names of the routes to export, empty for all |
| `except`   | `[]`                                   | Patterns or group names of the routes to leave out             |
| `groups`   | `[]`                                   | Named sets of patterns, usable wherever a pattern goes         |
| `defaults` | `[]`                                   | Default parameter values for `route()`                         |

## Not covered yet

Props are matched by their top-level key only. A partial reload of `user.permissions` returns nothing (reload `user` instead), and a `defer()`, `once()`, `scroll()` or `merge()` prop nested inside another prop is resolved right away, like a plain value. Give expensive parts their own top-level prop:

```php
'user' => ['name' => $user->name],
'userPermissions' => Inertia::defer(function () use ($user) { return $user->permissions(); }),
```

## Credits

`route()` follows the API of [Ziggy](https://github.com/tighten/ziggy) by Tighten. Where the Inertia protocol leaves room for choices, the server side follows the official [Laravel adapter](https://github.com/inertiajs/inertia-laravel).

## Disclaimer

This project is community-made and unofficial, and is not affiliated with Phalcon, Inertia.js or Laravel. It may break if the Inertia protocol changes.
