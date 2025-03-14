import { defineConfig } from 'vite';
import laravel, { refreshPaths } from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: [
                'resources/css/app.css',                           // Asset della sandbox
                'resources/js/app.js',                             // Asset della sandbox
                'packages/ultra/uploadmanager/resources/css/app.css',      // Asset del pacchetto
                'packages/ultra/uploadmanager/resources/js/app.js',        // Asset del pacchetto
                'packages/ultra/uploadmanager/resources/ts/core/file_upload_manager.ts' // Asset del pacchetto
            ],
            refresh: [
                ...refreshPaths,
                'resources/views/**',                              // Viste della sandbox
                'packages/ultra/uploadmanager/resources/views/**', // Viste del pacchetto
            ],
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
            '@ts': '/resources/ts',
            // Aggiungi alias per il pacchetto se necessario
            '@uploadmanager': '/packages/ultra/uploadmanager/resources',
            '@ultra-images': '/packages/ultra/uploadmanager/resources/ts/assets/images',
        },
    },
    build: {
        outDir: 'public/build', // Genera nella root della sandbox
        manifest: true,
        sourcemap: true, // Opzionale, rimuovilo in production
    },
    server: {
        watch: {
            usePolling: true, // Per WSL2/Windows
        },
        hmr: {
            host: 'localhost',
            port: 5173,
        },
    },
    fs: {
        allow: [
            './resources',
            './packages/ultra/uploadmanager/resources',
        ],
    },
});
