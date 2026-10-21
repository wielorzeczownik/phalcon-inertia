import { defineConfig } from 'vite';

export default defineConfig({
  build: {
    lib: {
      entry: 'src/browser.ts',
      name: 'route',
      formats: ['umd'],
      fileName: () => 'route.umd.js',
    },
    outDir: 'dist',
    emptyOutDir: false,
    sourcemap: false,
  },
});
