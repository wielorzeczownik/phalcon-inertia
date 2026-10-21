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

export function route(): Router;
export function route<T extends RouteName>(
  name: T,
  params?: RouteParameters<T>,
  isAbsolute?: boolean,
  config?: Config
): string;
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

export const RouteVue = {
  install(app: VueApp, options?: Config): void {
    const bound = bind(options);
    app.config.globalProperties.route = bound;
    if (Number(app.version.split('.', 1)[0]) > 2) {
      app.provide('route', bound);
    }
  },
};

export function useRoute(defaultConfig?: Config): BoundRoute {
  if (!defaultConfig && !globalConfig()) {
    throw new Error(
      'Route error: missing configuration. Print Routes::script() or pass the config into useRoute().'
    );
  }

  return bind(defaultConfig);
}
