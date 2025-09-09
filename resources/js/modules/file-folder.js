// Contains core logic for file and folder management.

/**
 * Functions to move here from dashboard.js:
 * - handleCreateFolder()
 * - navigateToFolder()
 * - renderFiles()
 * - renderPagination()
 * - deleteItem()
 * - restoreItem
 * - forceDeleteItem
 * - loadUserFiles
 */


// Import UI functions
import { showNotification, escapeHtml, initializeTooltips, formatFileSize, updateBreadcrumbsDisplay } from './ui.js';

// Module-level state
let state = {
    currentPage: 1,
    lastMainSearch: '',
    currentParentId: null,
    breadcrumbs: [],
    layout: localStorage.getItem('filesLayout') || 'grid',
    lastItems: [],
    delegatedListenersBound: false,
    containerRef: null,
    processingStatusCache: {},
};

// CSRF helper: safely read token from meta or cookie
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta && meta.content) return meta.content;
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

// Processing status helper with simple in-memory cache
async function getProcessingStatus(fileId) {
    try {
        if (state.processingStatusCache && state.processingStatusCache[fileId]) {
            return state.processingStatusCache[fileId];
        }
        const response = await fetch(`/files/${fileId}/processing-status`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-XSRF-TOKEN': getCsrfToken(),
            },
            credentials: 'same-origin'
        });
        if (!response.ok) {
            // Best-effort; don't block menu rendering
            return null;
        }
        const data = await response.json().catch(() => null);
        if (!data) return null;
        state.processingStatusCache[fileId] = data;
        return data;
    } catch (_) {
        return null;
    }
}

// --- Local UI helpers for this module ---
function showCreateFolderModal() {
    const modal = document.getElementById('createFolderModal');
    modal?.classList.remove('hidden');
}

function hideCreateFolderModal() {
    const modal = document.getElementById('createFolderModal');
    modal?.classList.add('hidden');
}

function hideNewDropdown() {
    const dd = document.getElementById('newDropdown');
    if (!dd) return;
    dd.classList.add('hidden', 'opacity-0', 'invisible', 'translate-y-[-10px]');
}

// Main initializer for this module
export function initializeFileFolderManagement(initialState) {
    // Set initial state
    state.currentParentId = initialState.currentParentId;
    // Normalize parent ID: convert 'null' or empty to null, else to number
    if (state.currentParentId === 'null' || state.currentParentId === '' || typeof state.currentParentId === 'undefined') {
        state.currentParentId = null;
    } else if (state.currentParentId !== null) {
        const parsed = parseInt(state.currentParentId, 10);
        if (!Number.isNaN(parsed)) state.currentParentId = parsed;
    }
    state.breadcrumbs = initialState.breadcrumbs;

    // Attach event listeners that are managed by this module
    const createFolderBtn = document.getElementById('create-folder-btn');
    createFolderBtn?.addEventListener('click', () => {
        showCreateFolderModal();
    });

    // Bind the "New -> New Folder" dropdown option
    const createFolderOption = document.getElementById('createFolderOption');
    createFolderOption?.addEventListener('click', (e) => {
        e.preventDefault();
        hideNewDropdown();
        showCreateFolderModal();
    });

    // Bind the "New -> Upload File" dropdown option
    const uploadFileOption = document.getElementById('uploadFileOption');
    uploadFileOption?.addEventListener('click', (e) => {
        e.preventDefault();
        hideNewDropdown();
        if (typeof window.showUploadModal === 'function') {
            window.showUploadModal();
        } else {
            showNotification('Upload modal not initialized', 'error');
        }
    });

    // Create Folder Modal handlers
    const createFolderModal = document.getElementById('createFolderModal');
    const createFolderForm = document.getElementById('createFolderForm');
    const newFolderNameInput = document.getElementById('newFolderNameInput');
    const cancelCreateFolderBtn = document.getElementById('cancelCreateFolderBtn');
    const closeCreateFolderModalBtn = document.getElementById('closeCreateFolderModalBtn');

    createFolderForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const name = (newFolderNameInput?.value || '').trim();
        if (!name) {
            showNotification('Folder name is required', 'error');
            return;
        }
        try {
            await handleCreateFolder(name);
            newFolderNameInput.value = '';
            hideCreateFolderModal();
        } catch (_) { /* notification already shown in handler */ }
    });

    cancelCreateFolderBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        newFolderNameInput && (newFolderNameInput.value = '');
        hideCreateFolderModal();
    });

    closeCreateFolderModalBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        newFolderNameInput && (newFolderNameInput.value = '');
        hideCreateFolderModal();
    });

    const breadcrumbsContainer = document.getElementById('breadcrumbsContainer');
    breadcrumbsContainer?.addEventListener('click', (e) => {
        if (e.target.tagName === 'A' && e.target.dataset.folderId) {
            e.preventDefault();
            const folderId = e.target.dataset.folderId === 'null' ? null : e.target.dataset.folderId;
            const folderName = e.target.textContent;
            navigateToFolder(folderId, folderName);
        }
    });

    // Initial breadcrumbs render
    updateBreadcrumbsDisplay(state.breadcrumbs);

    // --- Layout toggle (Grid/List) ---
    const btnGrid = document.getElementById('btnGridLayout');
    const btnList = document.getElementById('btnListLayout');

    function updateLayoutToggleUI() {
        if (!btnGrid || !btnList) return;
        const gridActive = state.layout === 'grid';
        // Grid button
        btnGrid.classList.toggle('text-primary', gridActive);
        btnGrid.classList.toggle('text-text-secondary', !gridActive);
        btnGrid.setAttribute('aria-pressed', gridActive ? 'true' : 'false');
        // List button
        btnList.classList.toggle('text-primary', !gridActive);
        btnList.classList.toggle('text-text-secondary', gridActive);
        btnList.setAttribute('aria-pressed', !gridActive ? 'true' : 'false');
    }

    function setLayout(layout) {
        state.layout = layout;
        localStorage.setItem('filesLayout', layout);
        applyLayoutClasses(layout);
        updateLayoutToggleUI();
        // Re-render current list without refetching
        if (Array.isArray(state.lastItems)) {
            renderFiles(state.lastItems);
        }
    }

    btnGrid?.addEventListener('click', (e) => { e.preventDefault(); setLayout('grid'); });
    btnList?.addEventListener('click', (e) => { e.preventDefault(); setLayout('list'); });

    // Apply initial layout
    applyLayoutClasses(state.layout);
    updateLayoutToggleUI();
    // Bind delegated listeners once to ensure clicks are captured after re-renders
    bindDelegatedListeners();
    // Expose debug helpers for manual testing in console
    if (typeof window !== 'undefined') {
        window.__files = {
            deleteItem,
            restoreItem,
            forceDeleteItem,
            loadUserFiles,
            navigateToFolder,
            state
        }
    }
}

async function handleCreateFolder(folderName) {
    // Coerce parentId to null or number for backend validation
    let parentId = state.currentParentId;
    if (parentId === 'null' || parentId === '' || typeof parentId === 'undefined') parentId = null;
    if (parentId !== null) {
        const parsed = parseInt(parentId, 10);
        if (!Number.isNaN(parsed)) parentId = parsed; else parentId = null;
    }
    if (!folderName) {
        showNotification('Folder name is required', 'error');
        return;
    }

    try {
        console.debug('Creating folder', { parentId, stateCurrent: state.currentParentId });
        const response = await fetch('/folders', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ file_name: folderName, parent_id: parentId })
        });

        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'Failed to create folder');

        showNotification('Folder created successfully', 'success');
        loadUserFiles(state.lastMainSearch, state.currentPage, state.currentParentId);
    } catch (error) {
        console.error('Create folder error:', error);
        showNotification(error.message, 'error');
    }
}


