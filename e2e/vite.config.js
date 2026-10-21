import path from 'node:path';

import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/js/app.js'],
      ssr: 'resources/js/ssr.js',
    }),
    vue(),
  ],
  resolve: {
    alias: {
      'phalcon-routes': path.resolve(import.meta.dirname, '../js'),
    },
  },
});
