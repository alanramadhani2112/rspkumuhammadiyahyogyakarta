import { defineConfig } from 'vite';

export default defineConfig({
  publicDir: false,
  build: {
    manifest: true,
    outDir: 'public/build',
    emptyOutDir: true,
    rollupOptions: {
      input: {
        app: 'resources/js/app.js',
        admin: 'resources/js/admin.js',
        editorBlocks: 'resources/js/editor-blocks.js',
      },
    },
  },
});
