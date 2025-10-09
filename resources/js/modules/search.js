// Contains all logic for main search and advanced search.

/**
 * Functions to move here from dashboard.js:
 * - initializeSearch()
 * - initializeAdvancedSearch()
 * - openAdvancedSearchModal()
 * - closeAdvancedSearchModal()
 * - handleAdvancedSearchSubmit()
 */

import { showNotification } from './ui.js';

// --- Module State ---
let loadUserFilesCallback;

// --- Initialization ---
export function initializeSearch(loadUserFiles) {
    loadUserFilesCallback = loadUserFiles;
    initializeAdvancedSearchModal();

    const mainSearchInput = document.getElementById('mainSearchInput');
    const mainSearchButton = document.getElementById('mainSearchButton');

    const performMainSearch = () => {
        const query = mainSearchInput.value.trim();
        // Search across ALL folders (pass null as parent_id to get flattened results)
        // This mimics Google Drive behavior - search shows all matching files regardless of folder
        loadUserFilesCallback(query, 1, null);
    };

    mainSearchButton?.addEventListener('click', performMainSearch);
    mainSearchInput?.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            performMainSearch();
        }
    });
}

// --- Helper Functions (Stubs/Implementations) ---
function closeAdvancedSearch() {
    const modal = document.getElementById('advancedSearchModal');
    if(modal) modal.classList.add('hidden');
}

function performAdvancedSearch(query, filters) {
    console.log("Performing advanced search with:", { query, filters });
    loadUserFilesCallback(query, 1, null, filters);
}

// --- Advanced Search Modal Logic ---
function initializeAdvancedSearchModal() {
    const modal = document.getElementById('advancedSearchModal');
    const openBtn = document.getElementById('advanced-search-button');
    const form = document.getElementById('advancedSearchForm');
    const closeBtn = document.getElementById('advancedSearchCloseBtn');
    const cancelBtn = document.getElementById('cancelAdvancedSearch');
    const clearFiltersBtn = document.getElementById('clearSearchFilters');
    openBtn?.addEventListener('click', () => {
        // Check if we're in the main "My Documents" view
        const filesContainer = document.getElementById('filesContainer');
        const currentView = filesContainer?.dataset?.view || 'main';
        
        if (currentView !== 'main') {
            showNotification('Advanced search is only available in My Documents view', 'info');
            return;
        }
        
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    });
    
    [closeBtn, cancelBtn].forEach(btn => btn?.addEventListener('click', closeAdvancedSearch));
    clearFiltersBtn?.addEventListener('click', () => form?.reset());

    form?.addEventListener('submit', (e) => {
        e.preventDefault();
        performAdvancedSearchFromForm();
        closeAdvancedSearch();
    });
}

function performAdvancedSearchFromForm() {
    const form = document.getElementById('advancedSearchForm');
    if (!form) return;

    const query = document.getElementById('advancedSearchQuery')?.value?.trim() || '';
    const filters = {
        // Search options
        match_type: document.getElementById('searchMatchType')?.value || 'contains',
        case_sensitive: document.getElementById('searchCaseSensitive')?.value || 'insensitive',
        whole_word: document.getElementById('searchWholeWord')?.checked || false,
        
        // File filters
        type: document.getElementById('searchFileType')?.value || '',
        date_from: document.getElementById('searchDateFrom')?.value || '',
        date_to: document.getElementById('searchDateTo')?.value || '',
        size_min: document.getElementById('searchSizeMin')?.value || '',
        size_max: document.getElementById('searchSizeMax')?.value || '',
        shared: document.getElementById('searchShared')?.value || '',
        
        // Sort options
        sort_by: document.getElementById('searchSortBy')?.value || 'updated_at',
        sort_order: document.getElementById('searchSortOrder')?.value || 'desc'
    };

    Object.keys(filters).forEach(key => {
        if (filters[key] === '' || filters[key] === false) delete filters[key];
    });

    performAdvancedSearch(query, filters);
}





function escapeHtml(str) {
    return str
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
