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
};

// CSRF helper: safely read token from meta or cookie
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta && meta.content) return meta.content;
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
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
        };
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
    // Mark trash view so actions menu renders Restore/Force Delete
    itemsContainer.dataset.view = 'trash';

    try {
        itemsContainer.innerHTML = '<div class="p-4 text-center text-text-secondary col-span-full">Loading trash...</div>';

        const response = await fetch('/files/trash', {
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
        const items = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : []);

        itemsContainer.innerHTML = '';

        if (items.length === 0) {
            itemsContainer.innerHTML = '<div class="p-4 text-center text-text-secondary col-span-full">Trash is empty.</div>';
            return;
        }

        renderFiles(items);
        // Re-initialize tooltips after rendering new content
        initializeTooltips();
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

function renderFiles(items) {
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
    console.debug('[actions-menu] open', { itemId, inTrashView });
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
        menu.innerHTML = `
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
    }

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
            try { document.removeEventListener('click', onMenuItemClick, true); } catch (_) {}
            try { document.removeEventListener('pointerdown', onMenuItemPointerDown, true); } catch (_) {}
            menu.remove();
            button.setAttribute('aria-expanded', 'false');
            button.style.pointerEvents = prevPointerEvents || '';
        };
        deleteBtn.addEventListener('mousedown', directHandler, true);
        deleteBtn.addEventListener('click', directHandler, true);
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
            console.debug('[diagnostic] invoking restoreItem from direct button handler', { itemId: id });
            restoreItem(id);
            try { document.removeEventListener('click', onMenuItemClick, true); } catch (_) {}
            try { document.removeEventListener('pointerdown', onMenuItemPointerDown, true); } catch (_) {}
            menu.remove();
            button.setAttribute('aria-expanded', 'false');
            button.style.pointerEvents = prevPointerEvents || '';
        };
        restoreBtn.addEventListener('mousedown', directRestore, true);
        restoreBtn.addEventListener('click', directRestore, true);
    }

    const forceBtn = menu.querySelector('.actions-menu-item[data-action="force-delete"]');
    if (forceBtn) {
        const directForce = (ev) => {
            console.debug('[actions-menu-item][direct] event', ev.type, { action: 'force-delete', itemId: forceBtn.dataset.itemId });
            ev.preventDefault();
            ev.stopPropagation();
            ev.stopImmediatePropagation?.();
            const id = forceBtn.dataset.itemId;
            console.debug('[diagnostic] invoking forceDeleteItem from direct button handler', { itemId: id });
            forceDeleteItem(id);
            try { document.removeEventListener('click', onMenuItemClick, true); } catch (_) {}
            try { document.removeEventListener('pointerdown', onMenuItemPointerDown, true); } catch (_) {}
            menu.remove();
            button.setAttribute('aria-expanded', 'false');
            button.style.pointerEvents = prevPointerEvents || '';
        };
        forceBtn.addEventListener('mousedown', directForce, true);
        forceBtn.addEventListener('click', directForce, true);
    }

    // Use a capturing delegated listener to ensure clicks are caught reliably
    const onMenuItemClick = (e) => {
        const target = e.target.closest('.actions-menu-item');
        if (!target) return;
        if (!menu.contains(target)) return; // Only handle clicks for this menu instance
        console.debug('[actions-menu-item] click', { action: target.dataset.action, itemId: target.dataset.itemId });
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation?.();

        const action = target.dataset.action;
        const clickedItemId = target.dataset.itemId;
        console.debug('[actions-menu] action selected', { action, itemId: clickedItemId });

        // Cleanup listeners and close menu helper
        const cleanup = () => {
            try { document.removeEventListener('click', onMenuItemClick, true); } catch (_) {}
            menu.remove();
            button.setAttribute('aria-expanded', 'false');
            // restore trigger pointer events
            button.style.pointerEvents = prevPointerEvents || '';
        };

        switch (action) {
            case 'delete':
                console.debug('[diagnostic] invoking deleteItem from click', { itemId: clickedItemId });
                deleteItem(clickedItemId);
                cleanup();
                return;
            case 'restore':
                if (confirm('Restore this item?')) {
                    restoreItem(clickedItemId);
                }
                cleanup();
                return;
            case 'force-delete':
                if (confirm('Permanently delete this item? This cannot be undone.')) {
                    forceDeleteItem(clickedItemId);
                }
                cleanup();
                return;
            case 'rename':
                showNotification('Rename functionality coming soon!', 'info');
                cleanup();
                return;
            default:
                cleanup();
                return;
        }
    };
    document.addEventListener('click', onMenuItemClick, true);

    // Some environments may intercept click; handle pointerdown early for critical actions
    const onMenuItemPointerDown = (e) => {
        const target = e.target.closest('.actions-menu-item');
        if (!target) return;
        if (!menu.contains(target)) return;
        const act = target.dataset.action;
        if (!['delete','restore','force-delete'].includes(act)) return;
        console.debug('[actions-menu-item] pointerdown', { action: act, itemId: target.dataset.itemId });
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation?.();
        const clickedItemId = target.dataset.itemId;
        if (act === 'delete') {
            console.debug('[diagnostic] invoking deleteItem from pointerdown', { itemId: clickedItemId });
            deleteItem(clickedItemId);
        } else if (act === 'restore') {
            console.debug('[diagnostic] invoking restoreItem from pointerdown', { itemId: clickedItemId });
            restoreItem(clickedItemId);
        } else if (act === 'force-delete') {
            console.debug('[diagnostic] invoking forceDeleteItem from pointerdown', { itemId: clickedItemId });
            forceDeleteItem(clickedItemId);
        }
        try { document.removeEventListener('pointerdown', onMenuItemPointerDown, true); } catch (_) {}
        try { document.removeEventListener('click', onMenuItemClick, true); } catch (_) {}
        menu.remove();
        button.setAttribute('aria-expanded', 'false');
        button.style.pointerEvents = prevPointerEvents || '';
    };
    document.addEventListener('pointerdown', onMenuItemPointerDown, true);

    // Close menu when clicking outside (ignore clicks on the trigger button)
    setTimeout(() => {
        document.addEventListener('click', function closeMenu(e) {
            if (!menu.contains(e.target) && !button.contains(e.target)) {
                try { document.removeEventListener('click', onMenuItemClick, true); } catch (_) {}
                try { document.removeEventListener('pointerdown', onMenuItemPointerDown, true); } catch (_) {}
                try { document.removeEventListener('click', globalClickLogger, true); } catch (_) {}
                menu.remove();
                button.setAttribute('aria-expanded', 'false');
                // restore trigger pointer events
                button.style.pointerEvents = prevPointerEvents || '';
                document.removeEventListener('click', closeMenu);
            }
        });
        // Close on Escape
        document.addEventListener('keydown', function onEsc(e) {
            if (e.key === 'Escape') {
                menu.remove();
                button.setAttribute('aria-expanded', 'false');
                document.removeEventListener('keydown', onEsc);
            }
        });
    }, 0);
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

async function forceDeleteItem(itemId) {
    try {
        console.debug('[forceDeleteItem] Initiating force delete', { itemId });
        const response = await fetch(`/files/${itemId}/force-delete`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-XSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        });

        console.debug('[forceDeleteItem] Fetch completed', { status: response.status });
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || 'Failed to delete item permanently');
        }

        showNotification('Item deleted permanently.', 'success');
        // Refresh trash view
        if (typeof loadTrashItems === 'function') {
            await loadTrashItems();
        }
    } catch (error) {
        console.error('Error permanently deleting item:', error);
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
            if (confirm('Move this item to Trash?')) {
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