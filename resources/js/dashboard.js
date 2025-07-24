// Initialize Supabase client
let supabase;
if (window.supabase && window.SUPABASE_URL && window.SUPABASE_KEY) {
    supabase = window.supabase.createClient(window.SUPABASE_URL, window.SUPABASE_KEY);
} else {
    console.error('Supabase credentials not found or Supabase client not loaded. Ensure SUPABASE_URL, SUPABASE_KEY are set in your .env and app.blade.php, and the Supabase JS client is loaded.');
}

// Global variables for pagination, search, and current folder
let currentPage = 1;
let lastMainSearch = '';
let currentParentId = null; // null for root directory
let breadcrumbs = [{ id: null, name: 'My Documents' }]; // For navigation

// Check if WebAuthn is available
if (typeof WebAuthn === 'undefined') {
    console.warn('WebAuthn not loaded - some features may be limited');
}

document.addEventListener('DOMContentLoaded', () => {
    initializeApp();
});

function initializeApp() {
    initializeN8nChat();
    initializeUserProfile();
    initializeUploadModal();
    initializeFileManagement();
    initializeFolderManagement(); // New
    initializeSearch();
    loadUserFiles(); // Will be called with currentParentId
    initializeWebAuthnForDashboard();
    updateBreadcrumbsDisplay(); // New
}

function initializeFolderManagement() {
    const createFolderBtn = document.getElementById('createFolderBtn');
    const createFolderModal = document.getElementById('createFolderModal');
    const closeCreateFolderModalBtn = document.getElementById('closeCreateFolderModalBtn');
    const createFolderForm = document.getElementById('createFolderForm');
    const newFolderNameInput = document.getElementById('newFolderNameInput');
    const cancelCreateFolderBtn = document.getElementById('cancelCreateFolderBtn');

    if (createFolderBtn) {
        createFolderBtn.addEventListener('click', () => {
            if (createFolderModal) createFolderModal.classList.remove('hidden');
            if (newFolderNameInput) newFolderNameInput.focus();
        });
    }

    if (closeCreateFolderModalBtn) {
        closeCreateFolderModalBtn.addEventListener('click', () => {
            if (createFolderModal) createFolderModal.classList.add('hidden');
            if (createFolderForm) createFolderForm.reset();
        });
    }

    if (cancelCreateFolderBtn) {
        cancelCreateFolderBtn.addEventListener('click', () => {
            if (createFolderModal) createFolderModal.classList.add('hidden');
            if (createFolderForm) createFolderForm.reset();
        });
    }

    if (createFolderForm) {
        createFolderForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const folderName = newFolderNameInput.value.trim();
            if (folderName) {
                await handleCreateFolder(folderName);
                if (createFolderModal) createFolderModal.classList.add('hidden');
                createFolderForm.reset();
            }
        });
    }

    // Event listener for breadcrumb navigation
    const breadcrumbsContainer = document.getElementById('breadcrumbsContainer');
    if (breadcrumbsContainer) {
        breadcrumbsContainer.addEventListener('click', (e) => {
            if (e.target.tagName === 'A' && e.target.dataset.folderId) {
                e.preventDefault();
                const folderId = e.target.dataset.folderId === 'null' ? null : e.target.dataset.folderId;
                const folderName = e.target.textContent;
                navigateToFolder(folderId, folderName);
            }
        });
    }
}

async function handleCreateFolder(folderName) {
    try {
        const response = await fetch('/folders', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                folder_name: folderName,
                parent_id: currentParentId
            })
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Failed to create folder');
        }

        const newFolder = await response.json();
        // console.log('Folder created:', newFolder);
        await loadUserFiles(lastMainSearch, 1, currentParentId); // Reload current view
    } catch (error) {
        console.error('Create folder error:', error);
        alert(`Error creating folder: ${error.message}`);
    }
}