export async function loadTrashItems() {
    const itemsContainer = document.getElementById('filesContainer');
    if (!itemsContainer) {
        console.error('Items container not found');
        return;
    }

    try {
        // Mark container as trash view for context-sensitive actions
        itemsContainer.dataset.view = 'trash';
        itemsContainer.innerHTML = '<div class="flex justify-center items-center py-8"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div></div>';

        const response = await fetch('/files/trash');
        if (!response.ok) throw new Error('Failed to fetch trash items');
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to load trash items');
        }

        const items = data.data || [];
        displayItems(items, 'trash');
    } catch (error) {
        console.error('Error loading trash items:', error);
        itemsContainer.innerHTML = `
            <div class="p-4 text-center text-text-secondary col-span-full">
                <p class="mb-2">Error loading trash. Please try again.</p>
                <p class="text-xs text-red-500">${escapeHtml(error.message || '')}</p>
            </div>
        `;
    }
}


function navigateToFolder(folderId, folderName) {
    const existingIndex = state.breadcrumbs.findIndex(crumb => crumb.id == folderId);

    if (existingIndex !== -1) {
        state.breadcrumbs = state.breadcrumbs.slice(0, existingIndex + 1);
    } else {
        state.breadcrumbs.push({ id: folderId, name: folderName });
    }

    // Normalize folderId to number or null
    if (folderId === null || folderId === 'null') {
        state.currentParentId = null;
    } else {
        const parsedId = parseInt(folderId, 10);
        state.currentParentId = Number.isNaN(parsedId) ? folderId : parsedId;
    }
    localStorage.setItem('currentParentId', state.currentParentId);
    document.getElementById('currentFolderId').value = state.currentParentId;
    localStorage.setItem('breadcrumbs', JSON.stringify(state.breadcrumbs));
    state.currentPage = 1;
    state.lastMainSearch = '';
    document.getElementById('mainSearchInput').value = '';

    loadUserFiles(state.lastMainSearch, state.currentPage, state.currentParentId);
    updateBreadcrumbsDisplay(state.breadcrumbs);
}

function getFileIcon(fileName) {
    if (!fileName) return '📄';
    const ext = fileName.split('.').pop()?.toLowerCase();
    const iconMap = {
        'pdf': '📄',
        'doc': '📝', 'docx': '📝',
        'xls': '📊', 'xlsx': '📊',
        'ppt': '📺', 'pptx': '📺',
        'jpg': '🖼️', 'jpeg': '🖼️', 'png': '🖼️', 'gif': '🖼️',
        'mp4': '🎥', 'avi': '🎥', 'mov': '🎥',
        'mp3': '🎵', 'wav': '🎵',
        'zip': '📦', 'rar': '📦',
        'txt': '📄'
    };
    return iconMap[ext] || '📄';
}

function applyLayoutClasses(layout) {
    const container = document.getElementById('filesContainer');
    if (!container) return;
    if (layout === 'list') {
        container.className = 'grid grid-cols-1 gap-2';
    } else {
        container.className = 'grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4';
    }
}

// Bind delegated event listeners once to survive re-renders
function bindDelegatedListeners() {
    const container = document.getElementById('filesContainer');
    if (!container) return;
    // If Livewire or another render replaced the container element, rebind
    if (state.containerRef !== container) {
        state.containerRef = container;
        state.delegatedListenersBound = false;
    }
    if (state.delegatedListenersBound) return;
    state.delegatedListenersBound = true;

    // Open actions menu (click)
    container.addEventListener('click', (e) => {
        const btn = e.target.closest('.actions-menu-btn');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            const itemId = btn.dataset.itemId;
            console.debug('[actions-menu-btn] click', { itemId });
            showActionsMenu(btn, itemId);
        }
    });

    // Open actions menu (keyboard)
    container.addEventListener('keydown', (e) => {
        const btn = e.target.closest('.actions-menu-btn');
        if (btn && (e.key === 'Enter' || e.key === ' ')) {
            e.preventDefault();
            const itemId = btn.dataset.itemId;
            console.debug('[actions-menu-btn] keydown', { itemId, key: e.key });
            showActionsMenu(btn, itemId);
        }
    });

    // Folder navigation (skip in Trash view)
    container.addEventListener('click', (e) => {
        const inTrashView = (container?.dataset.view === 'trash');
        
        // Check if clicking actions menu button - ignore
        if (e.target.closest('.actions-menu-btn')) return;
        
        // Check for folder navigation
        const folder = e.target.closest('[data-folder-nav-id]');
        if (folder && !inTrashView) {
            const folderId = folder.dataset.folderNavId;
            const folderName = folder.dataset.folderNavName;
            console.debug('[folder] navigate click', { folderId, folderName });
            navigateToFolder(folderId, folderName);
            return;
        }

        // Check for file preview (files only, not folders)
        const fileCard = e.target.closest('[data-file-id]');
        if (fileCard && !inTrashView) {
            const fileId = fileCard.dataset.fileId;
            const isFolder = fileCard.dataset.isFolder === 'true';
            if (!isFolder) {
                console.debug('[file] preview click', { fileId });
                window.location.href = `/files/${fileId}/preview`;
                return;
            }
        }
    });
}

function createGoogleDriveCard(item) {
    const isFolder = !!item.is_folder;
    const name = item.file_name || item.name || 'Untitled';
    const itemIcon = isFolder ? '📁' : getFileIcon(name);
    const dateRaw = item.updated_at || item.created_at;
    let itemDate = '—';
    if (dateRaw) {
        const d = new Date(dateRaw);
        if (!isNaN(d.getTime())) {
            itemDate = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }
    }

    const cardElement = document.createElement('div');
    cardElement.className = 'group relative bg-[#1F2235] rounded-lg border border-[#4A4D6A] hover:border-[#7C7F96] hover:shadow-lg transition-all duration-200 cursor-pointer';
    
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

    // Accessibility attributes for keyboard navigation
    cardElement.setAttribute('tabindex', '0');
    cardElement.setAttribute('role', 'button');
    cardElement.setAttribute('aria-label', `Open ${isFolder ? 'folder' : 'file'} ${name}`);

    cardElement.innerHTML = `
        <!-- Header with three-dot menu -->
        <div class="absolute top-2 right-2 z-10">
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
            <!-- Icon area -->
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
            </div>
        </div>

        <!-- Hover overlay for selection -->
        <div class="absolute inset-0 bg-blue-500 bg-opacity-0 group-hover:bg-opacity-5 transition-all duration-200 pointer-events-none"></div>
    `;

    return cardElement;
}

function createListRow(item) {
    const isFolder = !!item.is_folder;
    const name = item.file_name || item.name || 'Untitled';
    const icon = isFolder ? '📁' : getFileIcon(name);
    const size = isFolder ? '' : (typeof item.file_size !== 'undefined' ? formatFileSize(parseInt(item.file_size || 0, 10)) : '');
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
    row.dataset.fileId = item.id; // Add this for click handler compatibility
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

function findItemById(itemId) {
    return state.lastItems?.find(item => item.id == itemId) || null;
}

export function renderFiles(items) {
    const container = document.getElementById('filesContainer');
    if (!container) return;

    // Clear container
    container.innerHTML = '';

    if (items.length === 0) {
        container.innerHTML = `<p class="text-gray-400 text-center col-span-full py-10">No files or folders found.</p>`;
        return;
    }

    // Persist items to allow re-rendering on layout toggle
    state.lastItems = items;

    // Ensure container classes reflect current layout
    applyLayoutClasses(state.layout);

    // Create and append elements based on current layout
    const useGrid = state.layout !== 'list';
    items.forEach(item => {
        const element = useGrid ? createGoogleDriveCard(item) : createListRow(item);
        container.appendChild(element);
    });

    // Attach event listeners for actions menu
    container.querySelectorAll('.actions-menu-btn').forEach(btn => {
        btn.addEventListener('click', e => {
            e.stopPropagation();
            const itemId = e.currentTarget.dataset.itemId;
            showActionsMenu(e.currentTarget, itemId);
        });
        // Open menu via keyboard
        btn.addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const itemId = e.currentTarget.dataset.itemId;
                showActionsMenu(e.currentTarget, itemId);
            }
        });
    });

    // Attach event listeners for folder navigation (skip in Trash view)
    const inTrashView = (container?.dataset.view === 'trash');
    if (!inTrashView) {
        container.querySelectorAll('[data-folder-nav-id]').forEach(folder => {
            folder.addEventListener('click', e => {
                // Don't navigate if clicking on actions menu
                if (e.target.closest('.actions-menu-btn')) return;
                
                const folderId = e.currentTarget.dataset.folderNavId;
                const folderName = e.currentTarget.dataset.folderNavName;
                navigateToFolder(folderId, folderName);
            });
            // Keyboard navigate into folder
            folder.addEventListener('keydown', e => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    const folderId = e.currentTarget.dataset.folderNavId;
                    const folderName = e.currentTarget.dataset.folderNavName;
                    navigateToFolder(folderId, folderName);
                }
            });
        });
    }
}

