import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            // Changed from .js to .ts to match your project structure
            input: 'resources/js/app.ts',
            // Added SSR entry point since your build command requires it
            ssr: 'resources/js/ssr.ts', 
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            // This ensures your '@/' imports work correctly in TypeScript
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
});