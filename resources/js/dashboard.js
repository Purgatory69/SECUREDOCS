// ====================================================================
// Main Dashboard Initializer
// ====================================================================
// This script is the entry point for all frontend functionality
//

// --- Module Imports ---
import { initializeN8nChat } from './modules/n8n.js';
import { initializeUploadModal } from './modules/upload.js';
import { initializeUi, updateBreadcrumbsDisplay, initializeTooltips } from './modules/ui.js';
import { loadUserFiles, loadTrashItems, initializeFileFolderManagement } from './modules/file-folder.js';
import './modules/upload.js';
import './modules/blockchain-upload.js';
import './modules/client-arweave-modal.js';
import { loadBlockchainItems } from './modules/blockchain-page.js';
import { initializeBundlrWalletWidget } from './modules/bundlr-wallet-widget.js';
import { initializeSearch } from './modules/search.js';
// OLD: import { initializePermanentStorageModal } from './modules/permanent-storage.js'; // REMOVED - using client-side now
import { initializeClientArweaveModal } from './modules/client-arweave-modal.js';
import { NotificationManager } from './modules/notifications.js';
import StorageUsageManager from './modules/storage-usage.js';

// --- Supabase Client Check ---
if (!window.supabase || !window.SUPABASE_URL || !window.SUPABASE_KEY) {
}

// --- Helper to Get Translated "My Documents" Text ---
function getMyDocumentsText() {
    return window.I18N?.dbMyDocuments || 
           document.getElementById('db-js-localization-data')?.getAttribute('data-my-documents') || 
           'My Documents';
}

// --- Global State ---
// Centralized state management for the dashboard application.
const state = {
    lastMainSearch: '',
    currentPage: 1,
    currentParentId: localStorage.getItem('currentParentId') || null,
    // Use function to get translated text - will be evaluated when needed
    get breadcrumbs() {
        const stored = localStorage.getItem('breadcrumbs');
        if (stored) {
            try {
                const parsed = JSON.parse(stored);
                // Update root breadcrumb name with current translation
                if (parsed.length > 0 && (parsed[0].id === null || parsed[0].id === 'null')) {
                    parsed[0].name = getMyDocumentsText();
                }
                return parsed;
            } catch (e) {
                console.error('Error parsing breadcrumbs from localStorage:', e);
            }
        }
        // Default fallback with translated text
        return [{ id: null, name: getMyDocumentsText() }];
    },
    set breadcrumbs(value) {
        // When setting breadcrumbs, ensure root has translated name
        if (value && value.length > 0 && (value[0].id === null || value[0].id === 'null')) {
            value[0].name = getMyDocumentsText();
        }
        localStorage.setItem('breadcrumbs', JSON.stringify(value));
    }
};

// --- App Initialization ---
let appInitialized = false;

function initializeApp() {
    // Prevent multiple initializations
    if (appInitialized) {
        console.log('App already initialized, skipping...');
        return;
    }
    
    console.log('Initializing SecureDocs dashboard...');
    
    // Ensure hidden form inputs have the correct current folder ID.
    const currentFolderIdInput = document.getElementById('currentFolderId');
    if (currentFolderIdInput) {
        currentFolderIdInput.value = state.currentParentId;
    }
    // --- Initialize All Imported Modules ---
    // initializeN8nChat();
    initializeUploadModal();
    // Disabled for now: do not auto-open the Blockchain modal on sidebar click.
    // setupBlockchainLazyInit();
    
    // Modules requiring dependencies are initialized here.
    initializeSearch(loadUserFiles);
    // Expose for modules (e.g., upload.js) to refresh after actions
    window.loadUserFiles = loadUserFiles;
    window.loadTrashItems = loadTrashItems;
    initializeUi({
        loadUserFiles,
        loadTrashItems,
        loadBlockchainItems,
        state: { lastMainSearch: state.lastMainSearch }
    });
    initializeFileFolderManagement({
        currentParentId: state.currentParentId,
        breadcrumbs: state.breadcrumbs
    });

    // --- Initialize Client-Side Arweave System ---
    // Initialize the client-side Arweave modal (user pays directly with MetaMask)
    const clientArweaveModal = document.getElementById('clientArweaveModal');
    console.log('Client Arweave modal check:', !!clientArweaveModal);
    
    if (clientArweaveModal) {
        console.log('Initializing client-side Arweave modal...');
        initializeClientArweaveModal();
    } else {
        console.log('Skipping client Arweave initialization - modal not found');
    }
    
    // --- Initialize Bundlr Wallet Widget ---
    // Initialize the navigation wallet widget (real Bundlr integration)
    console.log('Initializing Bundlr wallet widget...');
    initializeBundlrWalletWidget();
    
    // --- Delayed Initialization (10 seconds) ---
    // Delay heavy API calls to improve initial page load performance
    setTimeout(() => {
        console.log('Initializing delayed services...');
        
        // --- Initialize Notification System ---
        // Initialize the notification bell and dropdown functionality
        window.notificationManager = new NotificationManager();
        
        // --- Initialize Storage Usage Manager ---
        // Initialize storage usage display and premium upgrade prompts (only on user dashboard)
        const storageUsageElement = document.getElementById('storageUsage');
        if (storageUsageElement) {
            window.storageManager = new StorageUsageManager();
        }
        
        console.log('Delayed services initialized!');
    }, 10000); // 10 second delay
    
    // --- Initial Data Load ---
    // Fetches the initial set of files for the root directory.
    loadUserFiles(state.lastMainSearch, state.currentPage, state.currentParentId);
    
    // Initialize tooltips after DOM is ready
    initializeTooltips();
    
    // Mark as initialized
    appInitialized = true;
    console.log('SecureDocs dashboard initialization complete!');
}

// --- Event Listeners for Initialization ---
window.debugTest = function() {
    console.log('[DEBUG TEST] Window function works!');
    return 'JavaScript is working';
};
document.addEventListener('DOMContentLoaded', () => {
    initializeApp();
});
document.addEventListener('livewire:load', () => {
    initializeApp();
});
document.addEventListener('livewire:update', () => {
    console.log('Livewire DOM update detected. Re-initializing dashboard modules.');
    initializeApp(); // Re-run all initializers to re-attach event listeners.
});