function showActionsMenu(button, itemId) {
    // Close any existing menus first
    document.querySelectorAll('.actions-menu').forEach(m => m.remove());
    document.querySelectorAll('.actions-menu-btn[aria-expanded="true"]').forEach(b => b.setAttribute('aria-expanded', 'false'));

    // Get the container to check current view
    const container = document.getElementById('filesContainer');

    // Create a dark-themed context menu
    const menu = document.createElement('div');
    menu.className = 'actions-menu absolute bg-[#1F2235] text-gray-200 rounded-lg shadow-lg border border-[#4A4D6A] py-2 z-50 min-w-[160px] max-h-64 overflow-auto';
    menu.setAttribute('role', 'menu');
    // Ensure it stays on top even over modals/popovers
    menu.style.zIndex = '9999';
    menu.style.top = '100%';
    menu.style.bottom = 'auto';
    menu.style.right = '0';
    menu.style.left = 'auto';
    menu.style.pointerEvents = 'auto';
    
    const inTrashView = (document.getElementById('filesContainer')?.dataset.view === 'trash');
    
    // Find the item data to check vectorization and blockchain status
    const itemData = findItemById(itemId);
    // Robust boolean coercion: handle true/false, 1/0, 'true'/'false', '1'/'0'
    const asBool = (v) => (v === true || v === 1 || v === '1' || v === 'true');
    const isFolder = asBool(itemData?.is_folder);
    const isBlockchainStored = asBool(itemData?.is_blockchain_stored);
    // Align with backend File::isVectorized(): flag true AND vectorized_at not null
    const isVectorized = asBool(itemData?.is_vectorized) && itemData?.vectorized_at != null;
    const isDeleted = !!itemData?.deleted_at;
    
    console.debug('[actions-menu] open', { itemId, inTrashView, isVectorized, isBlockchainStored, isFolder, isDeleted });
    
    if (inTrashView) {
        menu.innerHTML = `
            <button type="button" class="actions-menu-item w-full text-left px-4 py-2 text-sm text-gray-200 hover:bg-[#2A2D47] hover:text-white flex items-center" data-action="restore" data-item-id="${itemId}" role="menuitem" tabindex="-1" title="Restore" data-tooltip="Restore">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h4l3-3m0 0l3 3m-3-3v12" />
                </svg>
                Restore
            </button>
            <button type="button" class="actions-menu-item w-full text-left px-4 py-2 text-sm text-red-400 hover:bg-[#2A2D47] hover:text-red-300 flex items-center" data-action="force-delete" data-item-id="${itemId}" role="menuitem" tabindex="-1" title="Delete permanently" data-tooltip="Delete permanently">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                Delete permanently
            </button>
        `;
    } else {
        let menuItems = `
            <button class="actions-menu-item w-full text-left px-4 py-2 text-sm text-gray-200 hover:bg-[#2A2D47] hover:text-white flex items-center" data-action="delete" data-item-id="${itemId}" role="menuitem" tabindex="-1" title="Delete" data-tooltip="Delete">
                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                Delete
            </button>
            <button class="actions-menu-item w-full text-left px-4 py-2 text-sm text-gray-200 hover:bg-[#2A2D47] hover:text-white flex items-center" data-action="rename" data-item-id="${itemId}" role="menuitem" tabindex="-1" title="Rename" data-tooltip="Rename">
                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Rename
            </button>
        `;

        // Add blockchain transfer options based on current view and storage location
        if (!isFolder) {
            const inBlockchainView = (container?.dataset.view === 'blockchain');
            
            if (inBlockchainView && isBlockchainStored) {
                // In blockchain view - add comprehensive blockchain actions
                menuItems += `
                    <div class="border-t border-[#4A4D6A] my-1"></div>
                    <button class="actions-menu-item w-full text-left px-4 py-2 text-sm text-purple-400 hover:bg-[#2A2D47] hover:text-purple-300 flex items-center" data-action="download-from-blockchain" data-item-id="${itemId}" role="menuitem" tabindex="-1" title="Download to Supabase storage" data-tooltip="Download to Supabase storage">
                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                        </svg>
                        Download to Supabase
                    </button>
                    <button class="actions-menu-item w-full text-left px-4 py-2 text-sm text-blue-400 hover:bg-[#2A2D47] hover:text-blue-300 flex items-center" data-action="view-on-ipfs" data-item-id="${itemId}" role="menuitem" tabindex="-1" title="View on IPFS Gateway" data-tooltip="View on IPFS Gateway">
                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        View on IPFS Gateway
                    </button>
                    <button class="actions-menu-item w-full text-left px-4 py-2 text-sm text-green-400 hover:bg-[#2A2D47] hover:text-green-300 flex items-center" data-action="copy-ipfs-hash" data-item-id="${itemId}" role="menuitem" tabindex="-1" title="Copy IPFS Hash" data-tooltip="Copy IPFS Hash">
                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        Copy IPFS Hash
                    </button>
                    <button class="actions-menu-item w-full text-left px-4 py-2 text-sm text-yellow-400 hover:bg-[#2A2D47] hover:text-yellow-300 flex items-center" data-action="blockchain-info" data-item-id="${itemId}" role="menuitem" tabindex="-1" title="Blockchain Information" data-tooltip="Blockchain Information">
                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Blockchain Info
                    </button>
                    <button class="actions-menu-item w-full text-left px-4 py-2 text-sm text-indigo-400 hover:bg-[#2A2D47] hover:text-indigo-300 flex items-center" data-action="share-ipfs-link" data-item-id="${itemId}" role="menuitem" tabindex="-1" title="Share IPFS Link" data-tooltip="Share IPFS Link">
                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
                        </svg>
                        Share IPFS Link
                    </button>
                    <div class="border-t border-[#4A4D6A] my-1"></div>
                    <button class="actions-menu-item w-full text-left px-4 py-2 text-sm text-red-400 hover:bg-[#2A2D47] hover:text-red-300 flex items-center" data-action="remove-from-blockchain" data-item-id="${itemId}" role="menuitem" tabindex="-1" title="Remove from blockchain" data-tooltip="Remove from blockchain">
                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Remove from Blockchain
                    </button>
                `;
            } else if (!inBlockchainView && !isBlockchainStored) {
                // In main view and file is not on blockchain - add upload to blockchain option
                menuItems += `
                    <div class="border-t border-[#4A4D6A] my-1"></div>
                    <button class="actions-menu-item w-full text-left px-4 py-2 text-sm text-blue-400 hover:bg-[#2A2D47] hover:text-blue-300 flex items-center" data-action="upload-to-blockchain" data-item-id="${itemId}" role="menuitem" tabindex="-1" title="Upload to blockchain storage" data-tooltip="Upload to blockchain storage">
                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        Upload to Blockchain
                    </button>
                `;
            }
        }

        // Add vector management option if not a folder
        if (!isFolder) {
            if (isVectorized) {
                console.debug('[DEBUG] Adding vector removal button for item:', itemId, { isVectorized, isFolder });
                menuItems += `
                    <div class="border-t border-[#4A4D6A] my-1"></div>
                    <button class="actions-menu-item w-full text-left px-4 py-2 text-sm text-orange-400 hover:bg-[#2A2D47] hover:text-orange-300 flex items-center" data-action="remove-from-vector" data-item-id="${itemId}" role="menuitem" tabindex="-1" title="Remove from AI vector database" data-tooltip="Remove from AI vector database">
                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Remove from AI Vector DB
                    </button>
                `;
            } else {
                console.debug('[DEBUG] Adding vector add button for item:', itemId, { isVectorized, isFolder });
                menuItems += `
                    <div class="border-t border-[#4A4D6A] my-1"></div>
                    <button class="actions-menu-item w-full text-left px-4 py-2 text-sm text-green-400 hover:bg-[#2A2D47] hover:text-green-300 flex items-center" data-action="add-to-vector" data-item-id="${itemId}" role="menuitem" tabindex="-1" title="Add to AI vector database" data-tooltip="Add to AI vector database">
                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add to AI Vector DB
                    </button>
                `;
            }
        } else {
            console.debug('[DEBUG] NOT adding vector buttons for folder:', itemId, { isVectorized, isFolder });
        }

        // Add blockchain removal option if file is stored on blockchain, not a folder, AND not in blockchain view
        const inBlockchainView = (container?.dataset.view === 'blockchain');
        if (!isFolder && isBlockchainStored && !inBlockchainView) {
            menuItems += `
                <div class="border-t border-[#4A4D6A] my-1"></div>
                <button class="actions-menu-item w-full text-left px-4 py-2 text-sm text-orange-400 hover:bg-[#2A2D47] hover:text-orange-300 flex items-center" data-action="remove-from-blockchain" data-item-id="${itemId}" role="menuitem" tabindex="-1" title="Remove from blockchain storage" data-tooltip="Remove from blockchain storage">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                    </svg>
                    Remove from Blockchain
                </button>
            `;
        }

        menu.innerHTML = menuItems;
    }
    
    // Async: check processing status to decide whether to show "Restore vectors"
    (async () => {
        try {
            if (!isFolder && isVectorized) {
                const status = await getProcessingStatus(itemId);
                if (status?.vectors_soft_deleted) {
                    const dividerHtml = inTrashView ? '<div class="border-t border-[#4A4D6A] my-1"></div>' : '';
                    const restoreBtn = `
                        ${dividerHtml}
                        <button class="actions-menu-item w-full text-left px-4 py-2 text-sm text-green-400 hover:bg-[#2A2D47] hover:text-green-300 flex items-center" data-action="restore-vectors" data-item-id="${itemId}" role="menuitem" tabindex="-1" title="Restore vectors" data-tooltip="Restore vectors">
                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M20 4l-6 6M4 20l6-6" />
                            </svg>
                            Restore vectors
                        </button>
                    `;
                    menu.insertAdjacentHTML('beforeend', restoreBtn);
                }
            }
        } catch (err) {
            console.warn('[actions-menu] processing-status check failed', err);
        }
    })();

    // Position menu relative to button
    // Append to body to avoid nesting interactive elements (<button> inside <button>)
    // and position it using viewport coordinates.
    try {
        document.body.appendChild(menu);
        const btnRect = button.getBoundingClientRect();
        // Use fixed positioning relative to viewport
        menu.style.position = 'fixed';
        // Reset conflicting sides to avoid stretching
        menu.style.right = 'auto';
        menu.style.bottom = 'auto';
        // Initial placement below and right-aligned to the trigger with slight offsets to avoid overlap with trigger
        const offsetY = 8; // push menu a bit below
        const offsetX = -4; // nudge left a bit
        menu.style.top = `${btnRect.bottom + offsetY}px`;
        menu.style.left = `${btnRect.right + offsetX}px`;

        // Now measure and adjust to keep within viewport and prefer right alignment
        const menuRect = menu.getBoundingClientRect();
        const viewportW = window.innerWidth;
        const viewportH = window.innerHeight;

        // Vertical flip if not enough space below
        const spaceBelow = viewportH - btnRect.bottom;
        const spaceAbove = btnRect.top;
        if (spaceBelow < menuRect.height + 8 && spaceAbove > spaceBelow) {
            menu.style.top = `${Math.max(8, btnRect.top - menuRect.height)}px`;
        }

        // Horizontal positioning: right-align to button by default
        let left = btnRect.right - menuRect.width + offsetX;
        if (left < 8) left = 8;
        if (left + menuRect.width > viewportW - 8) {
            left = Math.max(8, viewportW - menuRect.width - 8);
        }
        menu.style.left = `${left}px`;
    } catch (_) { /* noop */ }

    // Update aria-expanded for the trigger
    button.setAttribute('aria-expanded', 'true');
    // Prevent trigger from intercepting clicks while menu is open
    const prevPointerEvents = button.style.pointerEvents;
    button.style.pointerEvents = 'none';

    // Focus first menu item for keyboard users
    const firstItem = menu.querySelector('.actions-menu-item');
    if (firstItem) {
        firstItem.focus();
    }

    // DIAGNOSTIC: Compare all menu buttons to see what's different about delete
    const allButtons = menu.querySelectorAll('.actions-menu-item');
    console.debug('[DIAGNOSTIC] Menu buttons comparison:');
    allButtons.forEach((btn, i) => {
        const rect = btn.getBoundingClientRect();
        const computedStyle = window.getComputedStyle(btn);
        console.debug(`[DIAGNOSTIC] Button ${i}:`, {
            action: btn.dataset.action,
            itemId: btn.dataset.itemId,
            text: btn.textContent.trim(),
            visible: rect.width > 0 && rect.height > 0,
            position: { x: rect.x, y: rect.y, w: rect.width, h: rect.height },
            pointerEvents: computedStyle.pointerEvents,
            zIndex: computedStyle.zIndex,
            display: computedStyle.display,
            opacity: computedStyle.opacity,
            transform: computedStyle.transform
        });
    });

    // DIAGNOSTIC: Add temporary global click logger
    const globalClickLogger = (e) => {
        const rect = e.target.getBoundingClientRect();
        console.debug('[DIAGNOSTIC] Global click received:', {
            target: e.target.tagName + (e.target.className ? '.' + e.target.className.split(' ').join('.') : ''),
            action: e.target.dataset?.action,
            position: { x: rect.x, y: rect.y, w: rect.width, h: rect.height },
            clickX: e.clientX,
            clickY: e.clientY,
            isInMenu: menu.contains(e.target),
            targetText: e.target.textContent?.trim().substring(0, 20)
        });
    };
    document.addEventListener('click', globalClickLogger, true);
    
    // Clean up global logger after 10 seconds
    setTimeout(() => {
        document.removeEventListener('click', globalClickLogger, true);
        console.debug('[DIAGNOSTIC] Global click logger removed');
    }, 10000);

    // Unified cleanup helper to close menu and unbind listeners (use function declaration for hoisting)
    function cleanup() {
        try { document.removeEventListener('click', onOutsideClick); } catch (_) {}
        try { document.removeEventListener('keydown', onEsc); } catch (_) {}
        try { document.removeEventListener('click', globalClickLogger, true); } catch (_) {}
        try { menu.remove(); } catch (_) {}
        try { button.setAttribute('aria-expanded', 'false'); } catch (_) {}
        try { button.style.pointerEvents = prevPointerEvents || ''; } catch (_) {}
    }

    // Redundant direct listeners on the delete button to bypass delegation issues
    const deleteBtn = menu.querySelector('.actions-menu-item[data-action="delete"]');
    if (deleteBtn) {
        const directHandler = (ev) => {
            console.debug('[actions-menu-item][direct] event', ev.type, { action: 'delete', itemId: deleteBtn.dataset.itemId });
            ev.preventDefault();
            ev.stopPropagation();
            ev.stopImmediatePropagation?.();
            const id = deleteBtn.dataset.itemId;
            console.debug('[diagnostic] invoking deleteItem from direct button handler', { itemId: id });
            deleteItem(id);
            cleanup();
        };
        deleteBtn.addEventListener('click', directHandler);
    }

    // Direct listeners for restore and force-delete in Trash view
    const restoreBtn = menu.querySelector('.actions-menu-item[data-action="restore"]');
    if (restoreBtn) {
        const directRestore = (ev) => {
            console.debug('[actions-menu-item][direct] event', ev.type, { action: 'restore', itemId: restoreBtn.dataset.itemId });
            ev.preventDefault();
            ev.stopPropagation();
            ev.stopImmediatePropagation?.();
            const id = restoreBtn.dataset.itemId;
            if (window.confirm('Restore this item?')) {
                console.debug('[diagnostic] invoking restoreItem from direct button handler', { itemId: id });
                restoreItem(id);
            }
            cleanup();
        };
        restoreBtn.addEventListener('click', directRestore);
    }

    const forceBtn = menu.querySelector('.actions-menu-item[data-action="force-delete"]');
    if (forceBtn) {
        const directForce = (ev) => {
            console.debug('[actions-menu-item][direct] event', ev.type, { action: 'force-delete', itemId: forceBtn.dataset.itemId });
            ev.preventDefault();
            ev.stopPropagation();
            ev.stopImmediatePropagation?.();
            const id = forceBtn.dataset.itemId;
            if (window.confirm('Delete this item permanently?')) {
                console.debug('[diagnostic] invoking forceDeleteItem from direct button handler', { itemId: id });
                forceDeleteItem(id);
            }
            cleanup();
        };
        forceBtn.addEventListener('click', directForce);
    }

    // Direct listeners for blockchain transfer actions
    const downloadFromBlockchainBtn = menu.querySelector('.actions-menu-item[data-action="download-from-blockchain"]');
    if (downloadFromBlockchainBtn) {
        const directDownload = (ev) => {
            console.debug('[actions-menu-item][direct] event', ev.type, { action: 'download-from-blockchain', itemId: downloadFromBlockchainBtn.dataset.itemId });
            ev.preventDefault();
            ev.stopPropagation();
            ev.stopImmediatePropagation?.();
            const id = downloadFromBlockchainBtn.dataset.itemId;
            if (window.confirm('Download this file from blockchain to Supabase storage?')) {
                console.debug('[diagnostic] invoking downloadFromBlockchain from direct button handler', { itemId: id });
                downloadFromBlockchain(id);
            }
            cleanup();
        };
        downloadFromBlockchainBtn.addEventListener('click', directDownload);
    }

    const uploadToBlockchainBtn = menu.querySelector('.actions-menu-item[data-action="upload-to-blockchain"]');
    if (uploadToBlockchainBtn) {
        const directUpload = (ev) => {
            console.debug('[actions-menu-item][direct] event', ev.type, { action: 'upload-to-blockchain', itemId: uploadToBlockchainBtn.dataset.itemId });
            ev.preventDefault();
            ev.stopPropagation();
            ev.stopImmediatePropagation?.();
            const id = uploadToBlockchainBtn.dataset.itemId;
            if (window.confirm('Upload this file to blockchain storage?')) {
                console.debug('[diagnostic] invoking uploadToBlockchain from direct button handler', { itemId: id });
                uploadToBlockchain(id);
            }
            cleanup();
        };
        uploadToBlockchainBtn.addEventListener('click', directUpload);
    }

    // Blockchain-specific action handlers
    const viewOnIpfsBtn = menu.querySelector('.actions-menu-item[data-action="view-on-ipfs"]');
    if (viewOnIpfsBtn) {
        viewOnIpfsBtn.addEventListener('click', (ev) => {
            ev.preventDefault();
            ev.stopPropagation();
            const id = viewOnIpfsBtn.dataset.itemId;
            viewOnIPFS(id);
            cleanup();
        });
    }

    const copyIpfsHashBtn = menu.querySelector('.actions-menu-item[data-action="copy-ipfs-hash"]');
    if (copyIpfsHashBtn) {
        copyIpfsHashBtn.addEventListener('click', (ev) => {
            ev.preventDefault();
            ev.stopPropagation();
            const id = copyIpfsHashBtn.dataset.itemId;
            copyIPFSHash(id);
            cleanup();
        });
    }

    const blockchainInfoBtn = menu.querySelector('.actions-menu-item[data-action="blockchain-info"]');
    if (blockchainInfoBtn) {
        blockchainInfoBtn.addEventListener('click', (ev) => {
            ev.preventDefault();
            ev.stopPropagation();
            const id = blockchainInfoBtn.dataset.itemId;
            showBlockchainInfo(id);
            cleanup();
        });
    }

    const shareIpfsBtn = menu.querySelector('.actions-menu-item[data-action="share-ipfs-link"]');
    if (shareIpfsBtn) {
        shareIpfsBtn.addEventListener('click', (ev) => {
            ev.preventDefault();
            ev.stopPropagation();
            const id = shareIpfsBtn.dataset.itemId;
            shareIPFSLink(id);
            cleanup();
        });
    }

    const removeFromBlockchainBtn = menu.querySelector('.actions-menu-item[data-action="remove-from-blockchain"]');
    if (removeFromBlockchainBtn) {
        removeFromBlockchainBtn.addEventListener('click', (ev) => {
            ev.preventDefault();
            ev.stopPropagation();
            const id = removeFromBlockchainBtn.dataset.itemId;
            if (window.confirm('Remove this file from blockchain storage? This action cannot be undone.')) {
                removeBlockchainItem(id);
            }
            cleanup();
        });
    }

    // Direct listener for rename placeholder
    const renameBtn = menu.querySelector('.actions-menu-item[data-action="rename"]');
    if (renameBtn) {
        const directRename = (ev) => {
            console.debug('[actions-menu-item][direct] event', ev.type, { action: 'rename', itemId: renameBtn.dataset.itemId });
            ev.preventDefault();
            ev.stopPropagation();
            ev.stopImmediatePropagation?.();
            showNotification('Rename functionality coming soon!', 'info');
            cleanup();
        };
        renameBtn.addEventListener('click', directRename);
    }

    // Direct listeners for vector actions to ensure reliability
    const rmVectorBtn = menu.querySelector('.actions-menu-item[data-action="remove-from-vector"]');
    console.debug('[DEBUG] Looking for vector removal button:', !!rmVectorBtn);
    if (rmVectorBtn) {
        console.debug('[DEBUG] Found vector removal button, attaching listener');
        const directRmVector = (ev) => {
            console.debug('[DEBUG] Vector removal button clicked!', ev.type, { action: 'remove-from-vector', itemId: rmVectorBtn.dataset.itemId });
            ev.preventDefault();
            ev.stopPropagation();
            ev.stopImmediatePropagation?.();
            const id = rmVectorBtn.dataset.itemId;
            console.debug('[DEBUG] Directly calling removeFromVectorDatabase for itemId:', id);
            
            try {
                removeFromVectorDatabase(id);
                console.debug('[DEBUG] removeFromVectorDatabase called successfully');
            } catch (error) {
                console.error('[DEBUG] Error calling removeFromVectorDatabase:', error);
            }
            cleanup();
        };
        rmVectorBtn.addEventListener('click', directRmVector);
    }

    const addVectorBtn = menu.querySelector('.actions-menu-item[data-action="add-to-vector"]');
    console.debug('[DEBUG] Looking for vector add button:', !!addVectorBtn);
    if (addVectorBtn) {
        console.debug('[DEBUG] Found vector add button, attaching listener');
        const directAddVector = (ev) => {
            console.debug('[DEBUG] Vector add button clicked!', ev.type, { action: 'add-to-vector', itemId: addVectorBtn.dataset.itemId });
            ev.preventDefault();
            ev.stopPropagation();
            ev.stopImmediatePropagation?.();
            const id = addVectorBtn.dataset.itemId;
            console.debug('[DEBUG] Directly calling addToVectorDatabase for itemId:', id);
            
            try {
                addToVectorDatabase(id);
                console.debug('[DEBUG] addToVectorDatabase called successfully');
            } catch (error) {
                console.error('[DEBUG] Error calling addToVectorDatabase:', error);
            }
            cleanup();
        };
        addVectorBtn.addEventListener('click', directAddVector);
    }

    const restoreVecBtn = menu.querySelector('.actions-menu-item[data-action="restore-vectors"]');
    if (restoreVecBtn) {
        const directRestoreVec = (ev) => {
            console.debug('[actions-menu-item][direct] event', ev.type, { action: 'restore-vectors', itemId: restoreVecBtn.dataset.itemId });
            ev.preventDefault();
            ev.stopPropagation();
            ev.stopImmediatePropagation?.();
            const id = restoreVecBtn.dataset.itemId;
            if (window.confirm('Restore previously soft-deleted vectors for this file?')) {
                console.debug('[diagnostic] invoking restoreVectors from direct button handler', { itemId: id });
                restoreVectors(id);
            }
            cleanup();
        };
        restoreVecBtn.addEventListener('click', directRestoreVec);
    }

    const rmBlockchainBtn = menu.querySelector('.actions-menu-item[data-action="remove-from-blockchain"]');
    if (rmBlockchainBtn) {
        const directRmBlockchain = (ev) => {
            console.debug('[actions-menu-item][direct] event', ev.type, { action: 'remove-from-blockchain', itemId: rmBlockchainBtn.dataset.itemId });
            ev.preventDefault();
            ev.stopPropagation();
            ev.stopImmediatePropagation?.();
            const id = rmBlockchainBtn.dataset.itemId;
            if (window.confirm('Remove this file from blockchain storage? This will unpin it from IPFS but keep the original file.')) {
                console.debug('[diagnostic] invoking removeFromBlockchain from direct button handler', { itemId: id });
                removeFromBlockchain(id);
            }
            cleanup();
        };
        rmBlockchainBtn.addEventListener('click', directRmBlockchain);
    }

    // Bind listeners for outside/escape closing
    function onOutsideClick(e) {
        if (!menu.contains(e.target) && !button.contains(e.target)) {
            cleanup();
        }
    }
    document.addEventListener('click', onOutsideClick);
    function onEsc(e) {
        if (e.key === 'Escape') {
            cleanup();
        }
    }
    document.addEventListener('keydown', onEsc);
}

