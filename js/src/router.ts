import { stringify, toText } from './query.ts';
import Route from './route.ts';
import type { Config, RouteDefinition } from './types.ts';

type Parameters = Record<string, unknown>;

type AbsoluteConfig = Config & { absolute: boolean };

interface Unresolved {
  name?: string;
  params: Parameters;
  query: Parameters;
  route?: RouteDefinition;
}

declare const PhalconRoutes: Config | undefined;

export function globalConfig(): Config | undefined {
  return typeof PhalconRoutes === 'undefined'
    ? (globalThis as { PhalconRoutes?: Config }).PhalconRoutes
    : PhalconRoutes;
}

function isObject(value: unknown): value is Parameters {
  return typeof value === 'object' && value !== null;
}

function isMissing(value: unknown): value is null | undefined {
  return value === null || value === undefined;
}

function isLooselyEqual(left: unknown, right: unknown): boolean {
  return isMissing(left) || isMissing(right)
    ? isMissing(left) && isMissing(right)
    : toText(left) === toText(right);
}

function isSubset(subset: Parameters, full: Parameters): boolean {
  return Object.entries(subset).every(([key, value]) => {
    const current = full[key];
    if (Array.isArray(value) && Array.isArray(current)) {
      return value.every((item) =>
        current.some((candidate) => isLooselyEqual(candidate, item))
      );
    }
    return isObject(value) && isObject(current)
      ? isSubset(value, current)
      : isLooselyEqual(value, current);
  });
}

/**
Result of `route()`. With a route name it converts to the URL, without one it inspects the current URL.
*/
export default class Router extends String {
  private readonly config: AbsoluteConfig;
  private readonly route?: Route;
  private readonly parameters: Parameters = {};

  constructor(
    name?: string,
    params?: unknown,
    isAbsolute = true,
    config?: Config
  ) {
    super();

    const resolved = config ?? globalConfig();
    if (!resolved) {
      throw new Error(
        'Route error: missing configuration. Print Routes::script() or pass the config to RouteVue or useRoute().'
      );
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

  private unresolve(url?: string): Unresolved {
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

  private currentUrl(): string {
    const { host, pathname, search } = this.location();

    return (this.config.absolute ? host + pathname : pathname) + search;
  }

  private location(): { host: string; pathname: string; search: string } {
    const browser = (globalThis as { window?: { location: Location } }).window;
    const fallback = browser?.location;

    return {
      host: this.config.location?.host ?? fallback?.host ?? '',
      pathname: this.config.location?.pathname ?? fallback?.pathname ?? '',
      search: this.config.location?.search ?? fallback?.search ?? '',
    };
  }

  private parse(params: unknown, route: Route): Parameters {
    const value =
      typeof params === 'string' || typeof params === 'number'
        ? [params]
        : params;
    const defaults = this.config.defaults;
    const segments = route.parameterSegments.filter(
      ({ name }) => defaults[name] === undefined
    );

    let normalized: Parameters = {};
    if (Array.isArray(value)) {
      for (const [index, item] of (value as unknown[]).entries()) {
        const segment = segments[index];
        if (segment) {
          normalized[segment.name] = item;
        } else if (isObject(item)) {
          Object.assign(normalized, item);
        } else {
          normalized[toText(item)] = '';
        }
      }
    } else if (isObject(value)) {
      const [only] = segments;
      const binding = Object.values(route.bindings)[0];
      const isModel =
        segments.length === 1 &&
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

  private defaults(route: Route): Parameters {
    return Object.fromEntries(
      route.parameterSegments
        .filter(({ name }) => this.config.defaults[name] !== undefined)
        .map(({ name }) => [name, this.config.defaults[name]])
    );
  }

  private substituteBindings(params: Parameters, route: Route): Parameters {
    const names = new Set(route.parameterSegments.map(({ name }) => name));

    return Object.fromEntries(
      Object.entries(params).map(([key, value]) => {
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

        throw new Error(
          `Route error: object passed as '${key}' parameter is missing route model binding key '${binding ?? 'id'}'.`
        );
      })
    );
  }

  override toString(): string {
    const route = this.route;
    if (!route) {
      return '';
    }

    const names = new Set(route.parameterSegments.map(({ name }) => name));
    const unhandled = Object.fromEntries(
      Object.entries(this.parameters).filter(
        ([key]) => key !== '_query' && !names.has(key)
      )
    );
    const query = this.parameters._query;

    return (
      route.compile(this.parameters) +
      stringify({ ...unhandled, ...(isObject(query) && query) })
    );
  }

  override valueOf(): string {
    return this.toString();
  }

  /**
  Parameters and query of the current URL. Values are strings, as they come from the URL.
  */
  get params(): Parameters {
    const { params, query } = this.unresolve();

    return { ...params, ...query };
  }

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
  // eslint-disable-next-line sonarjs/function-return-type -- the overloads give each call one return type
  current(name?: string, params?: unknown): string | boolean | undefined {
    const {
      name: current,
      params: currentParams,
      query,
      route,
    } = this.unresolve();

    if (!name) {
      return current;
    }

    const pattern = name.replaceAll('.', String.raw`\.`).replaceAll('*', '.*');
    const isMatch =
      current !== undefined && new RegExp(`^${pattern}$`).test(current);

    if (!isMatch || !route || isMissing(params)) {
      return isMatch;
    }

    const parsed = this.parse(params, new Route(current, route, this.config));
    const routeParams = { ...currentParams, ...query };
    const isEmpty =
      Object.values(parsed).every((value) => !value) &&
      Object.values(routeParams).every((value) => value === undefined);

    return isEmpty || isSubset(parsed, routeParams);
  }

  /**
  Whether the route is in the route list.
  */
  has(name: string): boolean {
    return Object.hasOwn(this.config.routes, name);
  }
}
