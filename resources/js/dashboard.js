import { createClient } from '@supabase/supabase-js';

console.log('Dashboard JS loaded!');

// Get Supabase credentials from global window object (set in Blade)
const supabaseUrl = window.SUPABASE_URL;
const supabaseKey = window.SUPABASE_KEY;
const supabase = createClient(supabaseUrl, supabaseKey);
window.supabase = supabase;

// --- Dashboard Logic from old app.js ---

// Handle file display
function handleFiles(files) {
    const fileList = document.getElementById('fileList');
    const uploadBtn = document.getElementById('uploadBtn');
    if (!fileList || files.length === 0) return;
    fileList.classList.remove('hidden');
    fileList.innerHTML = '<div class="text-sm font-medium">Selected Files:</div>';
    uploadBtn.disabled = false;
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const fileSize = (file.size / (1024 * 1024)).toFixed(2); // in MB
        const fileItem = document.createElement('div');
        fileItem.className = 'flex items-center justify-between text-sm p-2 bg-bg-light rounded';
        fileItem.innerHTML = `
            <div class="flex items-center">
                <span class="mr-2">${getFileIcon(file.name)}</span>
                <span class="truncate max-w-[200px]">${file.name}</span>
            </div>
            <span class="text-text-secondary text-xs">${fileSize} MB</span>
        `;
        fileList.appendChild(fileItem);
    }
}

// Get file icon based on extension
function getFileIcon(fileName) {
    const extension = fileName.split('.').pop().toLowerCase();
    if (["jpg", "jpeg", "png", "gif", "webp"].includes(extension)) return '🖼️';
    if (["pdf"].includes(extension)) return '📄';
    if (["doc", "docx"].includes(extension)) return '📝';
    if (["xls", "xlsx", "csv"].includes(extension)) return '📊';
    if (["ppt", "pptx"].includes(extension)) return '🎬';
    if (["zip", "rar", "7z"].includes(extension)) return '📦';
    return '📄';
}

// --- Live Search Dropdown Implementation ---
let searchTimeout = null;
let lastSearchQuery = '';
let searchResults = [];

function createSearchDropdown() {
    let dropdown = document.getElementById('searchDropdown');
    if (!dropdown) {
        dropdown = document.createElement('div');
        dropdown.id = 'searchDropdown';
        dropdown.className = 'absolute left-0 right-0 top-full bg-white border border-border-color rounded-lg shadow-lg z-30 mt-2 max-h-64 overflow-y-auto hidden';
        dropdown.style.minWidth = '200px';
        const searchInput = document.querySelector('input[placeholder*="Search"]');
        if (searchInput && searchInput.parentElement) {
            searchInput.parentElement.appendChild(dropdown);
        }
    }
    return dropdown;
}

function showSearchDropdown(results) {
    const dropdown = createSearchDropdown();
    if (!results.length) {
        dropdown.innerHTML = '<div class="p-3 text-text-secondary text-sm">No files found</div>';
    } else {
        dropdown.innerHTML = results.map(file =>
            `<div class="p-3 cursor-pointer hover:bg-bg-light text-sm truncate" data-file-id="${file.id}">${file.file_name}</div>`
        ).join('');
    }
    dropdown.classList.remove('hidden');
}

function hideSearchDropdown() {
    const dropdown = document.getElementById('searchDropdown');
    if (dropdown) dropdown.classList.add('hidden');
}

