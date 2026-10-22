import Router, { globalConfig } from './router.ts';
import type { Config, RouteName, RouteParameters } from './types.ts';

export { default as Router } from './router.ts';
export type {
  Config,
  ParameterInfo,
  RouteDefinition,
  RouteList,
  RouteName,
  RouteParameters,
} from './types.ts';

/**
Returns a `Router` for the current URL, see `current()`, `has()` and `params`.
*/
export function route(): Router;
/**
Builds the URL of a named route.

@param name Route name set with `setName()` in the Phalcon router.
@param params Values for the route parameters, by name or by position. A model object resolves to its `id`,
  extra values become query parameters and `_query` adds query parameters explicitly.
@param isAbsolute `false` returns a path instead of a full URL.
@param config Route config, defaults to the global `PhalconRoutes` printed by `Routes::script()`.
@throws When the config is missing, the route is unknown, or a parameter is missing or breaks its constraint.
*/
export function route<T extends RouteName>(
  name: T,
  params?: RouteParameters<T>,
  isAbsolute?: boolean,
  config?: Config
): string;
/**
Returns a `Router` for the current URL, with the given config.
*/
export function route(
  name: undefined,
  params: undefined,
  isAbsolute?: boolean,
  config?: Config
): Router;
export function route(
  name?: string,
  params?: unknown,
  isAbsolute?: boolean,
  config?: Config
): string | Router {
  return build(name, params, isAbsolute, config);
}

export default route;

type BoundRoute = typeof route;

function build(
  name?: string,
  params?: unknown,
  isAbsolute?: boolean,
  config?: Config
): string | Router {
  const router = new Router(name, params, isAbsolute, config);

  return name ? router.toString() : router;
}

function bind(defaultConfig?: Config): BoundRoute {
  return ((
    name?: string,
    params?: unknown,
    isAbsolute?: boolean,
    config: Config | undefined = defaultConfig
  ) => build(name, params, isAbsolute, config)) as BoundRoute;
}

interface VueApp {
  version: string;
  config: { globalProperties: Record<string, unknown> };
  provide(key: string, value: unknown): unknown;
}

/**
Vue plugin that adds `route()` to templates and, on Vue 3, to `inject('route')`.
Its options are the route config, defaults to the global `PhalconRoutes`.
*/
export const RouteVue = {
  install(app: VueApp, options?: Config): void {
    const bound = bind(options);
    app.config.globalProperties.route = bound;
    if (Number(app.version.split('.', 1)[0]) > 2) {
      app.provide('route', bound);
    }
  },
};

/**
Returns `route()` bound to the given config, or to the global `PhalconRoutes`.

@throws When neither config is available.
*/
export function useRoute(defaultConfig?: Config): BoundRoute {
  if (!defaultConfig && !globalConfig()) {
    throw new Error(
      'Route error: missing configuration. Print Routes::script() or pass the config into useRoute().'
    );
  }

  return bind(defaultConfig);
}
