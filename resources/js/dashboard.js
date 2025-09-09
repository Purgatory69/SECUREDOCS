// ====================================================================
// Main Dashboard Initializer
// ====================================================================
// This script is the entry point for all frontend functionality
// on the main user dashboard. It imports and initializes all 
// feature modules.
//

// --- Module Imports ---
import { initializeN8nChat } from './modules/n8n.js';
import { initializeUploadModal } from './modules/upload.js';
import { initializeFileFolderManagement, loadUserFiles, loadTrashItems } from './modules/file-folder.js';
import { loadBlockchainItems } from './modules/blockchain-page.js';
import { initializeUi, initializeTooltips } from './modules/ui.js';
import { initializeSearch } from './modules/search.js';
// import { setupBlockchainLazyInit } from './modules/blockchain.js';
import { NotificationManager } from './modules/notifications.js';

// --- Supabase Client Check ---
if (!window.supabase || !window.SUPABASE_URL || !window.SUPABASE_KEY) {
    console.error('Supabase client not found. Ensure it is configured in .env and loaded in app.blade.php.');
}

// --- Global State ---
// Centralized state management for the dashboard application.
const state = {
    currentPage: 1,
    lastMainSearch: '',
    currentParentId: localStorage.getItem('currentParentId') || null,
    breadcrumbs: JSON.parse(localStorage.getItem('breadcrumbs')) || [{ id: null, name: 'My Documents' }]
};

// --- Application Initialization ---
function initializeApp() {
    // Ensure hidden form inputs have the correct current folder ID.
    const currentFolderIdInput = document.getElementById('currentFolderId');
    if (currentFolderIdInput) {
        currentFolderIdInput.value = state.currentParentId;
    }

    // --- Initialize All Imported Modules ---
    initializeN8nChat();
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

    // --- Initialize Notification System ---
    // Initialize the notification bell and dropdown functionality
    window.notificationManager = new NotificationManager();
    
    // --- Initial Data Load ---
    // Fetches the initial set of files for the root directory.
    loadUserFiles(state.lastMainSearch, state.currentPage, state.currentParentId);
    
    // Initialize tooltips after DOM is ready
    initializeTooltips();
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
