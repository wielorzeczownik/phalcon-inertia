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
export const RouteVue = {
    install(app, options) {
        const bound = bind(options);
        app.config.globalProperties.route = bound;
        if (Number(app.version.split('.', 1)[0]) > 2) {
            app.provide('route', bound);
        }
    },
};
export function useRoute(defaultConfig) {
    if (!defaultConfig && !globalConfig()) {
        throw new Error('Route error: missing configuration. Print Routes::script() or pass the config into useRoute().');
    }
    return bind(defaultConfig);
}
