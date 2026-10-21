export interface RouteList {
}
type KnownRouteName = keyof RouteList;
export type RouteName = KnownRouteName | (string & {});
export interface ParameterInfo {
    name: string;
    required?: boolean;
    binding?: string;
}
type RawParameterValue = string | number;
type DefaultRoutable = {
    id: RawParameterValue;
} & Record<PropertyKey, unknown>;
type ParameterValue = RawParameterValue | DefaultRoutable;
type Routable<I extends ParameterInfo> = I extends {
    binding: string;
} ? {
    [K in I['binding']]: RawParameterValue;
} | RawParameterValue : ParameterValue;
interface HasQueryParameter {
    _query?: Record<string, unknown>;
}
type GenericRouteParametersObject = Record<PropertyKey, unknown> & HasQueryParameter;
type KnownRouteParametersObject<I extends readonly ParameterInfo[]> = {
    [T in I[number] as T['name']]?: Routable<T>;
} & GenericRouteParametersObject;
type RouteParametersObject<N extends RouteName> = N extends KnownRouteName ? RouteList[N] extends readonly ParameterInfo[] ? KnownRouteParametersObject<RouteList[N]> : GenericRouteParametersObject : GenericRouteParametersObject;
type GenericRouteParametersArray = unknown[];
type KnownRouteParametersArray<I extends readonly ParameterInfo[]> = [
    ...{
        [K in keyof I]: Routable<I[K]>;
    },
    ...unknown[]
];
type RouteParametersArray<N extends RouteName> = N extends KnownRouteName ? RouteList[N] extends readonly ParameterInfo[] ? KnownRouteParametersArray<RouteList[N]> : GenericRouteParametersArray : GenericRouteParametersArray;
export type RouteParameters<N extends RouteName> = ParameterValue | RouteParametersObject<N> | RouteParametersArray<N>;
export interface RouteDefinition {
    uri: string;
    methods: ('GET' | 'HEAD' | 'POST' | 'PATCH' | 'PUT' | 'OPTIONS' | 'DELETE')[];
    domain?: string;
    parameters?: string[];
    bindings?: Record<string, string>;
    wheres?: Record<string, string>;
}
export interface Config {
    url: string;
    port: number | null;
    defaults: Record<string, RawParameterValue>;
    routes: Record<string, RouteDefinition>;
    location?: {
        host?: string;
        pathname?: string;
        search?: string;
    };
}
export {};
