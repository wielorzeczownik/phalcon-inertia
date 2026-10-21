import { stringify, toText } from "./query.js";
import Route from "./route.js";
export function globalConfig() {
    return typeof PhalconRoutes === 'undefined'
        ? globalThis.PhalconRoutes
        : PhalconRoutes;
}
function isObject(value) {
    return typeof value === 'object' && value !== null;
}
function isMissing(value) {
    return value === null || value === undefined;
}
function isLooselyEqual(left, right) {
    return isMissing(left) || isMissing(right)
        ? isMissing(left) && isMissing(right)
        : toText(left) === toText(right);
}
function isSubset(subset, full) {
    return Object.entries(subset).every(([key, value]) => {
        const current = full[key];
        if (Array.isArray(value) && Array.isArray(current)) {
            return value.every((item) => current.some((candidate) => isLooselyEqual(candidate, item)));
        }
        return isObject(value) && isObject(current)
            ? isSubset(value, current)
            : isLooselyEqual(value, current);
    });
}
export default class Router extends String {
    config;
    route;
    parameters = {};
    constructor(name, params, isAbsolute = true, config) {
        super();
        const resolved = config ?? globalConfig();
        if (!resolved) {
            throw new Error('Route error: missing configuration. Print Routes::script() or pass the config to RouteVue or useRoute().');
        }
        this.config = { ...resolved, absolute: isAbsolute };
        if (!name) {
            return;
        }
        const definition = this.config.routes[name];
        if (!definition) {
            throw new Error(`Route error: route '${name}' is not in the route list.`);
        }
        this.route = new Route(name, definition, this.config);
        this.parameters = this.parse(params, this.route);
    }
    unresolve(url) {
        const isRelative = url?.startsWith('/') === true;
        let target = url ?? this.currentUrl();
        if (isRelative && this.config.absolute) {
            target = this.location().host + target;
        }
        const routes = Object.entries(this.config.routes).toReversed();
        for (const [name, definition] of routes) {
            const route = new Route(name, definition, this.config);
            const matched = route.matchesUrl(target);
            if (matched) {
                return { name, ...matched, route: definition };
            }
        }
        return { params: {}, query: {} };
    }
    currentUrl() {
        const { host, pathname, search } = this.location();
        return (this.config.absolute ? host + pathname : pathname) + search;
    }
    location() {
        const browser = globalThis.window;
        const fallback = browser?.location;
        return {
            host: this.config.location?.host ?? fallback?.host ?? '',
            pathname: this.config.location?.pathname ?? fallback?.pathname ?? '',
            search: this.config.location?.search ?? fallback?.search ?? '',
        };
    }
    parse(params, route) {
        const value = typeof params === 'string' || typeof params === 'number'
            ? [params]
            : params;
        const defaults = this.config.defaults;
        const segments = route.parameterSegments.filter(({ name }) => defaults[name] === undefined);
        let normalized = {};
        if (Array.isArray(value)) {
            for (const [index, item] of value.entries()) {
                const segment = segments[index];
                if (segment) {
                    normalized[segment.name] = item;
                }
                else if (isObject(item)) {
                    Object.assign(normalized, item);
                }
                else {
                    normalized[toText(item)] = '';
                }
            }
        }
        else if (isObject(value)) {
            const [only] = segments;
            const binding = Object.values(route.bindings)[0];
            const isModel = segments.length === 1 &&
                only !== undefined &&
                value[only.name] === undefined &&
                ((binding !== undefined && value[binding] !== undefined) ||
                    value.id !== undefined);
            normalized = isModel && only ? { [only.name]: value } : value;
        }
        return {
            ...this.defaults(route),
            ...this.substituteBindings(normalized, route),
        };
    }
    defaults(route) {
        return Object.fromEntries(route.parameterSegments
            .filter(({ name }) => this.config.defaults[name] !== undefined)
            .map(({ name }) => [name, this.config.defaults[name]]));
    }
    substituteBindings(params, route) {
        const names = new Set(route.parameterSegments.map(({ name }) => name));
        return Object.fromEntries(Object.entries(params).map(([key, value]) => {
            if (!names.has(key) || !isObject(value) || Array.isArray(value)) {
                return [key, value];
            }
            const binding = route.bindings[key];
            const bound = binding === undefined ? undefined : value[binding];
            if (bound !== undefined) {
                return [key, bound];
            }
            if (value.id !== undefined) {
                return [key, value.id];
            }
            throw new Error(`Route error: object passed as '${key}' parameter is missing route model binding key '${binding ?? 'id'}'.`);
        }));
    }
    toString() {
        const route = this.route;
        if (!route) {
            return '';
        }
        const names = new Set(route.parameterSegments.map(({ name }) => name));
        const unhandled = Object.fromEntries(Object.entries(this.parameters).filter(([key]) => key !== '_query' && !names.has(key)));
        const query = this.parameters._query;
        return (route.compile(this.parameters) +
            stringify({ ...unhandled, ...(isObject(query) && query) }));
    }
    valueOf() {
        return this.toString();
    }
    get params() {
        const { params, query } = this.unresolve();
        return { ...params, ...query };
    }
    // eslint-disable-next-line sonarjs/function-return-type -- the overloads give each call one return type
    current(name, params) {
        const { name: current, params: currentParams, query, route, } = this.unresolve();
        if (!name) {
            return current;
        }
        const pattern = name.replaceAll('.', String.raw `\.`).replaceAll('*', '.*');
        const isMatch = current !== undefined && new RegExp(`^${pattern}$`).test(current);
        if (!isMatch || !route || isMissing(params)) {
            return isMatch;
        }
        const parsed = this.parse(params, new Route(current, route, this.config));
        const routeParams = { ...currentParams, ...query };
        const isEmpty = Object.values(parsed).every((value) => !value) &&
            Object.values(routeParams).every((value) => value === undefined);
        return isEmpty || isSubset(parsed, routeParams);
    }
    has(name) {
        return Object.hasOwn(this.config.routes, name);
    }
}
