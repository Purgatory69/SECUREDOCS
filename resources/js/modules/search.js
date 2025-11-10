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
let loadTrashItemsCallback;
let loadSharedFilesCallback;
let loadBlockchainItemsCallback;

// --- Initialization ---
export function initializeSearch(callbacks) {
    if (typeof callbacks === 'function') {
        // Backward compatibility
        loadUserFilesCallback = callbacks;
    } else {
        loadUserFilesCallback = callbacks.loadUserFiles;
        loadTrashItemsCallback = callbacks.loadTrashItems;
        loadSharedFilesCallback = callbacks.loadSharedFiles;
        loadBlockchainItemsCallback = callbacks.loadBlockchainItems;
    }
    
    initializeAdvancedSearchModal();
    initializeMainSearch();
}

function initializeMainSearch() {
    const mainSearchInput = document.getElementById('mainSearchInput');
    const mainSearchButton = document.getElementById('mainSearchButton');

    const performMainSearch = () => {
        const query = mainSearchInput.value.trim();
        const currentView = getCurrentViewContext();
        
        // Perform search based on current tab
        switch (currentView) {
            case 'trash':
                if (loadTrashItemsCallback) loadTrashItemsCallback(query);
                break;
            case 'shared':
                if (loadSharedFilesCallback) loadSharedFilesCallback(query);
                break;
            case 'blockchain':
                if (loadBlockchainItemsCallback) loadBlockchainItemsCallback(query);
                break;
            case 'main':
            default:
                if (loadUserFilesCallback) loadUserFilesCallback(query, 1, null);
                break;
        }
    };

    mainSearchButton?.addEventListener('click', performMainSearch);
    mainSearchInput?.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            performMainSearch();
        }
    });
}

function getCurrentViewContext() {
    const filesContainer = document.getElementById('filesContainer');
    return filesContainer?.dataset?.view || 'main';
}

// --- Helper Functions (Stubs/Implementations) ---
function closeAdvancedSearch() {
    const modal = document.getElementById('advancedSearchModal');
    if(modal) modal.classList.add('hidden');
}

