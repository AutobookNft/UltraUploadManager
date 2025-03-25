import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                // 'packages/ultra/uploadmanager/resources/css/app.css',
                'packages/ultra/uploadmanager/resources/js/app.js',
                'packages/ultra/uploadmanager/resources/ts/core/file_upload_manager.ts'
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
            '@ts': '/resources/ts',
            '@ultra-images': '/packages/ultra/uploadmanager/resources/ts/assets/images',
        },
        preserveSymlinks: true,
    },
    build: {
        outDir: 'public/build',
        manifest: true,
        sourcemap: true,
    },
    server: {
        watch: {
            usePolling: true,
        },
        // hmr: {
        //     host: 'localhost',
        //     port: 5173,
        // },
        fs: {
            allow: [
                './resources',
                './packages/ultra/uploadmanager/resources',
            ],
        },
    },
});