function updateBreadcrumbsDisplay() {
    const breadcrumbsContainer = document.getElementById('breadcrumbsContainer');
    if (!breadcrumbsContainer) return;

    breadcrumbsContainer.innerHTML = breadcrumbs.map((crumb, index) => {
        if (index === breadcrumbs.length - 1) {
            return `<span class="text-white font-medium">${crumb.name}</span>`;
        }
        return `<a href="#" data-folder-id="${crumb.id}" class="text-primary hover:underline">${crumb.name}</a> <span class="mx-2 text-text-secondary">/</span>`;
    }).join('');

    // Update the main heading as well
    const mainHeading = document.querySelector('main h1.text-2xl');
    if (mainHeading) {
        mainHeading.textContent = breadcrumbs[breadcrumbs.length - 1].name;
    }
}

function navigateToFolder(folderId, folderName) {
    // Find the index of the folder in the current breadcrumbs
    const existingIndex = breadcrumbs.findIndex(crumb => crumb.id === folderId);

    if (existingIndex !== -1) {
        // If folder exists in breadcrumbs, truncate to that level
        breadcrumbs = breadcrumbs.slice(0, existingIndex + 1);
    } else {
        // This case should ideally not happen if navigation is only through displayed folders or breadcrumbs
        // If it's a new folder being entered (not from breadcrumbs click but from item click)
        // This will be handled by the item click handler that calls this.
        // For direct breadcrumb click, we assume it's valid.
    }

    currentParentId = folderId;
    currentPage = 1; // Reset to first page
    lastMainSearch = ''; // Clear search when navigating
    document.querySelector('input[placeholder*="Search"]').value = ''; // Clear search input field

    loadUserFiles(lastMainSearch, currentPage, currentParentId);
    updateBreadcrumbsDisplay();
}


// Add this new function to handle WebAuthn initialization
function initializeWebAuthnForDashboard() {
    // Only initialize if WebAuthn is available and we have the webauthn object
    if (typeof WebAuthn !== 'undefined' && window.webauthn) {
        console.log('WebAuthn is available for dashboard features');
        // Add any WebAuthn-related dashboard functionality here
        // For example, you might want to add event listeners for secure actions
    }
}

// --- N8N Chat Widget Initialization ---
function initializeN8nChat() {
    if (document.getElementById('adminSidebar')) {
        return; // Exit early
    }
    const currentUserEmail = window.userEmail;
    const currentUserId = window.userId;
    const currentUsername = window.username;

    // The backend now determines the correct webhook URL
    const n8nWebhookUrlToUse = window.chatWebhookUrl;

    // Customize initial messages based on premium status
    const initialMessages = window.userIsPremium 
        ? [
            'Hello, valued premium member!',
            'My name is Tubby, your premium assistant. How can I help you today?',
            'You have access to our premium support features.'
          ]
        : [
            'Hello!',
            'My name is Tubby. How can I assist you today?',
            'Upgrade to premium for personalized support and advanced features.'
          ];

    if (window.createChat && n8nWebhookUrlToUse) {
        window.createChat({
            webhookUrl: n8nWebhookUrlToUse, // Use the dynamically determined URL
            webhookConfig: {
                method: 'POST',
                headers: {}
            },
            target: '#n8n-chat-container',
            mode: 'window',
            chatInputKey: 'chatInput',
            chatSessionKey: 'sessionId',
            metadata: {
                ...(window.userIsPremium && { userId: currentUserId }),
                userEmail: currentUserEmail,
                userName: currentUsername,
                isPremium: window.userIsPremium || false
            },
            showWelcomeScreen: false,
            defaultLanguage: 'en',
            initialMessages: initialMessages,
            i18n: {
                en: {
                    title: 'Welcome!',
                    subtitle: "Ask me anything.",
                    getStarted: 'Start Chatting',
                    inputPlaceholder: 'Enter your message here...'
                }
            },
            theme: {
                colors: {
                    primary: '#4285f4'
                }
            }
        });
    }
}

