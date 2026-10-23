import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { renderToString } from 'react-dom/server';
import { resolve } from './pages.js';

createServer((page) =>
  createInertiaApp({
    page,
    render: renderToString,
    resolve,
    setup: ({ App, props }) => <App {...props} />,
  })
);
