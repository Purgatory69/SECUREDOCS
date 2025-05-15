import { createClient } from '@supabase/supabase-js';

// Get Supabase credentials from environment variables
const supabaseUrl = window.SUPABASE_URL;
const supabaseKey = window.SUPABASE_KEY;
const supabase = createClient(supabaseUrl, supabaseKey);
window.supabase = supabase;

supabase.auth.onAuthStateChange((_event, session) => {
    if (session?.user) {
        window.userId = session.user.id;
    }
});

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

document.addEventListener('DOMContentLoaded', () => {
    // Supabase client already initialized above

    // User Profile Dropdown Functionality
    const userProfileBtn = document.getElementById('userProfileBtn');
    const profileDropdown = document.getElementById('profileDropdown');
    const overlay = document.getElementById('overlay');
    if (userProfileBtn && profileDropdown && overlay) {
        userProfileBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            profileDropdown.classList.toggle('opacity-0');
            profileDropdown.classList.toggle('invisible');
            profileDropdown.classList.toggle('translate-y-[-10px]');
            overlay.classList.toggle('hidden');
        });
        overlay.addEventListener('click', function() {
            profileDropdown.classList.add('opacity-0', 'invisible', 'translate-y-[-10px]');
            overlay.classList.add('hidden');
        });
    }
    document.addEventListener('click', function(event) {
        if (!userProfileBtn || !profileDropdown || !overlay) return;
        const isClickInside = userProfileBtn.contains(event.target) || profileDropdown.contains(event.target);
        if (!isClickInside && !profileDropdown.classList.contains('invisible')) {
            profileDropdown.classList.add('opacity-0', 'invisible', 'translate-y-[-10px]');
            overlay.classList.add('hidden');
        }
    });

    // Upload Modal and File Upload Logic (as before)
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
    if (newBtn) newBtn.addEventListener('click', showUploadModal);
    if (closeModalBtn) closeModalBtn.addEventListener('click', hideUploadModal);
    if (cancelUploadBtn) cancelUploadBtn.addEventListener('click', hideUploadModal);
    if (modalBackdrop) modalBackdrop.addEventListener('click', hideUploadModal);

    if (dropZone) {
        dropZone.addEventListener('click', function() { if (fileInput) fileInput.click(); });
        dropZone.addEventListener('dragover', function(e) { e.preventDefault(); if (this) this.classList.add('border-primary'); });
        dropZone.addEventListener('dragleave', function() { if (this) this.classList.remove('border-primary'); });
        dropZone.addEventListener('drop', function(e) {
            e.preventDefault(); if (this) this.classList.remove('border-primary');
            if (e.dataTransfer.files.length > 0) handleFiles(e.dataTransfer.files);
        });
    }
    if (fileInput) fileInput.addEventListener('change', function() { if (this.files.length > 0) handleFiles(this.files); });

    // Upload button logic
    if (uploadBtn) {
        uploadBtn.addEventListener('click', async function() {
            const files = fileInput.files;
            if (files.length === 0) return;
            if (uploadProgress) uploadProgress.classList.remove('hidden');
            uploadBtn.disabled = true;
            const file = files[0];
            const userId = window.userId;
            try {
                // Skip authentication check since we're using Laravel auth
                // Just use the userId directly from window.userId
                console.log('Using user ID for upload:', window.userId);
                // Try to upload
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
                    console.log('Upload successful:', data);
                    alert('File uploaded successfully!');
                    // Save file metadata to Laravel database
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
                        // Refresh the file list so the new upload appears immediately
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
        });
    }

    // Load files when the page loads
    loadUserFiles();

    // File deletion handlers
    document.body.addEventListener('click', async (e) => {
        if (e.target.classList.contains('delete-btn')) {
            const fileId = e.target.dataset.fileId;
            if (confirm('Are you sure?')) await deleteFile(fileId);
        }
    });
});

// Load and render user's files
async function loadUserFiles() {
    try {
        const response = await fetch('/files');
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
            filesContainer.innerHTML = '<div class="p-4 text-center text-text-secondary col-span-full">No files uploaded yet.</div>';
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
        
        console.log('Downloading file from Supabase Storage:', filePath);
        
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
        
        console.log('Download initiated for file:', fileName);
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
        console.log('Attempting to delete from Supabase Storage (relative path):', deletePath);
        try {
            const result = await supabase.storage.from('docs').remove([deletePath]);
            console.log('Supabase remove result:', result);
            if (result.error) {
                alert('Error deleting file from storage: ' + result.error.message);
                console.error('Supabase Storage remove error:', result.error);
                return;
            }
            if (result.data && result.data.length > 0) {
                console.log('Supabase Storage remove response:', result.data);
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