// --- User Profile Dropdown ---
function initializeUserProfile() {
    const userProfileBtn = document.getElementById('userProfileBtn');
    const profileDropdown = document.getElementById('profileDropdown');
    const overlay = document.getElementById('overlay');

    if (!userProfileBtn || !profileDropdown || !overlay) return;

    userProfileBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        toggleProfileDropdown();
    });

    overlay.addEventListener('click', hideProfileDropdown);

    document.addEventListener('click', (event) => {
        const isClickInside = userProfileBtn.contains(event.target) || profileDropdown.contains(event.target);
        if (!isClickInside && !profileDropdown.classList.contains('invisible')) {
            hideProfileDropdown();
        }
    });

    function toggleProfileDropdown() {
        profileDropdown.classList.toggle('opacity-0');
        profileDropdown.classList.toggle('invisible');
        profileDropdown.classList.toggle('translate-y-[-10px]');
        overlay.classList.toggle('hidden');
    }

    function hideProfileDropdown() {
        profileDropdown.classList.add('opacity-0', 'invisible', 'translate-y-[-10px]');
        overlay.classList.add('hidden');
    }
}

// --- Upload Modal ---
function initializeUploadModal() {
    const newBtn = document.getElementById('newBtn');
    const uploadModal = document.getElementById('uploadModal');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const modalBackdrop = document.getElementById('modalBackdrop');
    const cancelUploadBtn = document.getElementById('cancelUploadBtn');
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const fileList = document.getElementById('fileList');
    const uploadBtn = document.getElementById('uploadBtn');
    const uploadProgress = document.getElementById('uploadProgress');
    const progressBar = document.getElementById('progressBar');
    const progressPercentage = document.getElementById('progressPercentage');

    if (!newBtn || !uploadModal) return;

    // Modal show/hide handlers
    newBtn.addEventListener('click', showUploadModal);
    [closeModalBtn, modalBackdrop, cancelUploadBtn].forEach(element => {
        if (element) {
            element.addEventListener('click', hideUploadModal);
        }
    });

    // Drag and drop functionality
    if (dropZone && fileInput) {
        dropZone.addEventListener('click', () => fileInput.click());
        dropZone.addEventListener('dragover', handleDragOver);
        dropZone.addEventListener('dragleave', handleDragLeave);
        dropZone.addEventListener('drop', handleDrop);
        fileInput.addEventListener('change', () => handleFiles(fileInput.files));
    }

    // Upload button handler
    if (uploadBtn) {
        uploadBtn.addEventListener('click', handleUpload);
    }

    function showUploadModal() {
        uploadModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function hideUploadModal() {
        uploadModal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        resetUploadForm();
    }

    function resetUploadForm() {
        if (fileInput) fileInput.value = '';
        if (fileList) {
            fileList.classList.add('hidden');
            fileList.innerHTML = '<div class="text-sm font-medium">Selected Files:</div>';
        }
        if (uploadBtn) uploadBtn.disabled = true;
        if (uploadProgress) uploadProgress.classList.add('hidden');
        if (progressBar) progressBar.style.width = '0%';
        if (progressPercentage) progressPercentage.textContent = '0%';
    }

    function handleDragOver(e) {
        e.preventDefault();
        dropZone.classList.add('border-primary');
    }

    function handleDragLeave() {
        dropZone.classList.remove('border-primary');
    }

    function handleDrop(e) {
        e.preventDefault();
        dropZone.classList.remove('border-primary');
        handleFiles(e.dataTransfer.files);
    }

    function handleFiles(files) {
        if (files.length > 0 && fileList && uploadBtn) {
            fileList.classList.remove('hidden');
            fileList.innerHTML = '<div class="text-sm font-medium">Selected Files:</div>';
            uploadBtn.disabled = false;

            Array.from(files).forEach(file => {
                const fileItem = document.createElement('div');
                fileItem.className = 'flex items-center justify-between text-sm py-1';
                fileItem.innerHTML = `
                    <div class="flex items-center">
                        <span class="mr-2">📄</span>
                        <span class="truncate max-w-[250px]">${file.name}</span>
                    </div>
                    <span class="text-xs text-text-secondary">${formatFileSize(file.size)}</span>
                `;
                fileList.appendChild(fileItem);
            });
        }
    }

    async function handleUpload() {
        const files = fileInput.files;
        if (files.length === 0) return;

        if (uploadProgress) uploadProgress.classList.remove('hidden');
        uploadBtn.disabled = true;

        const file = files[0];
        const userId = window.userId;

        try {
            const { data, error } = await supabase
                .storage
                .from('docs')
                .upload(`user_${userId}/${file.name}`, file, {
                    cacheControl: '3600',
                    upsert: false,
                    onProgress: (event) => {
                        const percent = (event.loaded / event.total) * 100;
                        if (progressBar) progressBar.style.width = `${percent}%`;
                        if (progressPercentage) progressPercentage.textContent = `${Math.round(percent)}%`;
                    }
                });

            if (error) {
                console.error('Upload error:', error);
                alert(`Upload failed: ${error.message}`);
            } else {
                alert('File uploaded successfully!');
                await saveFileMetadata(file, `user_${userId}/${file.name}`);
                await loadUserFiles();
            }
        } catch (error) {
            console.error('Unexpected error:', error);
            alert(`An unexpected error occurred: ${error.message}`);
        } finally {
            hideUploadModal();
        }
    }

    async function saveFileMetadata(file, filePath) {
        try {
            const response = await fetch('/files', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    file_name: file.name,
                    file_path: filePath,
                    file_size: file.size,
                    file_type: file.type,
                    mime_type: file.type,
                    is_folder: false, // Explicitly set for file uploads
                    parent_id: currentParentId // Associate with the current folder
                })
            });

            if (!response.ok) {
                throw new Error('Failed to save file metadata');
            }
        } catch (error) {
            console.error('Metadata save error:', error);
        }
    }
}

