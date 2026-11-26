import { resolve } from 'node:path';
import { defineConfig } from 'vite';

export default defineConfig({
    publicDir: false,
    build: {
        outDir: 'public/build',
        emptyOutDir: true,
        rollupOptions: {
            input: resolve(import.meta.dirname, 'assets/app.js'),
            output: {
                entryFileNames: 'app.js',
                assetFileNames: 'app.[ext]',
            },
        },
    },
});