function renderPagination(meta) {
    const container = document.getElementById('paginationContainer');
    if (!container) return;
    container.innerHTML = meta.links.map(link => `
        <button 
            class="pagination-btn px-4 py-2 mx-1 rounded-lg ${link.active ? 'bg-primary text-white' : 'bg-gray-700'} ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}"
            data-page="${new URLSearchParams(link.url?.split('?')[1]).get('page')}"
            ${!link.url ? 'disabled' : ''}>
            ${link.label.replace(/&laquo;|&raquo;/g, '')}
        </button>
    `).join('');

    container.querySelectorAll('.pagination-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const page = btn.dataset.page;
            if (page) {
                state.currentPage = parseInt(page);
                loadUserFiles(state.lastMainSearch, state.currentPage, state.currentParentId);
            }
        });
    });
}

async function deleteItem(itemId) {
    try {
        console.debug('[deleteItem] Initiating delete', { itemId });
        showNotification('Deleting item...', 'info');
        const response = await fetch(`/files/${itemId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-XSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        });
        console.debug('[deleteItem] Fetch completed', { status: response.status });
        const ct = response.headers.get('Content-Type') || '';
        console.debug('[deleteItem] Response received', { status: response.status, ok: response.ok, contentType: ct });
        // Consider non-JSON responses as an error (likely redirect to login or HTML error page)
        if (!ct.includes('application/json')) {
            const text = await response.text().catch(() => '');
            console.error('[deleteItem] Unexpected non-JSON response body (possible redirect):', text?.slice(0, 200));
            throw new Error('Move to trash failed: unexpected response (are you still logged in?)');
        }
        if (!response.ok) {
            // Try to parse JSON, else fallback to text
            let errorMessage = `Failed to move item to trash (status ${response.status})`;
            try {
                if (ct.includes('application/json')) {
                    const errorData = await response.json();
                    errorMessage = errorData.message || errorMessage;
                } else {
                    const text = await response.text();
                    console.error('[deleteItem] Non-JSON error response:', text);
                }
            } catch (parseErr) {
                console.error('[deleteItem] Error parsing error response:', parseErr);
            }
            throw new Error(errorMessage);
        }

        showNotification('Item moved to trash successfully.', 'success');
        loadUserFiles(state.lastMainSearch, state.currentPage, state.currentParentId);
    } catch (error) {
        console.error('Error moving item to trash:', error);
        showNotification(error.message, 'error');
    }
}

async function restoreItem(itemId) {
    try {
        console.debug('[restoreItem] Initiating restore', { itemId });
        const response = await fetch(`/files/${itemId}/restore`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-XSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        });

        console.debug('[restoreItem] Fetch completed', { status: response.status });
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || 'Failed to restore item');
        }

        showNotification('Item restored successfully.', 'success');
        // Refresh trash view
        if (typeof loadTrashItems === 'function') {
            await loadTrashItems();
        }
    } catch (error) {
        console.error('Error restoring item:', error);
        showNotification(error.message, 'error');
    }
}

// Remove blockchain items helper
async function removeBlockchainItem(itemId) {
    try {
        const response = await fetch(`/files/${itemId}/remove-from-blockchain`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || `Failed to remove from blockchain: ${response.status}`);
        }

        const result = await response.json();
        
        if (result.success) {
            showNotification('File removed from blockchain successfully', 'success');
            // Refresh blockchain items if we're in blockchain view
            const container = document.getElementById('filesContainer');
            if (container?.dataset.view === 'blockchain') {
                await loadBlockchainItems();
            }
        } else {
            throw new Error(result.message || 'Failed to remove from blockchain');
        }
        
    } catch (error) {
        console.error('Error removing blockchain item:', error);
        showNotification(`Failed to remove from blockchain: ${error.message}`, 'error');
    }
}

// Download file from blockchain to Supabase storage
async function downloadFromBlockchain(itemId) {
    try {
        showNotification('Downloading file from blockchain...', 'info');
        
        const response = await fetch(`/files/${itemId}/download-from-blockchain`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || `Failed to download from blockchain: ${response.status}`);
        }

        const result = await response.json();
        
        if (result.success) {
            showNotification('File downloaded to Supabase storage successfully', 'success');
            // Refresh the current view
            const container = document.getElementById('filesContainer');
            if (container?.dataset.view === 'blockchain') {
                await loadBlockchainItems();
            } else {
                await loadUserFiles();
            }
        } else {
            throw new Error(result.message || 'Failed to download from blockchain');
        }
        
    } catch (error) {
        console.error('Error downloading from blockchain:', error);
        showNotification(`Failed to download from blockchain: ${error.message}`, 'error');
    }
}

// Upload file to blockchain storage
async function uploadToBlockchain(itemId) {
    try {
        showNotification('Uploading file to blockchain...', 'info');
        
        const response = await fetch(`/blockchain/upload-existing`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                file_id: itemId,
                provider: 'pinata'
            })
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || `Failed to upload to blockchain: ${response.status}`);
        }

        const result = await response.json();
        
        if (result.success) {
            showNotification('File uploaded to blockchain successfully', 'success');
            // Refresh the current view
            const container = document.getElementById('filesContainer');
            if (container?.dataset.view === 'main') {
                await loadUserFiles();
            }
        } else {
            throw new Error(result.message || 'Failed to upload to blockchain');
        }
        
    } catch (error) {
        console.error('Error uploading to blockchain:', error);
        showNotification(`Failed to upload to blockchain: ${error.message}`, 'error');
    }
}

// View file on IPFS gateway
function viewOnIPFS(itemId) {
    const item = findItemById(itemId);
    if (!item) {
        showNotification('File not found', 'error');
        return;
    }
    
    // Extract IPFS hash from various possible sources
    let ipfsHash = item.ipfs_hash;
    
    // Try to extract from blockchain_url if no direct hash
    if (!ipfsHash && item.blockchain_url) {
        const match = item.blockchain_url.match(/\/ipfs\/([a-zA-Z0-9]+)/);
        if (match) {
            ipfsHash = match[1];
        }
    }
    
    // Try to extract from file_path
    if (!ipfsHash && item.file_path) {
        ipfsHash = item.file_path.replace('ipfs://', '');
    }
    
    if (!ipfsHash || ipfsHash.length < 10) {
        showNotification('IPFS hash not found for this file', 'error');
        return;
    }
    
    // Always construct the proper Pinata gateway URL format
    const gatewayUrl = `https://gateway.pinata.cloud/ipfs/${ipfsHash}`;
    console.log('Opening IPFS gateway URL:', gatewayUrl);
    
    window.open(gatewayUrl, '_blank');
    showNotification('Opened file on IPFS gateway', 'success');
}

// Copy IPFS hash to clipboard
async function copyIPFSHash(itemId) {
    const item = findItemById(itemId);
    if (!item) {
        showNotification('File not found', 'error');
        return;
    }
    
    const ipfsHash = item.ipfs_hash || (item.file_path ? item.file_path.replace('ipfs://', '') : null);
    if (!ipfsHash) {
        showNotification('IPFS hash not found for this file', 'error');
        return;
    }
    
    try {
        await navigator.clipboard.writeText(ipfsHash);
        showNotification('IPFS hash copied to clipboard', 'success');
    } catch (error) {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = ipfsHash;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showNotification('IPFS hash copied to clipboard', 'success');
    }
}

// Show blockchain information modal
function showBlockchainInfo(itemId) {
    const item = findItemById(itemId);
    if (!item) {
        showNotification('File not found', 'error');
        return;
    }
    
    const metadata = item.blockchain_metadata || {};
    const ipfsHash = item.ipfs_hash || (item.file_path ? item.file_path.replace('ipfs://', '') : 'N/A');
    
    const infoHtml = `
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" id="blockchainInfoModal">
            <div class="bg-[#0D0E2F] p-6 rounded-lg max-w-md w-full mx-4 border border-[#4A4D6A]">
                <h3 class="text-lg font-semibold text-white mb-4">Blockchain Information</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-400">File Name:</span>
                        <span class="text-white">${escapeHtml(item.file_name)}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Provider:</span>
                        <span class="text-white capitalize">${metadata.provider || 'Pinata'}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Status:</span>
                        <span class="text-green-400">${metadata.pin_status || 'Pinned'}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Encrypted:</span>
                        <span class="text-white">${metadata.encrypted ? 'Yes' : 'No'}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Redundancy:</span>
                        <span class="text-white">${metadata.redundancy_level || 3}x</span>
                    </div>
                    <div class="mt-4">
                        <span class="text-gray-400">IPFS Hash:</span>
                        <div class="mt-1 p-2 bg-[#1A1D3A] rounded border font-mono text-xs text-gray-300 break-all">
                            ${ipfsHash}
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button onclick="document.getElementById('blockchainInfoModal').remove()" 
                        class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                        Close
                    </button>
                    <button onclick="copyIPFSHash(${itemId}); document.getElementById('blockchainInfoModal').remove()" 
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Copy Hash
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', infoHtml);
}

// Share IPFS link
async function shareIPFSLink(itemId) {
    const item = findItemById(itemId);
    if (!item) {
        showNotification('File not found', 'error');
        return;
    }
    
    const ipfsHash = item.ipfs_hash || (item.file_path ? item.file_path.replace('ipfs://', '') : null);
    if (!ipfsHash) {
        showNotification('IPFS hash not found for this file', 'error');
        return;
    }
    
    const gatewayUrl = item.blockchain_url || `https://gateway.pinata.cloud/ipfs/${ipfsHash}`;
    const shareData = {
        title: `SecureDocs: ${item.file_name}`,
        text: `Check out this file on IPFS: ${item.file_name}`,
        url: gatewayUrl
    };
    
    try {
        if (navigator.share) {
            await navigator.share(shareData);
            showNotification('IPFS link shared successfully', 'success');
        } else {
            // Fallback - copy to clipboard
            await navigator.clipboard.writeText(gatewayUrl);
            showNotification('IPFS gateway link copied to clipboard', 'success');
        }
    } catch (error) {
        console.error('Error sharing:', error);
        showNotification('Failed to share link', 'error');
    }
}

async function forceDeleteItem(itemId) {
    try {
        console.debug('Force deleting item:', itemId);
        const response = await fetch(`/files/${itemId}/force-delete`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            credentials: 'same-origin'
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Failed to permanently delete item');
        }

        showNotification('Item permanently deleted.', 'success');
        loadTrashItems();
    } catch (error) {
        console.error('Error permanently deleting item:', error);
        showNotification(error.message, 'error');
    }
}

async function removeFromVectorDatabase(itemId) {
    try {
        console.log('[VECTOR REMOVAL] Starting removal for itemId:', itemId);
        
        const csrfToken = getCsrfToken();
        const url = `/files/${itemId}/remove-from-vector`;
        console.log('[VECTOR REMOVAL] Making request to:', url);
        
        const response = await fetch(url, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin'
        });

        console.log('[VECTOR REMOVAL] Response status:', response.status, response.statusText);
        
        let data;
        try {
            data = await response.json();
            console.log('[VECTOR REMOVAL] Response data:', data);
        } catch (e) {
            console.error('[VECTOR REMOVAL] Failed to parse response JSON:', e);
            throw new Error('Invalid response from server');
        }

        if (!response.ok) {
            console.error('[VECTOR REMOVAL] Request failed with status:', response.status, data);
            throw new Error(data.message || `HTTP ${response.status}: Failed to remove file from vector database`);
        }

        console.log('[VECTOR REMOVAL] Success! Showing notification...');
        
        // Show success notification
        if (window.notificationManager) {
            window.notificationManager.showSuccess(
                'Vector Removed Successfully',
                'File has been removed from AI vector database'
            );
        } else {
            showNotification('File removed from AI vector database successfully!', 'success');
        }
        
        // Update local state
        try {
            const item = state.lastItems?.find(i => i.id == itemId);
            if (item) {
                console.log('[VECTOR REMOVAL] Updating local state for item:', item.file_name);
                item.is_vectorized = false;
                item.vectorized_at = null;
            }
        } catch (e) {
            console.debug('[VECTOR REMOVAL] Local state update failed (non-fatal):', e);
        }
        
        console.log('[VECTOR REMOVAL] Reloading file list...');
        loadUserFiles(state.lastMainSearch, state.currentPage, state.currentParentId);
        
    } catch (error) {
        console.error('[VECTOR REMOVAL] Error removing file from vector database:', error);
        
        if (window.notificationManager) {
            window.notificationManager.showError(
                'Vector Removal Failed',
                error.message || 'Failed to remove file from vector database'
            );
        } else {
            showNotification(error.message || 'Failed to remove file from vector database', 'error');
        }
    }
}

async function addToVectorDatabase(itemId) {
    try {
        
        const csrfToken = getCsrfToken();
        const url = `/files/${itemId}/add-to-vector`;
        
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin'
        });
        
        let data;
        try {
            data = await response.json();
        } catch (e) {
            console.error('Failed to parse vectorization response JSON:', e);
            throw new Error('Invalid response from server');
        }

        if (!response.ok) {
            console.error('Vectorization request failed with status:', response.status, data);
            throw new Error(data.message || `HTTP ${response.status}: Failed to add file to vector database`);
        }
        
        // Show success notification
        if (window.notificationManager) {
            window.notificationManager.showSuccess(
                'Vector Processing Started',
                data.message || 'File sent for vectorization processing'
            );
        } else {
            showNotification(data.message || 'File sent for vectorization processing', 'success');
        }

        // Optimistically update UI state for the item (if present)
        try {
            const item = state.lastItems?.find(i => i.id == itemId);
            if (item) {
                // Don't mark as fully vectorized yet, as it's processing
            }
        } catch (e) {
            // non-fatal UI state update failure
        }
        
        loadUserFiles(state.lastMainSearch, state.currentPage, state.currentParentId);
        
    } catch (error) {
        console.error('Error adding file to vector database:', error);
        
        if (window.notificationManager) {
            window.notificationManager.showError(
                'Vector Processing Failed',
                error.message || 'Failed to send file for vector processing'
            );
        } else {
            showNotification(error.message || 'Failed to send file for vector processing', 'error');
        }
    }
}