// --- File Management ---
function initializeFileManagement() {
    // Delete button click handler
    document.body.addEventListener('click', async (e) => {
        const deleteButton = e.target.closest('.delete-file-btn');
        if (deleteButton) {
            const itemId = deleteButton.getAttribute('data-file-id'); // This is actually itemId
            const itemElement = deleteButton.closest('.border.border-border-color.rounded-lg');
            const isFolder = itemElement ? itemElement.dataset.isFolder === 'true' : false;
            const itemName = itemElement ? itemElement.dataset.itemName : 'this item';

            const confirmationMessage = isFolder ?
                `Are you sure you want to delete the folder "${itemName}" and all its contents? This action cannot be undone.` :
                `Are you sure you want to delete the file "${itemName}"?`;

            if (itemId && confirm(confirmationMessage)) {
                await deleteItem(itemId); // Renamed from deleteFile
            }
        }
    });

    // Pagination click handler
    document.body.addEventListener('click', (e) => {
        if (e.target.classList.contains('pagination-btn')) {
            const page = parseInt(e.target.getAttribute('data-page'));
            if (!isNaN(page)) {
                currentPage = page;
                loadUserFiles(lastMainSearch, currentPage, currentParentId); // Pass currentParentId
            }
        }
    });
}

// --- Search Functionality ---
function initializeSearch() {
    const searchInput = document.querySelector('input[placeholder*="Search"]');
    let searchTimeout = null;
    let searchResults = [];

    if (!searchInput) return;

    searchInput.addEventListener('input', function(e) {
        const query = this.value.trim();
        
        if (searchTimeout) clearTimeout(searchTimeout);
        
        if (!query) {
            hideSearchDropdown();
            return;
        }

        searchTimeout = setTimeout(async () => {
            try {
                const resp = await fetch(`/files?q=${encodeURIComponent(query)}&page=1`);
                if (!resp.ok) return;
                const data = await resp.json();
                searchResults = (data.files || []).slice(0, 10);
                showSearchDropdown(searchResults);
            } catch (error) {
                console.error('Search error:', error);
            }
        }, 300);
    });

    searchInput.addEventListener('blur', () => {
        setTimeout(hideSearchDropdown, 200);
    });

    searchInput.addEventListener('focus', () => {
        if (searchResults.length) {
            showSearchDropdown(searchResults);
        }
    });

    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            lastMainSearch = this.value.trim();
            currentPage = 1;
            loadUserFiles(lastMainSearch, currentPage);
            hideSearchDropdown();
        }
    });

    // Search icon click handler
    const searchIcon = searchInput.parentElement?.querySelector('span');
    if (searchIcon) {
        searchIcon.addEventListener('click', () => {
            lastMainSearch = searchInput.value.trim();
            currentPage = 1;
            loadUserFiles(lastMainSearch, currentPage);
            hideSearchDropdown();
        });
    }

    // Search dropdown click handler
    document.body.addEventListener('mousedown', (e) => {
        const dropdown = document.getElementById('searchDropdown');
        if (dropdown && !dropdown.classList.contains('hidden') && e.target.closest('#searchDropdown')) {
            const fileId = e.target.getAttribute('data-file-id');
            if (fileId) {
                downloadFile(fileId);
                hideSearchDropdown();
            }
        }
    });
}

