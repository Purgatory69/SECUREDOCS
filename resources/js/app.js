import './bootstrap';
import './modules/notifications.js';
import './modules/ai-categorization.js';

// Import WebAuthn scripts in the correct order
// First import the vendor WebAuthn library to ensure it's available globally
// import './vendor/webauthn';
// Then import our custom handler that depends on window.WebAuthn
import './webauthn-handler';

// -------------------------------------------------------------
// Supabase client bootstrap and upload helper
// -------------------------------------------------------------
(function bootstrapSupabase() {
    function ensureSupabaseClient() {
        try {
            if (!window.supabaseClient && window.supabase && window.SUPABASE_URL && window.SUPABASE_KEY) {
                window.supabaseClient = window.supabase.createClient(window.SUPABASE_URL, window.SUPABASE_KEY);
            }
        } catch (e) {
            console.error('Failed to initialize Supabase client:', e);
        }
        return window.supabaseClient;
    }

    function simulateProgress(file, onProgress) {
        if (typeof onProgress !== 'function') return { stop: () => {} };
        const total = Math.max(1, file?.size || 1);
        const start = Date.now();
        const minDuration = Math.max(800, Math.min(6000, total / 4000)); // ms
        let stopped = false;
        const id = setInterval(() => {
            if (stopped) return;
            const elapsed = Date.now() - start;
            const ratio = Math.min(0.9, elapsed / minDuration);
            const loaded = Math.floor(total * ratio);
            onProgress({ loaded, total });
        }, 120);
        return { stop: () => { stopped = true; clearInterval(id); } };
    }

    // Expose a single helper used by upload.js
    window.uploadFileToSupabase = async function uploadFileToSupabase(file, onProgress) {
        const client = ensureSupabaseClient();
        if (!client) {
            throw new Error('Supabase client not found. Ensure SUPABASE_URL/KEY are set and the CDN script is loaded.');
        }

        const bucket = 'docs';
        const userId = window.userId || 'anonymous';
        const safeName = (file?.name || `file_${Date.now()}`).replace(/[^a-zA-Z0-9._-]/g, '_');
        const key = `user_${userId}/${Date.now()}_${safeName}`;

        // Simulate progress while the SDK uploads in a single request
        const progressTimer = simulateProgress(file, onProgress);

        const { data, error } = await client.storage
            .from(bucket)
            .upload(key, file, {
                cacheControl: '3600',
                upsert: false,
                contentType: file?.type || 'application/octet-stream'
            });

        // Stop simulation and finish to 100%
        progressTimer.stop();
        if (typeof onProgress === 'function') {
            onProgress({ loaded: file?.size || 1, total: file?.size || 1 });
        }

        if (error) {
            console.error('Supabase upload error:', error);
            throw new Error(error.message || 'Failed to upload to storage');
        }

        // Return the storage key (path relative to bucket), which backend expects as file_path
        return data?.path || key;
    };
})();
