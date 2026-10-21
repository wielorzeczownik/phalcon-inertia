import { parse, toText } from './query.ts';
import type { Config, RouteDefinition } from './types.ts';

export interface ParameterSegment {
  name: string;
  required: boolean;
}

export interface Match {
  params: Record<string, string | undefined>;
  query: Record<string, unknown>;
}

function withoutTrailingSlashes(text: string): string {
  let end = text.length;
  while (end > 0 && text[end - 1] === '/') {
    end--;
  }
  return text.slice(0, end);
}

export default class Route {
  readonly bindings: Record<string, string>;
  readonly wheres: Record<string, string>;

  constructor(
    readonly name: string,
    readonly definition: RouteDefinition,
    readonly config: Config & { absolute: boolean }
  ) {
    this.bindings = definition.bindings ?? {};
    this.wheres = definition.wheres ?? {};
  }

  get template(): string {
    const template = withoutTrailingSlashes(
      `${this.origin}/${this.definition.uri}`
    );

    return template === '' ? '/' : template;
  }

  get origin(): string {
    const basePath = this.config.url.replace(/^\w+:\/\/[^/]+/, '');
    if (!this.config.absolute) {
      return basePath;
    }
    if (!this.definition.domain) {
      return this.config.url;
    }
    const scheme = /^\w+:\/\//.exec(this.config.url)?.[0] ?? '';
    const port = this.config.port ? `:${this.config.port}` : '';

    return `${scheme}${this.definition.domain}${port}${basePath}`;
  }

  get parameterSegments(): ParameterSegment[] {
    const segments: ParameterSegment[] = [];
    for (const [, name = '', optional] of this.template.matchAll(
      /\{(\w+)(\??)\}/g
    )) {
      segments.push({ name, required: optional === '' });
    }
    return segments;
  }

  matchesUrl(url: string): Match | undefined {
    if (!this.definition.methods.includes('GET')) {
      return undefined;
    }

    const pattern = this.template
      .replaceAll(
        /(\/?)\{(\w+)(\??)\}/g,
        (_match, slash: string, segment: string, optional: string) => {
          const where = this.wheres[segment]?.replaceAll(/^\^|\$$/g, '');
          const regex = `(?<${segment}>${where ?? '[^/?]+'})`;
          return optional ? `(${slash}${regex})?` : `${slash}${regex}`;
        }
      )
      .replace(/^\w+:\/\//, '');

    const bare = url.replace(/^\w+:\/\//, '');
    const separator = bare.indexOf('?');
    const location = separator === -1 ? bare : bare.slice(0, separator);
    const query = separator === -1 ? '' : bare.slice(separator + 1);
    const matches = new RegExp(`^${pattern}/?$`).exec(decodeURI(location));
    if (!matches) {
      return undefined;
    }

    const groups = Object.entries(matches.groups ?? {}) as [
      string,
      string | undefined,
    ][];
    const params: Record<string, string | undefined> = {};
    for (const [key, value] of groups) {
      params[key] = value === undefined ? value : decodeURIComponent(value);
    }

    return { params, query: parse(query) };
  }

  compile(params: Record<string, unknown>): string {
    if (this.parameterSegments.length === 0) {
      return this.template;
    }

    const origin = this.origin;
    const compiled = this.template
      .replaceAll(
        /\{(\w+)(\??)\}/g,
        (_match, segment: string, optional: string) => {
          const value = params[segment];
          if (!optional && (value === null || value === undefined)) {
            throw new Error(
              `Route error: '${segment}' parameter is required for route '${this.name}'.`
            );
          }

          const text =
            value === null || value === undefined ? '' : toText(value);
          const where = this.wheres[segment];
          const expected = optional ? `(${where})?` : where;
          if (where && !new RegExp(`^${expected}$`).test(text)) {
            throw new Error(
              `Route error: '${segment}' parameter does not match required format '${where}' for route '${this.name}'.`
            );
          }

          return encodeURI(text)
            .replaceAll('%7C', '|')
            .replaceAll('%25', '%')
            .replaceAll('$', '%24');
        }
      )
      .replace(`${origin}//`, () => `${origin}/`);

    return withoutTrailingSlashes(compiled);
  }
}
