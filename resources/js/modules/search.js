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
        // Assuming a global or passed-in state for currentParentId
        loadUserFilesCallback(query, 1, localStorage.getItem('currentParentId'));
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
    const saveSearchBtn = document.getElementById('saveSearchBtn');

    openBtn?.addEventListener('click', () => modal.classList.remove('hidden'));
    [closeBtn, cancelBtn].forEach(btn => btn?.addEventListener('click', closeAdvancedSearch));
    clearFiltersBtn?.addEventListener('click', () => form?.reset());

    form?.addEventListener('submit', (e) => {
        e.preventDefault();
        performAdvancedSearchFromForm();
        closeAdvancedSearch();
    });

    saveSearchBtn?.addEventListener('click', () => saveCurrentSearch());

    loadSavedSearches();
}

function performAdvancedSearchFromForm() {
    const form = document.getElementById('advancedSearchForm');
    if (!form) return;

    const query = document.getElementById('advancedSearchQuery')?.value?.trim() || '';
    const filters = {
        type: document.getElementById('searchFileType')?.value || '',
        date_from: document.getElementById('searchDateFrom')?.value || '',
        date_to: document.getElementById('searchDateTo')?.value || '',
        size_min: document.getElementById('searchSizeMin')?.value || '',
        size_max: document.getElementById('searchSizeMax')?.value || '',
        shared: document.getElementById('searchShared')?.value || '',
        sort_by: document.getElementById('searchSortBy')?.value || 'updated_at',
        sort_order: document.getElementById('searchSortOrder')?.value || 'desc'
    };

    Object.keys(filters).forEach(key => {
        if (filters[key] === '') delete filters[key];
    });

    performAdvancedSearch(query, filters);
}

async function saveCurrentSearch() {
    const query = document.getElementById('advancedSearchQuery')?.value?.trim();
    if (!query) {
        showNotification('Please enter a search query first', 'warning');
        return;
    }

    const name = prompt('Enter a name for this search:');
    if (!name) return;

    const filters = {
        type: document.getElementById('searchFileType')?.value || '',
        date_from: document.getElementById('searchDateFrom')?.value || '',
        date_to: document.getElementById('searchDateTo')?.value || '',
        size_min: document.getElementById('searchSizeMin')?.value || '',
        size_max: document.getElementById('searchSizeMax')?.value || '',
        shared: document.getElementById('searchShared')?.value || '',
        sort_by: document.getElementById('searchSortBy')?.value || 'updated_at',
        sort_order: document.getElementById('searchSortOrder')?.value || 'desc'
    };

    try {
        const response = await fetch('/search/save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({ name, query, filters })
        });

        const data = await response.json();
        if (response.ok) {
            showNotification('Search saved successfully!', 'success');
            loadSavedSearches();
        } else {
            throw new Error(data.message || 'Failed to save search');
        }
    } catch (error) {
        console.error('Error saving search:', error);
        showNotification(error.message || 'Failed to save search', 'error');
    }
}

async function loadSavedSearches() {
    const container = document.getElementById('savedSearchesList');
    if (!container) return;

    try {
        const response = await fetch('/search/saved', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        if (!response.ok) throw new Error('Failed to load saved searches');

        const searches = await response.json();
        container.innerHTML = '';
        if (searches.length === 0) {
            container.innerHTML = '<p class="text-text-secondary text-sm">No saved searches yet.</p>';
            return;
        }

        searches.forEach(search => {
            const item = document.createElement('div');
            item.className = 'flex items-center justify-between bg-[#3C3F58] rounded px-3 py-2';
            item.innerHTML = `
                <div>
                    <div class="font-medium">${escapeHtml(search.name)}</div>
                    <div class="text-xs text-text-secondary">${escapeHtml(search.query)}</div>
                </div>
                <div class="flex items-center gap-2">
                    <button class="px-2 py-1 text-xs bg-blue-500 rounded use-search-btn" data-search='${escapeHtml(JSON.stringify(search))}'>Use</button>
                    <button class="px-2 py-1 text-xs bg-red-500 rounded delete-search-btn" data-search-id="${search.id}">Delete</button>
                </div>`;
            container.appendChild(item);
        });

        container.querySelectorAll('.use-search-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const searchData = JSON.parse(e.target.dataset.search);
                applySavedSearch(searchData);
            });
        });

        container.querySelectorAll('.delete-search-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const searchId = e.target.dataset.searchId;
                if (window.confirm('Delete this saved search?')) {
                    deleteSavedSearch(searchId);
                }
            });
        });
    } catch (error) {
        console.error('Error loading saved searches:', error);
        container.innerHTML = '<p class="text-red-400 text-sm">Error loading saved searches.</p>';
    }
}

function applySavedSearch(searchData) {
    const queryInput = document.getElementById('advancedSearchQuery');
    if (queryInput) queryInput.value = searchData.query || '';

    const filters = searchData.filters ? (typeof searchData.filters === 'string' ? JSON.parse(searchData.filters) : searchData.filters) : {};
    
    Object.keys(filters).forEach(key => {
        // Construct ID similar to how it was done in the original file
        const elementId = 'search' + key.charAt(0).toUpperCase() + key.slice(1).replace(/_([a-z])/g, g => g[1].toUpperCase());
        const element = document.getElementById(elementId);
        if (element) {
            element.value = filters[key];
        }
    });

    closeAdvancedSearch();
    performAdvancedSearch(searchData.query, filters);
}

async function deleteSavedSearch(searchId) {
    try {
        const response = await fetch(`/search/saved/${searchId}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        });

        if (response.ok) {
            showNotification('Search deleted successfully', 'success');
            loadSavedSearches();
        } else {
            throw new Error('Failed to delete search');
        }
    } catch (error) {
        console.error('Error deleting search:', error);
        showNotification('Failed to delete search', 'error');
    }
}

function escapeHtml(str) {
    return str
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
