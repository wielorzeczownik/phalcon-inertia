import type { Config } from './types.ts';
type Parameters = Record<string, unknown>;
export declare function globalConfig(): Config | undefined;
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
    get params(): Parameters;
    current(): string | undefined;
    current(name: string, params?: unknown): boolean;
    has(name: string): boolean;
}
export {};