// --- Utility Functions ---
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function getFileIcon(fileName) {
    const extension = fileName.split('.').pop()?.toLowerCase();
    const iconMap = {
        'pdf': '📕',
        'doc': '📘',
        'docx': '📘',
        'xls': '📊',
        'xlsx': '📊',
        'ppt': '📋',
        'pptx': '📋',
        'jpg': '🖼️',
        'jpeg': '🖼️',
        'png': '🖼️',
        'gif': '🖼️',
        'txt': '📄',
        'zip': '📦',
        'rar': '📦'
    };
    return iconMap[extension] || '📄';
}

function showSearchDropdown(results) {
    const dropdown = document.getElementById('searchDropdown');
    if (!dropdown) return;

    dropdown.innerHTML = results.map(file =>
        `<div class="p-3 cursor-pointer hover:bg-bg-light text-sm truncate" data-file-id="${file.id}">${file.file_name}</div>`
    ).join('');
    dropdown.classList.remove('hidden');
}

function hideSearchDropdown() {
    const dropdown = document.getElementById('searchDropdown');
    if (dropdown) {
        dropdown.classList.add('hidden');
    }
}

// --- Main File/Folder Loading Function ---
async function loadUserFiles(query = '', page = 1, parentId = null) { // Added parentId parameter
    const itemsContainer = document.getElementById('filesContainer'); // Renamed for clarity
    if (!itemsContainer) {
        console.error('Items container not found');
        return;
    }
    try {
        let url = `/files?page=${page}`;
        if (query) {
            url += `&q=${encodeURIComponent(query)}`;
        }
        if (parentId !== null) { // Check explicitly for null
            url += `&parent_id=${parentId}`;
        }

        const response = await fetch(url);
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || `HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();
        
        itemsContainer.innerHTML = ''; // Clear previous items
        const items = data.files || []; // Backend still uses 'files' key

        if (items.length === 0) {
            itemsContainer.innerHTML = `<div class="p-4 text-center text-text-secondary col-span-full">No items found in this folder.</div>`;
            // Still show pagination if on a page > 1 and no items (e.g. after deleting last item on a page)
            if (data.last_page > 1 && data.current_page > data.last_page) {
                 currentPage = data.last_page; // Go to last available page
                 loadUserFiles(query, currentPage, parentId); // Reload
                 return;
            } else if (data.last_page > 1) {
                 addPaginationControls(itemsContainer, data);
            }
            return;
        }

        items.forEach(item => {
            const itemElement = createItemElement(item); // Renamed function
            itemsContainer.appendChild(itemElement);
        });

        // Add pagination if needed
        if (data.last_page > 1) {
            addPaginationControls(itemsContainer, data);
        }

        // Add event listeners for file/folder interactions
        addItemEventListeners(); // Renamed function

    } catch (error) {
        console.error('Error loading items:', error);
        if (itemsContainer) {
            itemsContainer.innerHTML = `
                <div class="p-4 text-center text-text-secondary col-span-full">
                    <p class="mb-2">Error loading items. Please try again.</p>
                    <p class="text-xs text-red-500">${error.message}</p>
                </div>
            `;
        }
    }
}

function createItemElement(item) { // Renamed from createFileElement, takes generic item
    const itemElement = document.createElement('div');
    itemElement.className = 'border border-border-color rounded-lg overflow-hidden transition-shadow cursor-pointer hover:shadow-md';
    itemElement.dataset.itemId = item.id;
    itemElement.dataset.isFolder = item.is_folder;
    itemElement.dataset.itemName = item.file_name; // Used for navigation breadcrumb

    const itemIcon = item.is_folder ? '📁' : getFileIcon(item.file_name); // Folder icon or file icon
    const itemDate = new Date(item.created_at).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
    
    let badgeHtml = '';
    if (!item.is_folder && ["pdf", "docx", "xlsx", "pptx"].includes(item.file_type?.toLowerCase())) {
        const fileType = item.file_type ? item.file_type.toUpperCase() : '';
        badgeHtml = `<div class="absolute top-2 right-2 bg-[#e8f0fe] text-primary px-1.5 py-0.5 rounded text-xs font-medium">${fileType}</div>`;
    }

    itemElement.innerHTML = `
        <div class="h-[120px] bg-bg-light flex items-center justify-center border-b border-border-color relative">
            <span class="text-3xl">${itemIcon}</span>
            ${badgeHtml}
        </div>
        <div class="p-3 relative">
            <div class="text-sm text-white whitespace-nowrap overflow-hidden text-ellipsis mb-1">${item.file_name}</div>
            <div class="text-xs text-white text-text-secondary">Modified: ${itemDate}</div>
            <button class="delete-file-btn absolute top-3 right-3 text-text-secondary hover:text-danger" data-file-id="${item.id}" title="Delete ${item.is_folder ? 'folder' : 'file'}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
        </div>
    `;

    return itemElement;
}

function addPaginationControls(container, data) {
    let paginationHtml = '<div class="col-span-full flex justify-center mt-4 gap-2">';
    for (let i = 1; i <= data.last_page; i++) {
        const activeClass = i === data.current_page ? 'bg-primary text-white' : 'bg-bg-light text-text-secondary';
        paginationHtml += `<button class="pagination-btn px-3 py-1 rounded ${activeClass}" data-page="${i}">${i}</button>`;
    }
    paginationHtml += '</div>';
    container.insertAdjacentHTML('beforeend', paginationHtml);
}

function addItemEventListeners() { // Renamed from addFileEventListeners
    document.querySelectorAll('#filesContainer .border.border-border-color.rounded-lg').forEach(card => {
        card.addEventListener('click', function(e) {
            if (e.target.closest('.delete-file-btn')) return; // Ignore clicks on delete button

            const itemId = this.dataset.itemId;
            const isFolder = this.dataset.isFolder === 'true';
            const itemName = this.dataset.itemName;

            if (isFolder) {
                // Navigate into folder
                // Update breadcrumbs:
                // Check if we are navigating "back" to an already existing parent in the breadcrumb trail
                const existingIndex = breadcrumbs.findIndex(crumb => crumb.id === itemId);
                if (existingIndex !== -1) {
                    breadcrumbs = breadcrumbs.slice(0, existingIndex + 1);
                } else {
                    // Navigating into a new child folder
                    breadcrumbs.push({ id: itemId, name: itemName });
                }

                currentParentId = itemId;
                currentPage = 1; // Reset to first page of the new folder
                lastMainSearch = ''; // Clear search
                document.querySelector('input[placeholder*="Search"]').value = '';


                loadUserFiles(lastMainSearch, currentPage, currentParentId);
                updateBreadcrumbsDisplay();
            } else {
                // It's a file, download it
                if (itemId) {
                    downloadFile(itemId);
                }
            }
        });
    });
}

// --- File Operations ---
async function downloadFile(fileId) { // fileId here is actually itemId
    try {
        const response = await fetch(`/files/${fileId}`); // Use generic itemId
        if (!response.ok) {
            alert('Failed to fetch file details.');
            console.error('Failed to fetch file details:', response.status, response.statusText);
            return;
        }

        const fileData = await response.json();
        const filePath = fileData.file_path;
        const fileName = fileData.file_name;

        if (!filePath) {
            alert('File path missing in file data. Cannot download file.');
            console.error('File data missing file_path:', fileData);
            return;
        }

        const { data, error } = supabase.storage.from('docs').getPublicUrl(filePath);
        
        if (error) {
            alert('Error generating download URL: ' + error.message);
            console.error('Supabase Storage getPublicUrl error:', error);
            return;
        }

        if (!data?.publicUrl) {
            alert('Could not generate download URL.');
            return;
        }

        const downloadLink = document.createElement('a');
        downloadLink.href = data.publicUrl;
        downloadLink.download = fileName || 'download';
        downloadLink.target = '_blank';
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);

    } catch (error) {
        console.error('Error downloading file:', error);
        alert('Error downloading file. Please try again.');
    }
}

async function deleteItem(itemId) { // Renamed from deleteFile to deleteItem
    try {
        // Get item details first to check if it's a folder and to get path for files
        const response = await fetch(`/files/${itemId}`); // Endpoint remains /files/:id for fetching
        if (!response.ok) {
            alert('Failed to fetch item details.');
            console.error('Failed to fetch item details:', response.status, response.statusText);
            return;
        }

        const itemData = await response.json();

        // If it's a file and has a path, attempt to delete from Supabase storage
        if (!itemData.is_folder && itemData.file_path) {
            const deletePath = itemData.file_path;
            try {
                const result = await supabase.storage.from('docs').remove([deletePath]);
                if (result.error) {
                    // Log error but proceed to DB deletion; maybe file was already gone from storage
                    console.warn('Supabase Storage remove error:', result.error.message);
                }
            } catch (removeErr) {
                console.warn('Exception during Supabase remove:', removeErr.message || removeErr);
            }
        }
        // For folders, Supabase deletion is not directly handled here.
        // The backend's onDelete('cascade') for parent_id should handle children in DB.
        // Actual folder content deletion from storage would need recursive logic if files are stored by folder paths.
        // Current Supabase file paths are `user_${userId}/${file.name}`, not inherently hierarchical in storage.
        // If folders are deleted, their corresponding file entries (is_folder=true) are deleted.
        // Files within these folders (parent_id points to the folder) will be deleted from DB due to cascade.
        // Their actual storage objects in Supabase won't be deleted automatically unless we iterate and delete.
        // This is a more complex operation. For now, we focus on DB record deletion.
        // The user was warned that deleting a folder deletes its contents.

        // Delete from database (works for both files and folders)
        const dbResponse = await fetch(`/files/${itemId}`, { // Endpoint remains /files/:id for deletion
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        if (dbResponse.ok) {
            // Check if current page becomes empty after deletion
            const itemsContainer = document.getElementById('filesContainer');
            const remainingItems = itemsContainer.querySelectorAll('.border.border-border-color.rounded-lg').length -1; // -1 for the one being deleted

            if (remainingItems === 0 && currentPage > 1) {
                currentPage--; // Go to previous page
            }
            await loadUserFiles(lastMainSearch, currentPage, currentParentId); // Reload with currentParentId
        } else {
            const errorData = await dbResponse.json();
            alert(`Failed to delete item from database: ${errorData.message || dbResponse.statusText}`);
            console.error('Failed to delete item from database:', dbResponse.status, dbResponse.statusText, errorData);
        }

    } catch (error) {
        console.error('Error deleting item:', error);
        alert(`Error deleting item: ${error.message}. Please try again.`);
    }
}

// --- Event Listeners for Livewire Integration ---
document.addEventListener('livewire:load', () => {
    console.log('Livewire loaded, reinitializing dashboard...');
    initializeApp();
});

document.addEventListener('livewire:update', () => {
    console.log('Livewire updated, reinitializing dashboard...');
    initializeApp();
});