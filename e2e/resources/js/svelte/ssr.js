import { createInertiaApp } from '@inertiajs/svelte';
import createServer from '@inertiajs/svelte/server';
import { render } from 'svelte/server';
import { resolve } from './pages.js';

createServer((page) =>
  createInertiaApp({
    page,
    resolve,
    setup: ({ App, props }) => render(App, { props }),
  })
);
