import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

// Conditional NativePHP import
let nativephpMobile, nativephpHotFile;
const isVercel = process.env.VERCEL === '1' || process.env.NODE_ENV === 'production';

export default defineConfig(async () => {
    const input = [
        'resources/css/app.css', 
        'resources/js/app.js', 
        'resources/css/filament/admin/theme.css', 
        'resources/js/echo.js'
    ];

    // Only add nativephp adapter if NOT on Vercel
    if (!isVercel) {
        input.push('./vendor/nativephp/mobile/resources/js/phpProtocolAdapter.js');
    }

    const plugins = [
        laravel({
            input,
            refresh: true,
        }),
        tailwindcss(),
    ];

    if (!isVercel) {
        try {
            const nativephp = await import('./vendor/nativephp/mobile/resources/js/vite-plugin.js');
            nativephpMobile = nativephp.nativephpMobile;
            nativephpHotFile = nativephp.nativephpHotFile;
            
            if (nativephpMobile) plugins.push(nativephpMobile());
            // Update laravel plugin with hotFile if available
            plugins[0].config.hotFile = nativephpHotFile ? nativephpHotFile() : undefined;
        } catch (e) {
            console.warn('NativePHP plugins not found, skipping...');
        }
    }

    return {
        plugins,
        server: {
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
        build: {
            outDir: 'public/build',
            sourcemap: false,
        },
    };
});
