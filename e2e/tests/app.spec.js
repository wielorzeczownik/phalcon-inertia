import { expect, test } from '@playwright/test';

let browserErrors = [];

test.beforeEach(({ page }) => {
  browserErrors = [];
  page.on('pageerror', (error) => browserErrors.push(error.message));
  page.on('console', (message) => {
    if (
      message.type() === 'error' &&
      !/status of (404|409|419|422)/.test(message.text())
    ) {
      browserErrors.push(message.text());
    }
  });
});

test.afterEach(() => {
  expect(browserErrors).toEqual([]);
});

async function markPage(page) {
  await page.evaluate(() => {
    window.__sameDocument = true;
  });
}

async function isSameDocument(page) {
  return page.evaluate(() => window.__sameDocument === true);
}

test('first visit is server rendered and hydrates', async ({
  page,
  request,
}) => {
  const html = await (await request.get('/')).text();
  expect(html).toContain('data-server-rendered="true"');
  expect(html).toContain('Ada Lovelace');
  expect(html).toContain('<script data-page="app" type="application/json">');

  await page.goto('/');
  await expect(page.getByTestId('auth')).toHaveText('Signed in as Ada');
});

test('links navigate without a full page load', async ({ page }) => {
  await page.goto('/');
  await markPage(page);

  await page.getByRole('link', { name: 'Linus Torvalds' }).click();

  await expect(page).toHaveURL('/users/2');
  await expect(page.getByRole('heading')).toHaveText('Linus Torvalds');
  expect(await isSameDocument(page)).toBe(true);
});

test('validation errors come back and a valid form redirects with flash', async ({
  page,
}) => {
  await page.goto('/users/create');
  await page.getByRole('button', { name: 'Create' }).click();

  await expect(page.getByTestId('error')).toHaveText(
    'The name must have at least 2 characters.'
  );
  await expect(page).toHaveURL('/users/create');

  await page.getByLabel('Name').fill('Grace Hopper');
  await page.getByRole('button', { name: 'Create' }).click();

  await expect(page).toHaveURL('/users/3');
  await expect(page.getByRole('heading')).toHaveText('Grace Hopper');
  await expect(page.getByTestId('flash')).toHaveText('User created.');
});

test('precognition validates a field live without submitting', async ({
  page,
}) => {
  await page.goto('/users/create');
  await markPage(page);

  const [invalid] = await Promise.all([
    page.waitForResponse((r) => r.url().endsWith('/users')),
    page
      .getByLabel('Name')
      .fill('A')
      .then(() => page.getByLabel('Name').press('Tab')),
  ]);

  expect(invalid.status()).toBe(422);
  expect(invalid.headers()['precognition']).toBe('true');
  await expect(page.getByTestId('error')).toHaveText(
    'The name must have at least 2 characters.'
  );
  await expect(page).toHaveURL('/users/create');

  await page.getByLabel('Name').fill('Ada');
  await page.getByLabel('Name').press('Tab');

  await expect(page.getByTestId('error')).toHaveCount(0);
  await expect(page).toHaveURL('/users/create');
  expect(await isSameDocument(page)).toBe(true);
});

test('put redirects with 303 and shows the update', async ({ page }) => {
  await page.goto('/users/1');
  await page.getByLabel('Rename').fill('Ada King');

  const [response] = await Promise.all([
    page.waitForResponse(
      (r) => r.url().endsWith('/users/1') && r.request().method() === 'PUT'
    ),
    page.getByRole('button', { name: 'Save' }).click(),
  ]);

  expect(response.status()).toBe(303);
  await expect(page.getByRole('heading')).toHaveText('Ada King');
  await expect(page.getByTestId('flash')).toHaveText('User updated.');
});