async function restoreVectors(itemId) {
    try {
        console.debug('Restoring vectors for item:', itemId);
        const response = await fetch(`/files/${itemId}/restore-vectors`, {
            method: 'PATCH',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            credentials: 'same-origin'
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(data.message || 'Failed to restore vectors');
        }

        showNotification('Vectors restored successfully.', 'success');
        const itemsContainer = document.getElementById('filesContainer');
        const inTrashView = (itemsContainer?.dataset.view === 'trash');
        if (inTrashView && typeof loadTrashItems === 'function') {
            await loadTrashItems();
        } else {
            loadUserFiles(state.lastMainSearch, state.currentPage, state.currentParentId);
        }
    } catch (error) {
        console.error('Error restoring vectors:', error);
        showNotification(error.message, 'error');
    }
}

async function removeFromBlockchain(itemId) {
    try {
        console.debug('Removing item from blockchain storage:', itemId);
        const response = await fetch(`/files/${itemId}/remove-from-blockchain`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            credentials: 'same-origin'
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Failed to remove file from blockchain storage');
        }

        showNotification('File removed from blockchain storage successfully.', 'success');
        loadUserFiles(state.lastMainSearch, state.currentPage, state.currentParentId);
    } catch (error) {
        console.error('Error removing file from blockchain storage:', error);
        showNotification(error.message, 'error');
    }
}

/**
 * Loads files and folders from the server and renders them.
 * This function is designed to be independent and requires UI rendering functions to be passed as arguments.
 * @param {string} query - The search query.
 * @param {number} page - The page number for pagination.
 * @param {number|null} parentId - The ID of the parent folder.
 * @param {function} createItemElement - Function to create a DOM element for a file/folder.
 * @param {function} addPaginationControls - Function to render pagination UI.
 * @param {function} addItemEventListeners - Function to add event listeners to items.
 */
// Default render helpers used when not provided by caller
function defaultCreateItemElement(item) {
    const isFolder = !!item.is_folder;
    const name = item.file_name || item.name || 'Untitled';
    const size = isFolder ? 0 : parseInt(item.file_size || 0, 10);
    const updatedAt = item.updated_at ? new Date(item.updated_at).toLocaleDateString() : '';

    const wrapper = document.createElement('div');
    wrapper.className = 'file-item bg-gray-800 p-4 rounded-lg flex items-center justify-between cursor-pointer';
    wrapper.setAttribute('data-item-id', item.id);
    wrapper.setAttribute('data-item-name', name);
    wrapper.setAttribute('data-is-folder', isFolder);
    if (isFolder) {
        wrapper.setAttribute('data-folder-nav-id', item.id);
        wrapper.setAttribute('data-folder-nav-name', name);
    }

    const icon = isFolder ? '📁' : '📄';
    wrapper.innerHTML = `
        <div class="flex items-center truncate">
            <span class="text-2xl mr-4">${icon}</span>
            <span class="truncate">${escapeHtml(name)}</span>
        </div>
        <div class="text-sm text-gray-400 flex items-center">
            ${isFolder ? '' : `<span>${formatFileSize(size)}</span><span class="mx-2">|</span>`}
            <span>${escapeHtml(updatedAt)}</span>
            <button class="delete-item-btn ml-4 text-red-500 hover:text-red-400" data-item-id="${item.id}" title="Move to trash" aria-label="Move to trash">🗑️</button>
        </div>
    `;
    return wrapper;
}

function defaultAddItemEventListeners() {
    const container = document.getElementById('filesContainer');
    if (!container) return;

    container.querySelectorAll('.delete-item-btn').forEach(btn => {
        btn.addEventListener('click', e => {
            e.stopPropagation();
            const itemId = e.currentTarget.dataset.itemId;
            if (window.confirm('Move this item to Trash?')) {
                deleteItem(itemId);
            }
        });
    });

    container.querySelectorAll('[data-folder-nav-id]').forEach(folder => {
        folder.addEventListener('click', e => {
            const folderId = e.currentTarget.dataset.folderNavId;
            const folderName = e.currentTarget.dataset.folderNavName;
            navigateToFolder(folderId, folderName);
        });
    });
}

function defaultAddPaginationControls(itemsContainer, meta) {
    // Remove existing pagination block if present
    const existing = document.getElementById('filesPagination');
    existing?.remove();

    const pag = document.createElement('div');
    pag.id = 'filesPagination';
    pag.className = 'col-span-full flex justify-center gap-2 mt-4';

    (meta.links || []).forEach(link => {
        const btn = document.createElement('button');
        btn.className = `px-3 py-1 rounded ${link.active ? 'bg-primary text-white' : 'bg-gray-700 text-gray-200'} ${!link.url ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-600'}`;
        btn.innerText = link.label.replace(/&laquo;|&raquo;/g, '').trim();
        if (!link.url) btn.disabled = true;
        const pageParam = link.url ? new URL(link.url, window.location.origin).searchParams.get('page') : null;
        if (pageParam) {
            btn.addEventListener('click', () => {
                state.currentPage = parseInt(pageParam);
                loadUserFiles(state.lastMainSearch, state.currentPage, state.currentParentId);
            });
        }
        pag.appendChild(btn);
    });

    itemsContainer.parentElement?.appendChild(pag) || itemsContainer.appendChild(pag);
}

export async function loadUserFiles(query = '', page = 1, parentId = null, createItemElement, addPaginationControls, addItemEventListeners) {
    const itemsContainer = document.getElementById('filesContainer');
    if (!itemsContainer) {
        console.debug('Items container not found - skipping file loading');
        return;
    }
    // Mark main view so actions menu renders correct options
    itemsContainer.dataset.view = 'main';

    try {
        itemsContainer.innerHTML = '<div class="p-4 text-center text-text-secondary col-span-full">Loading...</div>';

        let url = `/files?page=${page}`;
        if (query) url += `&q=${encodeURIComponent(query)}`;
        if (parentId !== null && parentId !== "null") url += `&parent_id=${parentId}`;

        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || `HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();
        itemsContainer.innerHTML = '';

        // Resolve helper functions (fallback to defaults if not provided)
        const createEl = typeof createItemElement === 'function' ? createItemElement : defaultCreateItemElement;
        const addPagination = typeof addPaginationControls === 'function' ? addPaginationControls : defaultAddPaginationControls;
        const addListeners = typeof addItemEventListeners === 'function' ? addItemEventListeners : defaultAddItemEventListeners;

        const items = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : []);

        console.debug('Files API result sample:', items.slice(0, 5).map(i => ({ id: i.id, file_name: i.file_name, name: i.name, is_folder: i.is_folder })));

        if (items.length === 0) {
            itemsContainer.innerHTML = `<div class="p-4 text-center text-text-secondary col-span-full">No items found in this folder.</div>`;
            if (data?.last_page > 1) {
                addPagination(itemsContainer, data);
            }
            return;
        }

        // Use our new Google Drive-style renderer instead of the old item-by-item approach
        renderFiles(items);

        if (data?.last_page > 1) {
            addPagination(itemsContainer, data);
        }

    } catch (error) {
        console.error('Error loading items:', error);
        if (itemsContainer) {
            itemsContainer.innerHTML = `
                <div class="p-4 text-center text-text-secondary col-span-full">
                    <p class="mb-2">Error loading items. Please try again.</p>
                    <p class="text-xs text-red-500">${escapeHtml(error.message || '')}</p>
                </div>
            `;
        }
    }
}