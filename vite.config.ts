import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from 'tailwindcss';
import autoprefixer from 'autoprefixer';
import { fileURLToPath, URL } from 'node:url';
import { copyFileSync } from 'node:fs';
import { resolve } from 'node:path';

function copyPdfWorker() {
    const src = resolve('node_modules/pdfjs-dist/build/pdf.worker.min.mjs');
    const dest = resolve('public/pdf.worker.js');
    return {
        name: 'copy-pdf-worker',
        buildStart() { copyFileSync(src, dest); },
        configureServer() { copyFileSync(src, dest); },
    };
}

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/app.tsx',
                'resources/images/favicon-32.png',
                'resources/images/favicon-64.png',
            ],
            refresh: true,
        }),
        react(),
        copyPdfWorker(),
    ],
    css: {
        postcss: {
            plugins: [tailwindcss({ config: './tailwind.config.ts' }), autoprefixer()],
        },
    },
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('resources/js', import.meta.url)),
        },
    },
    build: {
        rollupOptions: {
            output: {
                chunkFileNames: 'assets/[name]-[hash].js',
                entryFileNames: 'assets/[name]-[hash].js',
            },
        },
    },
});
