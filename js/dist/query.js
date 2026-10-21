export function toText(value) {
    if (typeof value === 'string') {
        return value;
    }
    if (value instanceof Date) {
        return value.toISOString();
    }
    const isPrimitive = typeof value === 'number' ||
        typeof value === 'boolean' ||
        typeof value === 'bigint';
    return isPrimitive ? String(value) : JSON.stringify(value);
}
function encodeValue(value) {
    return encodeURIComponent(typeof value === 'boolean' ? String(Number(value)) : toText(value));
}
function pairs(key, value) {
    if (value === null || value === undefined) {
        return [];
    }
    return typeof value === 'object' && !(value instanceof Date)
        ? Object.entries(value).flatMap(([name, nested]) => pairs(`${key}[${name}]`, nested))
        : [`${key}=${encodeValue(value)}`];
}
export function stringify(query) {
    const encoded = Object.entries(query).flatMap(([key, value]) => pairs(key, value));
    return encoded.length > 0 ? `?${encoded.join('&')}` : '';
}
function decode(text) {
    try {
        return decodeURIComponent(text.replaceAll('+', ' '));
    }
    catch {
        return text;
    }
}
function keyPath(key) {
    const open = key.indexOf('[');
    if (open <= 0) {
        return [key];
    }
    const path = [key.slice(0, open)];
    let rest = key.slice(open);
    while (rest.startsWith('[')) {
        const close = rest.indexOf(']');
        if (close === -1) {
            break;
        }
        path.push(rest.slice(1, close));
        rest = rest.slice(close + 1);
    }
    return path;
}
function assign(target, key, value) {
    if (key === '' && Array.isArray(target)) {
        target.push(value);
    }
    else {
        target[key] = value;
    }
}
function child(target, key, next) {
    const existing = key === '' ? undefined : target[key];
    if (typeof existing === 'object' && existing !== null) {
        return existing;
    }
    const created = next === '' || /^\d+$/.test(next) ? [] : {};
    assign(target, key, created);
    return created;
}
export function parse(query = '') {
    const result = {};
    const pairsText = query.startsWith('?') ? query.slice(1) : query;
    for (const pair of pairsText.split('&')) {
        if (pair === '') {
            continue;
        }
        const separator = pair.indexOf('=');
        const key = decode(separator === -1 ? pair : pair.slice(0, separator));
        const value = separator === -1 ? '' : decode(pair.slice(separator + 1));
        const path = keyPath(key);
        const parents = path.slice(0, -1);
        let target = result;
        for (const [index, segment] of parents.entries()) {
            target = child(target, segment, path[index + 1] ?? '');
        }
        assign(target, path.at(-1) ?? key, value);
    }
    return result;
}
