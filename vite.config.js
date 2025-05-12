// vite.config.js
import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
      laravel({
        input: ['resources/css/app.css', 'resources/js/app.js'],
        refresh: true,
      }),
    ],
    define: {
      'process.env': {
        VITE_SUPABASE_URL: JSON.stringify(process.env.SUPABASE_URL),
        VITE_SUPABASE_KEY: JSON.stringify(process.env.SUPABASE_KEY)
      }
    }
  });