
// Global variables for pagination and search
let currentPage = 1;
let lastMainSearch = '';

document.addEventListener('DOMContentLoaded', () => {
    initializeApp();
});

function initializeApp() {
    initializeN8nChat();
    initializeUserProfile();
    initializeUploadModal();
    initializeFileManagement();
    initializeSearch();
    loadUserFiles();
}

// --- N8N Chat Widget Initialization ---
function initializeN8nChat() {
    const currentUserEmail = window.userEmail;
    const currentUserId = window.userId;
    const currentUsername = window.username;

    // if (window.createChat) {
    //     window.createChat({
    //         webhookUrl: 'https://fool1.app.n8n.cloud/webhook/0a216509-e55c-4a43-8d4a-581dffe09d18/chat',
    //         webhookConfig: {
    //             method: 'POST',
    //             headers: {}
    //         },
    //         target: '#n8n-chat-container',
    //         mode: 'window',
    //         chatInputKey: 'chatInput',
    //         chatSessionKey: 'sessionId',
    //         metadata: {
    //             userId: currentUserId,
    //             userEmail: currentUserEmail,
    //             userName: currentUsername
    //         },
    //         showWelcomeScreen: false,
    //         defaultLanguage: 'en',
    //         initialMessages: [
    //             'Hello!',
    //             'My Name is Tubby. How can I assist you today?'
    //         ],
    //         i18n: {
    //             en: {
    //                 title: 'Welcome!',
    //                 subtitle: "Ask me anything.",
    //                 getStarted: 'Start Chatting',
    //                 inputPlaceholder: 'Enter your message here...'
    //             }
    //         },
    //         theme: {
    //             colors: {
    //                 primary: '#4285f4'
    //             }
    //         }
    //     });
    // }
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
                    mime_type: file.type
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
        if (e.target.classList.contains('delete-file-btn') || e.target.closest('.delete-file-btn')) {
            const btn = e.target.closest('.delete-file-btn') || e.target;
            const fileId = btn.getAttribute('data-file-id');
            if (fileId && confirm('Are you sure you want to delete this file?')) {
                await deleteFile(fileId);
            }
        }
    });

    // Pagination click handler
    document.body.addEventListener('click', (e) => {
        if (e.target.classList.contains('pagination-btn')) {
            const page = parseInt(e.target.getAttribute('data-page'));
            if (!isNaN(page)) {
                currentPage = page;
                loadUserFiles(lastMainSearch, currentPage);
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

// --- Main File Loading Function ---
async function loadUserFiles(query = '', page = 1) {
    try {
        let url = `/files?page=${page}`;
        if (query) {
            url += `&q=${encodeURIComponent(query)}`;
        }

        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();
        const filesContainer = document.getElementById('filesContainer');
        
        if (!filesContainer) {
            console.error('Files container not found');
            return;
        }

        filesContainer.innerHTML = '';
        const files = data.files || [];

        if (files.length === 0) {
            filesContainer.innerHTML = '<div class="p-4 text-center text-text-secondary col-span-full">No files found.</div>';
            return;
        }

        files.forEach(file => {
            const fileElement = createFileElement(file);
            filesContainer.appendChild(fileElement);
        });

        // Add pagination if needed
        if (data.last_page > 1) {
            addPaginationControls(filesContainer, data);
        }

        // Add event listeners for file interactions
        addFileEventListeners();

    } catch (error) {
        console.error('Error loading files:', error);
        const filesContainer = document.getElementById('filesContainer');
        if (filesContainer) {
            filesContainer.innerHTML = `
                <div class="p-4 text-center text-text-secondary col-span-full">
                    <p class="mb-2">Error loading files. Please try again.</p>
                    <p class="text-xs text-red-500">${error.message}</p>
                </div>
            `;
        }
    }
}

function createFileElement(file) {
    const fileElement = document.createElement('div');
    fileElement.className = 'border border-border-color rounded-lg overflow-hidden transition-shadow cursor-pointer hover:shadow-md';
    
    const fileIcon = getFileIcon(file.file_name);
    const fileDate = new Date(file.created_at).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
    const fileType = file.file_type ? file.file_type.toUpperCase() : '';
    
    let badgeHtml = '';
    if (["pdf", "docx", "xlsx", "pptx"].includes(file.file_type?.toLowerCase())) {
        badgeHtml = `<div class="absolute top-2 right-2 bg-[#e8f0fe] text-primary px-1.5 py-0.5 rounded text-xs font-medium">${fileType}</div>`;
    }

    fileElement.innerHTML = `
        <div class="h-[120px] bg-bg-light flex items-center justify-center border-b border-border-color relative">
            <span class="text-3xl">${fileIcon}</span>
            ${badgeHtml}
        </div>
        <div class="p-3 relative">
            <div class="text-sm whitespace-nowrap overflow-hidden text-ellipsis mb-1">${file.file_name}</div>
            <div class="text-xs text-text-secondary">Modified: ${fileDate}</div>
            <button class="delete-file-btn absolute top-3 right-3 text-text-secondary hover:text-danger" data-file-id="${file.id}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
        </div>
    `;

    return fileElement;
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

function addFileEventListeners() {
    // File card click events for download
    document.querySelectorAll('.border.border-border-color.rounded-lg').forEach(card => {
        card.addEventListener('click', function(e) {
            if (e.target.closest('.delete-file-btn')) return;
            
            const deleteBtn = this.querySelector('.delete-file-btn');
            if (deleteBtn) {
                const fileId = deleteBtn.getAttribute('data-file-id');
                if (fileId) {
                    downloadFile(fileId);
                }
            }
        });
    });
}

// --- File Operations ---
async function downloadFile(fileId) {
    try {
        const response = await fetch(`/files/${fileId}`);
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

async function deleteFile(fileId) {
    try {
        // Get file details first
        const response = await fetch(`/files/${fileId}`);
        if (!response.ok) {
            alert('Failed to fetch file details.');
            console.error('Failed to fetch file details:', response.status, response.statusText);
            return;
        }

        const fileData = await response.json();
        const deletePath = fileData.file_path;

        if (!deletePath) {
            alert('File path missing in file data. Cannot delete from storage.');
            console.error('File data missing file_path:', fileData);
            return;
        }

        // Delete from Supabase storage
        try {
            const result = await supabase.storage.from('docs').remove([deletePath]);
            if (result.error) {
                alert('Error deleting file from storage: ' + result.error.message);
                console.error('Supabase Storage remove error:', result.error);
                return;
            }
        } catch (removeErr) {
            console.error('Exception during Supabase remove:', removeErr);
            alert('Exception during Supabase remove: ' + (removeErr.message || removeErr));
            return;
        }

        // Delete from database
        const dbResponse = await fetch(`/files/${fileId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        if (dbResponse.ok) {
            await loadUserFiles(lastMainSearch, currentPage);
        } else {
            alert('Failed to delete file record from database.');
            console.error('Failed to delete file record from database:', dbResponse.status, dbResponse.statusText);
        }

    } catch (error) {
        console.error('Error deleting file:', error);
        alert('Error deleting file. Please try again.');
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