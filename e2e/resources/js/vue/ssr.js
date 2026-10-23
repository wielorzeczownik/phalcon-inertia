import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { createSSRApp, h } from 'vue';
import { renderToString } from 'vue/server-renderer';
import { RouteVue } from 'phalcon-routes';
import { resolve } from './pages.js';

createServer((page) =>
  createInertiaApp({
    page,
    render: renderToString,
    resolve,
    setup({ App, props, plugin }) {
      const routes = props.initialPage.props.routes;
      return createSSRApp({ render: () => h(App, props) })
        .use(plugin)
        .use(RouteVue, { ...routes, location: new URL(page.url, routes.url) });
    },
  })
);
