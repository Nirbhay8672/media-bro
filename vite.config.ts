// import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        // Temporarily disabled wayfinder to fix build issues
        // wayfinder({
        //     formVariants: true,
        //     output: 'resources/js/wayfinder',
        // }),
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
            '../createLucideIcon.js': resolve(__dirname, 'node_modules/lucide-vue-next/dist/esm/createLucideIcon.js'),
        },
    },
});
