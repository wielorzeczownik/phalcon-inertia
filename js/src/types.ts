/**
Route names and their parameters. `Routes::types()` fills it through declaration merging,
which makes route names and parameters type checked.
*/
// eslint-disable-next-line @typescript-eslint/no-empty-object-type -- filled by declaration merging from Routes::types()
export interface RouteList {}

type KnownRouteName = keyof RouteList;

// eslint-disable-next-line @typescript-eslint/no-redundant-type-constituents -- (string & {}) keeps autocompletion of known names
export type RouteName = KnownRouteName | (string & {});

/**
Parameter of a route in `RouteList`.
*/
export interface ParameterInfo {
  name: string;
  required?: boolean;
  /**
  Model field that an object passed as this parameter resolves to, instead of `id`.
  */
  binding?: string;
}

type RawParameterValue = string | number;

type DefaultRoutable = { id: RawParameterValue } & Record<PropertyKey, unknown>;

type ParameterValue = RawParameterValue | DefaultRoutable;

type Routable<I extends ParameterInfo> = I extends { binding: string }
  ? { [K in I['binding']]: RawParameterValue } | RawParameterValue
  : ParameterValue;

interface HasQueryParameter {
  /**
  Query parameters, kept even when their names match route parameters.
  */
  _query?: Record<string, unknown>;
}

type GenericRouteParametersObject = Record<PropertyKey, unknown> &
  HasQueryParameter;

type KnownRouteParametersObject<I extends readonly ParameterInfo[]> = {
  [T in I[number] as T['name']]?: Routable<T>;
} & GenericRouteParametersObject;

type RouteParametersObject<N extends RouteName> = N extends KnownRouteName
  ? RouteList[N] extends readonly ParameterInfo[]
    ? KnownRouteParametersObject<RouteList[N]>
    : GenericRouteParametersObject
  : GenericRouteParametersObject;

type GenericRouteParametersArray = unknown[];

type KnownRouteParametersArray<I extends readonly ParameterInfo[]> = [
  ...{ [K in keyof I]: Routable<I[K]> },
  ...unknown[],
];

type RouteParametersArray<N extends RouteName> = N extends KnownRouteName
  ? RouteList[N] extends readonly ParameterInfo[]
    ? KnownRouteParametersArray<RouteList[N]>
    : GenericRouteParametersArray
  : GenericRouteParametersArray;

/**
Parameters of `route()`: one value, an object by name, or an array by position.
*/
export type RouteParameters<N extends RouteName> =
  ParameterValue | RouteParametersObject<N> | RouteParametersArray<N>;

/**
Route as exported by `Routes::toArray()`.
*/
export interface RouteDefinition {
  uri: string;
  methods: ('GET' | 'HEAD' | 'POST' | 'PATCH' | 'PUT' | 'OPTIONS' | 'DELETE')[];
  domain?: string;
  parameters?: string[];
  /**
  Model field per parameter, used instead of `id` when an object is passed.
  */
  bindings?: Record<string, string>;
  /**
  Regex constraint per parameter, from the Phalcon route pattern.
  */
  wheres?: Record<string, string>;
}

/**
Route config printed by `Routes::script()` or exported by `Routes::toArray()` and `Routes::toJson()`.
*/
export interface Config {
  /**
  Base URL of the app, including the Phalcon base URI.
  */
  url: string;
  port: number | null;
  /**
  Values used for parameters that are not passed, from the `defaults` option.
  */
  defaults: Record<string, RawParameterValue>;
  routes: Record<string, RouteDefinition>;
  /**
  Current URL for `current()` and `params`, defaults to `window.location`. Needed under SSR.
  */
  location?: {
    host?: string;
    pathname?: string;
    search?: string;
  };
}
