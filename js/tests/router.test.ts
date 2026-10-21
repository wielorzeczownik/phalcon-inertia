import { afterEach, expect, test, vi } from 'vitest';

import route, { type Config, RouteVue, useRoute } from '../src/index.ts';

const config: Config = {
  url: 'https://example.com/app',
  // eslint-disable-next-line unicorn/no-null
  port: null,
  defaults: {},
  routes: {
    home: { uri: '/', methods: ['GET', 'HEAD'] },
    'users.index': { uri: 'users', methods: ['GET', 'HEAD'] },
    'users.store': { uri: 'users', methods: ['POST'] },
    'users.show': {
      uri: 'users/{id}',
      methods: ['GET'],
      parameters: ['id'],
      wheres: { id: '[0-9]+' },
    },
    'posts.show': { uri: 'posts/{slug}', methods: ['GET'] },
    'posts.create': { uri: 'posts/create', methods: ['GET'] },
    'posts.comments': {
      uri: 'posts/{post}/comments/{comment}',
      methods: ['GET'],
    },
    'shop.show': {
      uri: 'items/{id}',
      methods: ['GET'],
      domain: 'shop.example.com',
    },
    'files.show': {
      uri: 'files/{path}',
      methods: ['GET'],
      wheres: { path: '.+' },
    },
  },
};

function locatedAt(pathname: string, search = ''): Config {
  return { ...config, location: { host: 'example.com', pathname, search } };
}

afterEach(() => {
  vi.unstubAllGlobals();
});

test('builds absolute URLs by default, with the base URI', () => {
  expect(route('users.show', { id: 5 }, undefined, config)).toBe(
    'https://example.com/app/users/5'
  );
  expect(route('home', undefined, undefined, config)).toBe(
    'https://example.com/app'
  );
});

test('relative URLs keep the Phalcon base URI', () => {
  expect(route('users.show', 5, false, config)).toBe('/app/users/5');
  expect(route('home', undefined, false, config)).toBe('/app');
});

test('accepts a value, an array or an object, and objects with an id', () => {
  expect(route('posts.comments', [1, { id: 2 }], false, config)).toBe(
    '/app/posts/1/comments/2'
  );
  expect(route('users.show', { id: 3, tab: 'posts' }, false, config)).toBe(
    '/app/users/3?tab=posts'
  );
  expect(
    route(
      'posts.comments',
      { post: { id: 7, title: 'Hello' }, comment: 2 },
      false,
      config
    )
  ).toBe('/app/posts/7/comments/2');
});

test('encodes the query string like qs in Ziggy', () => {
  expect(
    route(
      'users.index',
      {
        filter: { tags: ['a b', 'c'] },
        active: true,
        // eslint-disable-next-line unicorn/no-null -- nulls are skipped, as in qs
        missing: null,
        _query: { page: 2 },
      },
      false,
      config
    )
  ).toBe('/app/users?filter[tags][0]=a%20b&filter[tags][1]=c&active=1&page=2');
});

test('validates required parameters, wheres and route names', () => {
  expect(() => route('users.show', {}, false, config)).toThrow(
    "'id' parameter is required for route 'users.show'"
  );
  expect(() => route('users.show', { id: 'abc' }, false, config)).toThrow(
    "does not match required format '[0-9]+'"
  );
  expect(() => route('nope', {}, false, config)).toThrow(
    "route 'nope' is not in the route list"
  );
});

test('default parameter values fill in and can be overridden', () => {
  const localized: Config = {
    ...config,
    defaults: { locale: 'en' },
    routes: {
      'posts.show': { uri: '{locale}/posts/{post}', methods: ['GET'] },
    },
  };

  expect(route('posts.show', 1, false, localized)).toBe('/app/en/posts/1');
  expect(route('posts.show', { locale: 'pl', post: 1 }, false, localized)).toBe(
    '/app/pl/posts/1'
  );
});

test('uses the route hostname as the domain', () => {
  expect(route('shop.show', 4, undefined, config)).toBe(
    'https://shop.example.com/app/items/4'
  );
});

test('wheres can allow slashes in a parameter', () => {
  expect(route('files.show', { path: 'a/b c.txt' }, false, config)).toBe(
    '/app/files/a/b%20c.txt'
  );
});

test('current() names the route and matches names, wildcards and params', () => {
  const router = route(
    undefined,
    undefined,
    undefined,
    locatedAt('/app/users/5', '?tab=x')
  );

  expect(router.current()).toBe('users.show');
  expect(router.current('users.show')).toBe(true);
  expect(router.current('users.*')).toBe(true);
  expect(router.current('posts.*')).toBe(false);
  expect(router.current('users.show', { id: 5 })).toBe(true);
  expect(router.current('users.show', { id: 6 })).toBe(false);
  expect(router.current('users.show', { tab: 'x' })).toBe(true);
});

test('current() checks routes in Phalcon order, last added first', () => {
  const router = route(
    undefined,
    undefined,
    undefined,
    locatedAt('/app/posts/create')
  );

  expect(router.current()).toBe('posts.create');
});

test('params and has() read the current URL and the route list', () => {
  const router = route(
    undefined,
    undefined,
    undefined,
    locatedAt('/app/users/5', '?tab=x')
  );

  expect(router.params).toEqual({ id: '5', tab: 'x' });
  expect(router.has('users.show')).toBe(true);
  expect(router.has('nope')).toBe(false);
});

test('reads the global PhalconRoutes and window.location', () => {
  vi.stubGlobal('PhalconRoutes', config);
  vi.stubGlobal('window', {
    location: { host: 'example.com', pathname: '/app/users', search: '' },
  });

  expect(route('users.show', 1)).toBe('https://example.com/app/users/1');
  expect(route().current()).toBe('users.index');
});

test('explains how to load the configuration when there is none', () => {
  expect(() => route('users.show', 1)).toThrow(/missing configuration/);
  expect(() => useRoute()).toThrow(/missing configuration/);
});

test('RouteVue exposes a bound route() to templates and inject()', () => {
  const provide = vi.fn();
  const app = {
    version: '3.5.0',
    config: { globalProperties: {} as Record<string, unknown> },
    provide,
  };
  RouteVue.install(app, config);

  const bound = app.config.globalProperties.route as typeof route;
  expect(bound('users.show', 2)).toBe('https://example.com/app/users/2');
  expect(provide).toHaveBeenCalledWith('route', bound);
});

test('RouteVue without options reads the global PhalconRoutes, like ZiggyVue', () => {
  vi.stubGlobal('PhalconRoutes', config);
  const app = {
    version: '3.5.0',
    config: { globalProperties: {} as Record<string, unknown> },
    provide: vi.fn(),
  };
  RouteVue.install(app);

  const bound = app.config.globalProperties.route as typeof route;
  expect(bound('users.show', 2)).toBe('https://example.com/app/users/2');
});

test('useRoute() binds a configuration, as needed under SSR', () => {
  const bound = useRoute(locatedAt('/app/users/9'));

  expect(bound('users.show', 9, false)).toBe('/app/users/9');
  expect(bound().current('users.show', { id: 9 })).toBe(true);
});