async function performAdvancedSearch(query, filters) {
    console.log("Performing advanced search with:", { query, filters });
    
    const currentView = getCurrentViewContext();
    filters.view_context = currentView;
    
    try {
        // Use the SearchController API for advanced search
        const params = new URLSearchParams({
            q: query,
            ...filters
        });
        
        const response = await fetch(`/search?${params}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            throw new Error('Search request failed');
        }
        
        const data = await response.json();
        
        // Update the UI with search results
        displaySearchResults(data, currentView);
        
    } catch (error) {
        console.error('Advanced search error:', error);
        showNotification('Search failed. Please try again.', 'error');
    }
}

function displaySearchResults(data, viewContext) {
    const filesContainer = document.getElementById('filesContainer');
    if (!filesContainer) return;
    
    // Set the view context on the container so renderFiles knows which view we're in
    filesContainer.dataset.view = viewContext;
    
    // Add clear search button to the page (not inside filesContainer)
    addClearSearchButton(data.query, data.files.length);
    
    // Import the renderFiles function from file-folder.js module
    import('./file-folder.js').then(module => {
        if (module.renderFiles && typeof module.renderFiles === 'function') {
            // Use the existing renderFiles function to maintain consistent display
            module.renderFiles(data.files);
        } else {
            // Fallback: use the existing file display system directly
            displayFilesWithExistingSystem(data.files, filesContainer);
        }
    }).catch(() => {
        // Fallback if import fails
        displayFilesWithExistingSystem(data.files, filesContainer);
    });
    
    // Add pagination if needed
    if (data.pagination && data.pagination.last_page > 1) {
        addSearchPagination(data.pagination, data.query, data.filters);
    }
}

function displayFilesWithExistingSystem(files, container) {
    // Clear container
    container.innerHTML = '';
    
    if (files.length === 0) {
        container.innerHTML = `
            <div class="text-center py-12">
                <div class="text-gray-400 text-lg mb-2">No files found</div>
                <div class="text-gray-500 text-sm">Try adjusting your search criteria</div>
            </div>
        `;
        return;
    }
    
    // Use the same layout system as the existing file display
    // Check if we're in grid or list mode (default to grid)
    const useGrid = !container.classList.contains('list-layout');
    
    // Create document fragment for better performance
    const fragment = document.createDocumentFragment();
    
    files.forEach(file => {
        const element = useGrid ? createSearchFileCard(file) : createSearchListRow(file);
        fragment.appendChild(element);
    });
    
    container.appendChild(fragment);
}

// Create file card using the same structure as createGoogleDriveCard
function createSearchFileCard(item) {
    const isFolder = !!item.is_folder;
    const name = item.file_name || item.name || 'Untitled';
    const itemIcon = isFolder ? '📁' : getSearchFileIcon(name);
    const dateRaw = item.updated_at || item.created_at;
    let itemDate = '—';
    if (dateRaw) {
        const d = new Date(dateRaw);
        if (!isNaN(d.getTime())) {
            itemDate = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }
    }

    const cardElement = document.createElement('div');
    cardElement.className = 'group relative rounded-lg border border-[#4A4D6A] hover:border-[#7C7F96] hover:shadow-lg transition-all duration-200 cursor-pointer file-card';

    if (isFolder) {
        cardElement.setAttribute('data-folder-nav-id', item.id);
        cardElement.setAttribute('data-folder-nav-name', name);
    } else {
        cardElement.setAttribute('data-file-id', item.id);
        cardElement.setAttribute('data-is-folder', 'false');
    }
    cardElement.dataset.itemId = item.id;
    cardElement.dataset.isFolder = isFolder;
    cardElement.dataset.itemName = name;
    
    if (isFolder) {
        cardElement.dataset.folderNavId = item.id;
        cardElement.dataset.folderNavName = name;
    }

    cardElement.setAttribute('tabindex', '0');
    cardElement.setAttribute('role', 'button');
    cardElement.setAttribute('aria-label', `Open ${isFolder ? 'folder' : 'file'} ${name}`);

    cardElement.innerHTML = `
        <!-- Header with OTP indicator and three-dot menu -->
        <div class="absolute top-2 left-2 right-2 flex justify-between items-center z-9">
            <!-- OTP Security Indicator -->
                ${!isFolder && item.has_otp_protection ? `
                <div class="bg-orange-500 text-white p-1 rounded-full" title="OTP Protected" data-tooltip="OTP Protected">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
            ` : '<div></div>'}
            
            <!-- Actions Menu Button -->
            <button class="actions-menu-btn opacity-0 group-hover:opacity-100 transition-opacity p-1 rounded-full hover:bg-[#4A4D6A]" 
                    data-item-id="${item.id}" 
                    data-tooltip="More actions"
                    title="More actions"
                    aria-label="More actions"
                    aria-haspopup="menu"
                    aria-expanded="false">
                <svg viewBox="0 0 20 20" class="w-5 h-5 text-gray-300 hover:text-white" fill="currentColor">
                    <path d="M10 6c.82 0 1.5-.68 1.5-1.5S10.82 3 10 3s-1.5.67-1.5 1.5S9.18 6 10 6zm0 5.5c.82 0 1.5-.68 1.5-1.5s-.68-1.5-1.5-1.5-1.5.68-1.5 1.5.68 1.5 1.5 1.5zm0 5.5c.82 0 1.5-.67 1.5-1.5 0-.82-.68-1.5-1.5-1.5s-1.5.68-1.5 1.5c0 .83.68 1.5 1.5 1.5z"></path>
                </svg>
            </button>
        </div>

    <!-- Main content area -->
    <div class="p-4">
        <!-- Single icon here -->
        <div class="flex items-center justify-center h-16 mb-3">
            <span class="text-5xl">${itemIcon}</span>
        </div>
        
        <!-- File info -->
        <div class="space-y-1">
            <div class="text-sm font-medium text-white truncate" title="${escapeHtml(name)}">
                ${escapeHtml(name)}
            </div>
            <div class="text-xs text-gray-400">
                ${itemDate}
            </div>
            
            <!-- Arweave Status Badge (for files stored on Arweave) -->
            ${!isFolder && item.is_arweave ? `
                <div class="mt-3 pt-2 border-t border-[#4A4D6A]">
                    <div class="w-full flex items-center justify-center px-3 py-2 text-xs bg-green-600 text-white rounded-md font-medium">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        ⛓️ Stored on Arweave
                    </div>
                </div>
            ` : ''}
        </div>
    </div>

    <!-- Hover overlay for selection -->
    <div class="absolute inset-0 bg-blue-500 bg-opacity-0 group-hover:bg-opacity-5 transition-all duration-200 pointer-events-none"></div>
`;

    return cardElement;
}

// Create list row using the same structure as createListRow
function createSearchListRow(item) {
    const isFolder = !!item.is_folder;
    const name = item.file_name || item.name || 'Untitled';
    const icon = isFolder ? '📁' : getSearchFileIcon(name);
    const size = isFolder ? '' : (typeof item.file_size !== 'undefined' ? formatSearchFileSize(parseInt(item.file_size || 0, 10)) : '');
    const dateRaw = item.updated_at || item.created_at;
    let itemDate = '';
    if (dateRaw) {
        const d = new Date(dateRaw);
        if (!isNaN(d.getTime())) {
            itemDate = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }
    }

    const row = document.createElement('div');
    row.className = 'file-row bg-[#2A2D47] p-3 rounded-lg flex items-center justify-between hover:border-[#6B7280] border border-[#4A4D6A] transition-all cursor-pointer';
    row.dataset.itemId = item.id;
    row.dataset.fileId = item.id;
    row.dataset.isFolder = isFolder;
    row.dataset.itemName = name;
    if (isFolder) {
        row.dataset.folderNavId = item.id;
        row.dataset.folderNavName = name;
    }

    row.setAttribute('tabindex', '0');
    row.setAttribute('role', 'button');
    row.setAttribute('aria-label', `Open ${isFolder ? 'folder' : 'file'} ${name}`);

    row.innerHTML = `
        <div class="flex items-center min-w-0">
            <span class="text-2xl mr-3">${icon}</span>
            <span class="text-sm text-white truncate" title="${escapeHtml(name)}">${escapeHtml(name)}</span>
        </div>
        <div class="flex items-center text-xs text-gray-300 gap-3">
            ${size ? `<span class="hidden sm:inline">${size}</span>` : ''}
            ${itemDate ? `<span class="hidden sm:inline">${itemDate}</span>` : ''}
            <button class="actions-menu-btn p-2 rounded hover:bg-[#4A4D6A]" data-item-id="${item.id}" data-tooltip="More actions" title="More actions" aria-label="More actions">
                <svg viewBox="0 0 20 20" class="w-5 h-5 text-gray-300 hover:text-white" fill="currentColor">
                    <path d="M10 6c.82 0 1.5-.68 1.5-1.5S10.82 3 10 3s-1.5.67-1.5 1.5S9.18 6 10 6zm0 5.5c.82 0 1.5-.68 1.5-1.5s-.68-1.5-1.5-1.5-1.5.68-1.5 1.5.68 1.5 1.5 1.5zm0 5.5c.82 0 1.5-.67 1.5-1.5 0-.82-.68-1.5-1.5-1.5s-1.5.68-1.5 1.5c0 .83.68 1.5 1.5 1.5z"></path>
                </svg>
            </button>
        </div>
    `;

    return row;
}

// Helper functions for search file display
function getSearchFileIcon(fileName) {
    const ext = fileName.split('.').pop()?.toLowerCase() || '';
    const iconMap = {
        // Documents
        'pdf': '📄', 'doc': '📝', 'docx': '📝', 'txt': '📄', 'rtf': '📄',
        // Spreadsheets
        'xls': '📊', 'xlsx': '📊', 'csv': '📊',
        // Presentations
        'ppt': '📈', 'pptx': '📈',
        // Images
        'jpg': '🖼️', 'jpeg': '🖼️', 'png': '🖼️', 'gif': '🖼️', 'bmp': '🖼️', 'svg': '🖼️',
        // Videos
        'mp4': '🎥', 'avi': '🎥', 'mov': '🎥', 'wmv': '🎥', 'flv': '🎥',
        // Audio
        'mp3': '🎵', 'wav': '🎵', 'flac': '🎵', 'aac': '🎵',
        // Archives
        'zip': '📦', 'rar': '📦', '7z': '📦', 'tar': '📦',
        // Code
        'js': '💻', 'html': '💻', 'css': '💻', 'php': '💻', 'py': '💻'
    };
    return iconMap[ext] || '📄';
}

function formatSearchFileSize(bytes) {
    if (!bytes || bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

function addClearSearchButton(query, resultCount) {
    // Remove existing clear search button if it exists
    const existingButton = document.getElementById('clearSearchButton');
    if (existingButton) {
        existingButton.remove();
    }
    
    // Find a good place to add the clear search button (near the search input or breadcrumbs)
    const breadcrumbsContainer = document.getElementById('breadcrumbsContainer');
    const searchContainer = document.querySelector('.search-container') || breadcrumbsContainer;
    
    if (searchContainer) {
        const clearButton = document.createElement('div');
        clearButton.id = 'clearSearchButton';
        clearButton.className = 'mb-4 flex items-center justify-between bg-[#1F2235] rounded-lg p-3';
        clearButton.innerHTML = `
            <div class="text-white">
                <span class="text-sm font-medium">Search Results</span>
                <span class="text-gray-400 text-xs ml-2">${resultCount} files found${query ? ` for "${escapeHtml(query)}"` : ''}</span>
            </div>
            <button onclick="clearSearch()" class="text-blue-400 hover:text-blue-300 text-sm px-3 py-1 rounded hover:bg-blue-400/10 transition-colors">
                Clear Search
            </button>
        `;
        
        // Insert after breadcrumbs or at the beginning of main content
        if (breadcrumbsContainer && breadcrumbsContainer.nextSibling) {
            breadcrumbsContainer.parentNode.insertBefore(clearButton, breadcrumbsContainer.nextSibling);
        } else if (searchContainer.parentNode) {
            searchContainer.parentNode.insertBefore(clearButton, searchContainer.nextSibling);
        }
    }
}

// Removed custom file display functions - using existing system instead

function addSearchPagination(pagination, query, filters) {
    // Implement pagination for search results
    console.log('Add pagination:', pagination);
}

// Global function to clear search
window.clearSearch = function() {
    const mainSearchInput = document.getElementById('mainSearchInput');
    if (mainSearchInput) {
        mainSearchInput.value = '';
    }
    
    // Remove the clear search button
    const clearSearchButton = document.getElementById('clearSearchButton');
    if (clearSearchButton) {
        clearSearchButton.remove();
    }
    
    const currentView = getCurrentViewContext();
    switch (currentView) {
        case 'trash':
            if (loadTrashItemsCallback) loadTrashItemsCallback();
            break;
        case 'shared':
            if (loadSharedFilesCallback) loadSharedFilesCallback();
            break;
        case 'blockchain':
            if (loadBlockchainItemsCallback) loadBlockchainItemsCallback();
            break;
        case 'main':
        default:
            if (loadUserFilesCallback) loadUserFilesCallback();
            break;
    }
};

// --- Advanced Search Modal Logic ---
function initializeAdvancedSearchModal() {
    const modal = document.getElementById('advancedSearchModal');
    const openBtn = document.getElementById('advanced-search-button');
    const form = document.getElementById('advancedSearchForm');
    const closeBtn = document.getElementById('advancedSearchCloseBtn');
    const cancelBtn = document.getElementById('cancelAdvancedSearch');
    const clearFiltersBtn = document.getElementById('clearSearchFilters');
    openBtn?.addEventListener('click', () => {
        const currentView = getCurrentViewContext();
        
        // Update modal title to reflect current tab
        const modalTitle = modal?.querySelector('h3');
        if (modalTitle) {
            const viewNames = {
                'main': 'My Documents',
                'trash': 'Trash',
                'shared': 'Shared with Me',
                'blockchain': 'Blockchain Storage'
            };
            modalTitle.textContent = `Advanced Search - ${viewNames[currentView] || 'My Documents'}`;
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
    
    // Validate and cap file size inputs
    const sizeMinInput = document.getElementById('searchSizeMin');
    const sizeMaxInput = document.getElementById('searchSizeMax');
    
    let sizeMin = sizeMinInput?.value ? parseFloat(sizeMinInput.value) : '';
    let sizeMax = sizeMaxInput?.value ? parseFloat(sizeMaxInput.value) : '';
    
    // Enforce 100MB maximum
    if (sizeMax && sizeMax > 100) {
        sizeMax = 100;
        sizeMaxInput.value = '100';
        showNotification('Maximum file size capped at 100MB', 'info');
    }
    
    // Validate size range
    if (sizeMin && sizeMax && sizeMin > sizeMax) {
        showNotification('Minimum size cannot be greater than maximum size', 'error');
        return;
    }
    
    const filters = {
        // Search options
        match_type: document.getElementById('searchMatchType')?.value || 'contains',
        case_sensitive: document.getElementById('searchCaseSensitive')?.value || 'insensitive',
        whole_word: document.getElementById('searchWholeWord')?.checked || false,
        
        // File filters
        type: document.getElementById('searchFileType')?.value || '',
        date_from: document.getElementById('searchDateFrom')?.value || '',
        date_to: document.getElementById('searchDateTo')?.value || '',
        size_min: sizeMin || '',
        size_max: sizeMax || '',
        
        // Sort options
        sort_by: document.getElementById('searchSortBy')?.value || 'updated_at',
        sort_order: document.getElementById('searchSortOrder')?.value || 'desc'
    };

    // Clean up empty filters
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
