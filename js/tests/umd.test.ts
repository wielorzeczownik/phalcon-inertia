import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

import { expect, test } from 'vitest';

test('the UMD build gives a global route() that reads PhalconRoutes', () => {
  const bundle = readFileSync(
    new URL('../dist/route.umd.js', import.meta.url),
    'utf8'
  );
  const config = JSON.stringify({
    url: 'https://example.com',
    port: undefined,
    defaults: {},
    routes: { 'users.show': { uri: 'users/{id}', methods: ['GET'] } },
  });

  // eslint-disable-next-line sonarjs/code-eval
  const url: unknown = runInNewContext(
    `const PhalconRoutes = ${config};${bundle};route('users.show', 5)`
  );

  expect(url).toBe('https://example.com/users/5');
});
