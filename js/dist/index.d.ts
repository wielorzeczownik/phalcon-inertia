import Router from './router.ts';
import type { Config, RouteName, RouteParameters } from './types.ts';
export { default as Router } from './router.ts';
export type { Config, ParameterInfo, RouteDefinition, RouteList, RouteName, RouteParameters, } from './types.ts';
export declare function route(): Router;
export declare function route<T extends RouteName>(name: T, params?: RouteParameters<T>, isAbsolute?: boolean, config?: Config): string;
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
export declare const RouteVue: {
    install(app: VueApp, options?: Config): void;
};
export declare function useRoute(defaultConfig?: Config): BoundRoute;
