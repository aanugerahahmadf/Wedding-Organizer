import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import fs from 'fs';

// Conditionally load NativePhp plugin if it exists
let nativephpMobile = () => {};
let nativephpHotFile = () => '';

const nativephpPath = './vendor/nativephp/mobile/resources/js/vite-plugin.js';
if (fs.existsSync(nativephpPath)) {
    const nativephp = await import(nativephpPath);
    nativephpMobile = nativephp.nativephpMobile;
    nativephpHotFile = nativephp.nativephpHotFile;
}

const inputFiles = [
    'resources/css/app.css',
    'resources/js/app.js',
    'resources/css/filament/admin/theme.css',
    'resources/css/mobile-cards.css',
    'resources/js/echo.js',
];

// Only add NativePhp files if they exist
if (fs.existsSync('./vendor/nativephp/mobile/resources/js/phpProtocolAdapter.js')) {
    inputFiles.splice(3, 0, './vendor/nativephp/mobile/resources/js/phpProtocolAdapter.js');
}

// Only add filament-upload files if they exist
if (fs.existsSync('./vendor/asmit/filament-upload')) {
    inputFiles.push(
        './vendor/asmit/filament-upload/resources/css/advanced-file-upload.css',
        './vendor/asmit/filament-upload/resources/js/advanced-file-upload.js',
        './vendor/asmit/filament-upload/resources/js/pdf-preview-plugin.js'
    );
}

export default defineConfig({
    plugins: [
        laravel({
            input: inputFiles,
            refresh: true,
            hotFile: nativephpHotFile(),
        }),
        tailwindcss(),
        ...(nativephpMobile ? [nativephpMobile()] : []),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
