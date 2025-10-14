import './bootstrap';
import './modules/notifications.js';
import './modules/ai-categorization.js';

// Global error handler for debugging
window.addEventListener('error', (e) => {
    console.error('🚨 Global JavaScript Error:', {
        message: e.message,
        filename: e.filename,
        lineno: e.lineno,
        colno: e.colno,
        error: e.error,
        stack: e.error?.stack
    });
});

window.addEventListener('unhandledrejection', (e) => {
    console.error('🚨 Unhandled Promise Rejection:', e.reason);
});

// WebAuthn scripts are now only loaded on pages that specifically need them
// Removed global import to prevent conflicts on regular login page

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
        console.log('🚀 uploadFileToSupabase called with:', { 
            file: file, 
            hasSize: file && 'size' in file,
            size: file?.size,
            name: file?.name,
            type: file?.type 
        });
        
        const client = ensureSupabaseClient();
        if (!client) {
            throw new Error('Supabase client not found. Ensure SUPABASE_URL/KEY are set and the CDN script is loaded.');
        }

        // Validate file parameter
        if (!file) {
            console.error('❌ File is null/undefined in uploadFileToSupabase');
            throw new Error('File is required for upload');
        }
        
        if (typeof file.size !== 'number') {
            console.error('❌ File.size is not a number:', file.size, typeof file.size);
            throw new Error('Invalid file: size property is missing or invalid');
        }

        const bucket = 'docs';
        const userId = window.userId || 'anonymous';
        const safeName = (file.name || `file_${Date.now()}`).replace(/[^a-zA-Z0-9._-]/g, '_');
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
            const fileSize = file && file.size ? file.size : 1;
            onProgress({ loaded: fileSize, total: fileSize });
        }

        if (error) {
            console.error('Supabase upload error:', error);
            throw new Error(error.message || 'Failed to upload to storage');
        }

        // Return the storage key (path relative to bucket), which backend expects as file_path
        return data?.path || key;
    };
})();