// --- DOMContentLoaded for dashboard logic ---
function attachDashboardEventListeners() {
    console.log('Attaching dashboard event listeners...');
    // User Profile Dropdown Functionality
    const userProfileBtn = document.getElementById('userProfileBtn');
    const profileDropdown = document.getElementById('profileDropdown');
    const overlay = document.getElementById('overlay');
    if (userProfileBtn && profileDropdown && overlay) {
        userProfileBtn.onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            profileDropdown.classList.toggle('opacity-0');
            profileDropdown.classList.toggle('invisible');
            profileDropdown.classList.toggle('translate-y-[-10px]');
            overlay.classList.toggle('hidden');
        };
        overlay.onclick = function() {
            profileDropdown.classList.add('opacity-0', 'invisible', 'translate-y-[-10px]');
            overlay.classList.add('hidden');
        };
    }
    document.onclick = function(event) {
        if (!userProfileBtn || !profileDropdown || !overlay) return;
        const isClickInside = userProfileBtn.contains(event.target) || profileDropdown.contains(event.target);
        if (!isClickInside && !profileDropdown.classList.contains('invisible')) {
            profileDropdown.classList.add('opacity-0', 'invisible', 'translate-y-[-10px]');
            overlay.classList.add('hidden');
        }
    };

    // Upload Modal and File Upload Logic
    const newBtn = document.getElementById('newBtn');
    const uploadModal = document.getElementById('uploadModal');
    const modalBackdrop = document.getElementById('modalBackdrop');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const cancelUploadBtn = document.getElementById('cancelUploadBtn');
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const fileList = document.getElementById('fileList');
    const uploadBtn = document.getElementById('uploadBtn');
    const uploadProgress = document.getElementById('uploadProgress');
    const progressBar = document.getElementById('progressBar');
    const progressPercentage = document.getElementById('progressPercentage');

    function showUploadModal() {
        if (!uploadModal) return;
        uploadModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }
    function hideUploadModal() {
        if (!uploadModal) return;
        uploadModal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
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
    if (newBtn) newBtn.onclick = showUploadModal;
    if (closeModalBtn) closeModalBtn.onclick = hideUploadModal;
    if (cancelUploadBtn) cancelUploadBtn.onclick = hideUploadModal;
    if (modalBackdrop) modalBackdrop.onclick = hideUploadModal;

    if (dropZone) {
        dropZone.onclick = function() { if (fileInput) fileInput.click(); };
        dropZone.ondragover = function(e) { e.preventDefault(); if (this) this.classList.add('border-primary'); };
        dropZone.ondragleave = function() { if (this) this.classList.remove('border-primary'); };
        dropZone.ondrop = function(e) {
            e.preventDefault(); if (this) this.classList.remove('border-primary');
            if (e.dataTransfer.files.length > 0) handleFiles(e.dataTransfer.files);
        };
    }
    if (fileInput) fileInput.onchange = function() { if (this.files.length > 0) handleFiles(this.files); };

    // Upload button logic
    if (uploadBtn) {
        uploadBtn.onclick = async function() {
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
                    try {
                        const response = await fetch('/files', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                file_name: file.name,
                                file_path: `user_${userId}/${file.name}`,
                                file_size: file.size,
                                file_type: file.type,
                                mime_type: file.type
                            })
                        });
                        if (!response.ok) throw new Error('Failed to save file metadata');
                        await loadUserFiles();
                    } catch (error) {
                        console.error('Metadata save error:', error);
                    }
                }
            } catch (error) {
                console.error('Unexpected error:', error);
                alert(`An unexpected error occurred: ${error.message}`);
            } finally {
                hideUploadModal();
            }
        };
    }

    // File deletion handlers
    document.body.onclick = async (e) => {
        if (e.target.classList.contains('delete-btn')) {
            const fileId = e.target.dataset.fileId;
            if (confirm('Are you sure?')) await deleteFile(fileId);
        }
    };

    // --- Live Search Handler ---
    const searchInput = document.querySelector('input[placeholder*="Search"]');
    let searchTimeout = null;
    let lastSearchQuery = '';
    let searchResults = [];
    if (searchInput) {
        searchInput.oninput = function(e) {
            const query = this.value.trim();
            lastSearchQuery = query;
            if (searchTimeout) clearTimeout(searchTimeout);
            if (!query) {
                hideSearchDropdown();
                return;
            }
            searchTimeout = setTimeout(async () => {
                const resp = await fetch(`/files?q=${encodeURIComponent(query)}&page=1`);
                if (!resp.ok) return;
                const data = await resp.json();
                searchResults = (data.files || []).slice(0, 10);
                showSearchDropdown(searchResults);
            }, 300);
        };
        searchInput.onblur = () => setTimeout(hideSearchDropdown, 200);
        searchInput.onfocus = () => {
            if (searchResults.length) showSearchDropdown(searchResults);
        };
        searchInput.onkeydown = function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                lastMainSearch = this.value.trim();
                currentPage = 1;
                loadUserFiles(lastMainSearch, currentPage);
                hideSearchDropdown();
            }
        };
    }
    const searchIcon = searchInput?.parentElement?.querySelector('span');
    if (searchIcon) {
        searchIcon.onclick = function() {
            if (searchInput) {
                lastMainSearch = searchInput.value.trim();
                currentPage = 1;
                loadUserFiles(lastMainSearch, currentPage);
                hideSearchDropdown();
            }
        };
    }
    document.body.onmousedown = function(e) {
        const dropdown = document.getElementById('searchDropdown');
        if (dropdown && !dropdown.classList.contains('hidden') && e.target.closest('#searchDropdown')) {
            const fileId = e.target.getAttribute('data-file-id');
            if (fileId) {
                downloadFile(fileId);
                hideSearchDropdown();
            }
        }
    };
    window.onclick = function(e) {
        if (e.target.classList.contains('pagination-btn')) {
            const page = parseInt(e.target.getAttribute('data-page'));
            if (!isNaN(page)) {
                currentPage = page;
                loadUserFiles(lastMainSearch, currentPage);
            }
        }
    };
}

