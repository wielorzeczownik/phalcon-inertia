import Router, { globalConfig } from "./router.js";
export { default as Router } from "./router.js";
export function route(name, params, isAbsolute, config) {
    return build(name, params, isAbsolute, config);
}
export default route;
function build(name, params, isAbsolute, config) {
    const router = new Router(name, params, isAbsolute, config);
    return name ? router.toString() : router;
}
function bind(defaultConfig) {
    return ((name, params, isAbsolute, config = defaultConfig) => build(name, params, isAbsolute, config));
}
/**
Vue plugin that adds `route()` to templates and, on Vue 3, to `inject('route')`.
Its options are the route config, defaults to the global `PhalconRoutes`.
*/
export const RouteVue = {
    install(app, options) {
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
export function useRoute(defaultConfig) {
    if (!defaultConfig && !globalConfig()) {
        throw new Error('Route error: missing configuration. Print Routes::script() or pass the config into useRoute().');
    }
    return bind(defaultConfig);
}
