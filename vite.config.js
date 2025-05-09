// vite.config.js
import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig(({ command, mode }) => {
    // Load .env variables
    const env = loadEnv(mode, process.cwd(), '');
    
    // Determine the public host for HMR from VITE_DEV_SERVER_URL or APP_URL
    let hmrHost;
    if (env.VITE_DEV_SERVER_URL) {
        try {
            const url = new URL(env.VITE_DEV_SERVER_URL);
            hmrHost = url.hostname;
        } catch (e) {
            console.error("Invalid VITE_DEV_SERVER_URL for HMR host:", env.VITE_DEV_SERVER_URL);
            // Fallback or default if URL is invalid
            hmrHost = new URL(env.APP_URL).hostname;
        }
    } else {
        hmrHost = new URL(env.APP_URL).hostname;
    }

    return {
        base: '/',
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
            }),
            tailwindcss(),
        ],
        server: {
            host: '0.0.0.0', // Vite listens on all internal interfaces
            hmr: {
                host: hmrHost,       // Public hostname for HMR client
                protocol: 'wss',     // Secure WebSockets
                // clientPort: 443, // Usually not needed if using standard HTTPS ports
                                   // and correct VITE_DEV_SERVER_URL
            },
        },
    };
});