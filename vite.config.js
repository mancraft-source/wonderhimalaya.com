import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Fraunces', { weights: [300, 400, 500, 600], styles: ['normal', 'italic'], fallbacks: ['Georgia', 'serif'], preload: [{ weight: 400 }] }),
                bunny('Inter', { weights: [400, 500, 600, 700], fallbacks: ['ui-sans-serif', 'system-ui', 'sans-serif'], preload: [{ weight: 400 }, { weight: 600 }] }),
                bunny('JetBrains Mono', { weights: [400, 500], fallbacks: ['ui-monospace', 'monospace'], preload: false }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
