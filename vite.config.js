import { defineConfig } from 'vite';
import laravel, { refreshPaths } from 'laravel-vite-plugin'; // Assicurati che il plugin sia installato
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: [
                'resources/css/app.css',                           // Asset della sandbox
                'resources/js/app.js',                             // Asset della sandbox
                'packages/ultra/uploadmanager/resources/css/app.css',      // Asset del pacchetto
                'packages/ultra/uploadmanager/resources/js/app.js',        // Asset del pacchetto
                'packages/ultra/uploadmanager/resources/ts/file_upload_manager.ts' // Asset del pacchetto
            ],
            refresh: [
                ...refreshPaths,
                'resources/views/**', // Aggiungi refresh per le viste del pacchetto
            ],
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
            '@ts': '/resources/ts',
        },
    },
    build: {
        outDir: 'public/build',
        manifest: true,
        sourcemap: true, // Utile per sviluppo, opzionale per produzione
    },
    server: {
        watch: {
            usePolling: true, // Importante per WSL2/Windows 10
        },
        hmr: {
            host: 'localhost',
            port: 5173, // Porto per il server di sviluppo
        },
    },
});

