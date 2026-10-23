import path from 'node:path';

import { svelte } from '@sveltejs/vite-plugin-svelte';
import react from '@vitejs/plugin-react';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

const client = process.env.CLIENT ?? 'vue';

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/js/app.js'],
      ssr: 'resources/js/ssr.js',
    }),
    vue(),
    react(),
    svelte(),
  ],
  resolve: {
    alias: {
      '@client': path.resolve(import.meta.dirname, 'resources/js', client),
      'phalcon-routes': path.resolve(import.meta.dirname, '../js'),
    },
  },
});
