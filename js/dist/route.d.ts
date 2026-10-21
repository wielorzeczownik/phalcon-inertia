import type { Config, RouteDefinition } from './types.ts';
export interface ParameterSegment {
    name: string;
    required: boolean;
}
export interface Match {
    params: Record<string, string | undefined>;
    query: Record<string, unknown>;
}
export default class Route {
    readonly name: string;
    readonly definition: RouteDefinition;
    readonly config: Config & {
        absolute: boolean;
    };
    readonly bindings: Record<string, string>;
    readonly wheres: Record<string, string>;
    constructor(name: string, definition: RouteDefinition, config: Config & {
        absolute: boolean;
    });
    get template(): string;
    get origin(): string;
    get parameterSegments(): ParameterSegment[];
    matchesUrl(url: string): Match | undefined;
    compile(params: Record<string, unknown>): string;
}
