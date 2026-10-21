import { parse, toText } from "./query.js";
function withoutTrailingSlashes(text) {
    let end = text.length;
    while (end > 0 && text[end - 1] === '/') {
        end--;
    }
    return text.slice(0, end);
}
export default class Route {
    name;
    definition;
    config;
    bindings;
    wheres;
    constructor(name, definition, config) {
        this.name = name;
        this.definition = definition;
        this.config = config;
        this.bindings = definition.bindings ?? {};
        this.wheres = definition.wheres ?? {};
    }
    get template() {
        const template = withoutTrailingSlashes(`${this.origin}/${this.definition.uri}`);
        return template === '' ? '/' : template;
    }
    get origin() {
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
    get parameterSegments() {
        const segments = [];
        for (const [, name = '', optional] of this.template.matchAll(/\{(\w+)(\??)\}/g)) {
            segments.push({ name, required: optional === '' });
        }
        return segments;
    }
    matchesUrl(url) {
        if (!this.definition.methods.includes('GET')) {
            return undefined;
        }
        const pattern = this.template
            .replaceAll(/(\/?)\{(\w+)(\??)\}/g, (_match, slash, segment, optional) => {
            const where = this.wheres[segment]?.replaceAll(/^\^|\$$/g, '');
            const regex = `(?<${segment}>${where ?? '[^/?]+'})`;
            return optional ? `(${slash}${regex})?` : `${slash}${regex}`;
        })
            .replace(/^\w+:\/\//, '');
        const bare = url.replace(/^\w+:\/\//, '');
        const separator = bare.indexOf('?');
        const location = separator === -1 ? bare : bare.slice(0, separator);
        const query = separator === -1 ? '' : bare.slice(separator + 1);
        const matches = new RegExp(`^${pattern}/?$`).exec(decodeURI(location));
        if (!matches) {
            return undefined;
        }
        const groups = Object.entries(matches.groups ?? {});
        const params = {};
        for (const [key, value] of groups) {
            params[key] = value === undefined ? value : decodeURIComponent(value);
        }
        return { params, query: parse(query) };
    }
    compile(params) {
        if (this.parameterSegments.length === 0) {
            return this.template;
        }
        const origin = this.origin;
        const compiled = this.template
            .replaceAll(/\{(\w+)(\??)\}/g, (_match, segment, optional) => {
            const value = params[segment];
            if (!optional && (value === null || value === undefined)) {
                throw new Error(`Route error: '${segment}' parameter is required for route '${this.name}'.`);
            }
            const text = value === null || value === undefined ? '' : toText(value);
            const where = this.wheres[segment];
            const expected = optional ? `(${where})?` : where;
            if (where && !new RegExp(`^${expected}$`).test(text)) {
                throw new Error(`Route error: '${segment}' parameter does not match required format '${where}' for route '${this.name}'.`);
            }
            return encodeURI(text)
                .replaceAll('%7C', '|')
                .replaceAll('%25', '%')
                .replaceAll('$', '%24');
        })
            .replace(`${origin}//`, () => `${origin}/`);
        return withoutTrailingSlashes(compiled);
    }
}
