import type { Config } from './types.ts';
type Parameters = Record<string, unknown>;
export declare function globalConfig(): Config | undefined;
/**
Result of `route()`. With a route name it converts to the URL, without one it inspects the current URL.
*/
export default class Router extends String {
    private readonly config;
    private readonly route?;
    private readonly parameters;
    constructor(name?: string, params?: unknown, isAbsolute?: boolean, config?: Config);
    private unresolve;
    private currentUrl;
    private location;
    private parse;
    private defaults;
    private substituteBindings;
    toString(): string;
    valueOf(): string;
    /**
    Parameters and query of the current URL. Values are strings, as they come from the URL.
    */
    get params(): Parameters;
    /**
    Name of the route matching the current URL, `undefined` when none does.
    */
    current(): string | undefined;
    /**
    Whether the current URL matches the route.
    @param name Route name, `*` matches any part of it, e.g. `posts.*`.
    @param params Values that the current parameters and query must contain.
    */
    current(name: string, params?: unknown): boolean;
    /**
    Whether the route is in the route list.
    */
    has(name: string): boolean;
}
export {};
