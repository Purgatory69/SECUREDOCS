import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css',
                    'resources/js/app.js',
                    'resources/js/dashboard.js', 
                    'resources/js/webauthn-handler.js', 
                    'resources/js/file-preview.js',
<<<<<<< HEAD
                    'resources/js/modules/blockchain-upload.js',
                    'resources/js/modules/blockchain.js',
                    'resources/js/modules/file-folder.js',
                    'resources/js/modules/blockchain-page.js',
=======
>>>>>>> origin/language-feature
                    ],
            refresh: true,
        }),
    ],
    server: {
        cors: {
            origin: '*', // Or specify your application's origin, e.g., 'https://8000-firebase-securedocsimprovedgit-1748177464118.cluster-xpmcxs2fjnhg6xvn446ubtgpio.cloudworkstations.dev'
        },
    },
});
