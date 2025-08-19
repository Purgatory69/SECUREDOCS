import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/dashboard.js', 'resources/js/webauthn-handler.js', 'resources/js/file-preview.js'],
            refresh: true,
        }),
    ],
    server: {
        cors: {
            origin: '*', // Or specify your application's origin, e.g., 'https://8000-firebase-securedocsimprovedgit-1748177464118.cluster-xpmcxs2fjnhg6xvn446ubtgpio.cloudworkstations.dev'
        },
    },
});