// Attach on DOMContentLoaded
// Also re-run after Livewire updates
function initializeDashboard() {
    attachDashboardEventListeners();
    loadUserFiles();
}

document.addEventListener('DOMContentLoaded', initializeDashboard);
document.addEventListener('livewire:load', initializeDashboard);

// Update loadUserFiles to support search and pagination
async function loadUserFiles(query = '', page = 1) {
    try {
        let url = `/files?page=${page}`;
        if (query) url += `&q=${encodeURIComponent(query)}`;
        const response = await fetch(url);
        if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
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
            const fileElement = document.createElement('div');
            fileElement.className = 'border border-border-color rounded-lg overflow-hidden transition-shadow cursor-pointer hover:shadow-md';
            const fileIcon = getFileIcon(file.file_name);
            const fileDate = new Date(file.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
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
            filesContainer.appendChild(fileElement);
        });
        // Pagination controls
        if (data.last_page > 1) {
            let paginationHtml = '<div class="col-span-full flex justify-center mt-4 gap-2">';
            for (let i = 1; i <= data.last_page; i++) {
                paginationHtml += `<button class="pagination-btn px-3 py-1 rounded ${i === data.current_page ? 'bg-primary text-white' : 'bg-bg-light text-text-secondary'}" data-page="${i}">${i}</button>`;
            }
            paginationHtml += '</div>';
            filesContainer.insertAdjacentHTML('beforeend', paginationHtml);
        }
        // Delete button events
        document.querySelectorAll('.delete-file-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const fileId = this.getAttribute('data-file-id');
                if (confirm('Are you sure you want to delete this file?')) {
                    deleteFile(fileId);
                }
            });
        });
        // Card click events for download
        document.querySelectorAll('.border.border-border-color.rounded-lg').forEach(card => {
            card.addEventListener('click', function(e) {
                if (e.target.closest('.delete-file-btn')) return;
                // Find the file ID from the delete button within this card
                const deleteBtn = this.querySelector('.delete-file-btn');
                if (deleteBtn) {
                    const fileId = deleteBtn.getAttribute('data-file-id');
                    if (fileId) {
                        downloadFile(fileId);
                    }
                }
            });
        });
    } catch (error) {
        console.error('Error loading files:', error);
    }
}

async function downloadFile(fileId) {
    try {
        // First get the file details to know the path in storage
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
        // Generate the public URL for the file
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
        // Create a temporary link element to trigger the download
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
        // Then delete from database
        const dbResponse = await fetch(`/files/${fileId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        if (dbResponse.ok) {
            loadUserFiles();
        } else {
            alert('Failed to delete file record from database.');
            console.error('Failed to delete file record from database:', dbResponse.status, dbResponse.statusText);
        }
    } catch (error) {
        console.error('Error deleting file:', error);
        alert('Error deleting file. Please try again.');
    }
}

// --- End of dashboard logic ---

// TODO: In the future, refactor modal, file list, and search into Livewire/Alpine components for better reactivity and maintainability. 