import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// 📱 NATIVEPHP MOBILE ADAPTER 📱
// iOS uses 'php://' protocol which standard axios/fetch don't understand.
if (window.location.protocol === 'php:') {
    try {
        // Use the adapter from the vendor directory
        import('../../vendor/nativephp/mobile/resources/js/phpProtocolAdapter.js').then((module) => {
            const phpAdapter = module.default;
            window.axios.defaults.adapter = phpAdapter;
            
            // Also override global fetch for Livewire compatibility
            const originalFetch = window.fetch;
            window.fetch = function(url, options) {
                if (typeof url === 'string' && url.startsWith('/')) {
                    url = window.location.origin + url;
                }
                return originalFetch(url, options);
            };
            
            console.log('NativePHP: iOS Protocol Adapter loaded.');
        });
    } catch (e) {
        console.error('NativePHP: Failed to load protocol adapter', e);
    }
}

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import './echo';