test('deferred, partial and optional props load on demand', async ({
  page,
}) => {
  await page.goto('/dashboard');

  await expect(page.getByTestId('stats')).toHaveText('Visits: 42');
  await expect(page.getByTestId('stats-title')).toHaveText('Stats');
  await expect(page.getByTestId('audit')).toHaveText('no audit');
  const first = Number(await page.getByTestId('counter').textContent());

  await page.getByRole('button', { name: 'Refresh counter' }).click();
  await expect(page.getByTestId('counter')).toHaveText(String(first + 1));

  await page.getByRole('button', { name: 'Load audit' }).click();
  await expect(page.getByTestId('audit')).toHaveText('audit log loaded');
  await expect(page.getByTestId('stats')).toHaveText('Visits: 42');
  await expect(page.getByTestId('stats-title')).toHaveText('Stats');
});

test('an empty response redirects back instead of failing', async ({
  page,
}) => {
  await page.goto('/dashboard');
  await markPage(page);

  const [response] = await Promise.all([
    page.waitForResponse((r) => r.url().endsWith('/noop')),
    page.getByRole('button', { name: 'Do nothing' }).click(),
  ]);

  expect(response.status()).toBe(302);
  await expect(page).toHaveURL('/dashboard');
  await expect(page.getByRole('heading')).toHaveText('Dashboard');
  expect(await isSameDocument(page)).toBe(true);
});

test('a new asset version forces a full reload', async ({ page, context }) => {
  await page.goto('/');
  await markPage(page);
  await context.addCookies([
    { name: 'asset_version', value: '2', url: page.url() },
  ]);

  await page.getByRole('link', { name: 'Dashboard' }).click();

  await expect(page).toHaveURL('/dashboard');
  await expect(page.getByRole('heading')).toHaveText('Dashboard');
  expect(await isSameDocument(page)).toBe(false);
});

test('location() leaves with a full page visit', async ({ page }) => {
  await page.goto('/');
  await markPage(page);

  await page.getByRole('link', { name: 'Leave' }).click();

  await expect(page).toHaveURL('/dashboard?external=1');
  expect(await isSameDocument(page)).toBe(false);
});

test('unknown routes render the error page with 404', async ({ page }) => {
  const response = await page.goto('/missing');

  expect(response.status()).toBe(404);
  await expect(page.getByRole('heading')).toHaveText('Error 404');
});

test('the root view can change per request', async ({ page }) => {
  await page.goto('/admin');

  await expect(page.locator('#admin-layout h1')).toHaveText('Admin');
});

test('writes without the XSRF token are rejected', async ({ page }) => {
  await page.goto('/');

  const response = await page.request.post('/users', {
    data: { name: 'Mallory' },
  });

  expect(response.status()).toBe(419);
});

test('a deferred prop that fails is rescued while the rest loads', async ({
  page,
}) => {
  await page.goto('/dashboard');

  await expect(page.getByTestId('permissions')).toHaveText(
    'Permissions failed'
  );
  await expect(page.getByTestId('stats')).toHaveText('Visits: 42');
});

test('a once prop is not resolved again when the client has it', async ({
  page,
}) => {
  await page.goto('/dashboard');
  const plans = await page.getByTestId('plans').textContent();

  const [response] = await Promise.all([
    page.waitForResponse((r) => r.url().endsWith('/dashboard')),
    page.getByRole('link', { name: 'Visit again' }).click(),
  ]);

  expect(response.request().headers()['x-inertia-except-once-props']).toBe(
    'plans'
  );
  expect((await response.json()).props).not.toHaveProperty('plans');
  await expect(page.getByTestId('plans')).toHaveText(plans);
});

test('a redirect with a fragment keeps the fragment', async ({ page }) => {
  await page.goto('/');
  await markPage(page);

  await page.getByRole('link', { name: 'Jump' }).click();

  await expect(page).toHaveURL('/dashboard#stats');
  await expect(page.getByRole('heading')).toHaveText('Dashboard');
  expect(await isSameDocument(page)).toBe(true);
});

test('infinite scroll loads the next pages of a Phalcon paginator', async ({
  page,
}) => {
  await page.goto('/feed');
  await expect(page.getByTestId('item').first()).toHaveText('Item 1');

  await page.getByTestId('item').last().scrollIntoViewIfNeeded();

  await expect(page.getByTestId('item')).toHaveCount(30);
  await expect(page.getByTestId('item').last()).toHaveText('Item 30');
});
