import Router from './router.ts';
import type { Config, RouteName, RouteParameters } from './types.ts';
export { default as Router } from './router.ts';
export type { Config, ParameterInfo, RouteDefinition, RouteList, RouteName, RouteParameters, } from './types.ts';
/**
Returns a `Router` for the current URL, see `current()`, `has()` and `params`.
*/
export declare function route(): Router;
/**
Builds the URL of a named route.

@param name Route name set with `setName()` in the Phalcon router.
@param params Values for the route parameters, by name or by position. A model object resolves to its `id`,
  extra values become query parameters and `_query` adds query parameters explicitly.
@param isAbsolute `false` returns a path instead of a full URL.
@param config Route config, defaults to the global `PhalconRoutes` printed by `Routes::script()`.
@throws When the config is missing, the route is unknown, or a parameter is missing or breaks its constraint.
*/
export declare function route<T extends RouteName>(name: T, params?: RouteParameters<T>, isAbsolute?: boolean, config?: Config): string;
/**
Returns a `Router` for the current URL, with the given config.
*/
export declare function route(name: undefined, params: undefined, isAbsolute?: boolean, config?: Config): Router;
export default route;
type BoundRoute = typeof route;
interface VueApp {
    version: string;
    config: {
        globalProperties: Record<string, unknown>;
    };
    provide(key: string, value: unknown): unknown;
}
/**
Vue plugin that adds `route()` to templates and, on Vue 3, to `inject('route')`.
Its options are the route config, defaults to the global `PhalconRoutes`.
*/
export declare const RouteVue: {
    install(app: VueApp, options?: Config): void;
};
/**
Returns `route()` bound to the given config, or to the global `PhalconRoutes`.

@throws When neither config is available.
*/
export declare function useRoute(defaultConfig?: Config): BoundRoute;
