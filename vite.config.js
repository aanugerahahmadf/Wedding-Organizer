import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { nativephpMobile, nativephpHotFile } from './vendor/nativephp/mobile/resources/js/vite-plugin.js'; 

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/filament/admin/theme.css', './vendor/nativephp/mobile/resources/js/phpProtocolAdapter.js', 'resources/css/mobile-cards.css', './vendor/asmit/filament-upload/resources/css/advanced-file-upload.css', './vendor/asmit/filament-upload/resources/js/advanced-file-upload.js', './vendor/asmit/filament-upload/resources/js/pdf-preview-plugin.js', 'resources/js/echo.js'],
            refresh: true,
            hotFile: nativephpHotFile(),
        }),
        tailwindcss(),
        nativephpMobile(), 
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
