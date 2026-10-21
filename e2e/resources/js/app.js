import { createInertiaApp } from '@inertiajs/vue3';
import { createSSRApp, h } from 'vue';
import { RouteVue } from 'phalcon-routes';
import { resolve } from './pages.js';

createInertiaApp({
  resolve,
  setup({ el, App, props, plugin }) {
    createSSRApp({ render: () => h(App, props) })
      .use(plugin)
      .use(RouteVue)
      .mount(el);
  },
});
