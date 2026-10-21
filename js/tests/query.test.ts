import { expect, test } from 'vitest';

import { parse, stringify } from '../src/query.ts';

test('stringify skips nulls and encodes dates and booleans', () => {
  expect(
    stringify({
      at: new Date('2026-01-02T03:04:05.000Z'),
      on: false,
      no: undefined,
    })
  ).toBe('?at=2026-01-02T03%3A04%3A05.000Z&on=0');
  expect(stringify({})).toBe('');
});

test('parse reads nested objects, arrays and plus signs', () => {
  expect(parse('a[b]=1&c[]=2&c[]=3&d[0]=x&e=x+y&f')).toEqual({
    a: { b: '1' },
    c: ['2', '3'],
    d: ['x'],
    e: 'x y',
    f: '',
  });
});
