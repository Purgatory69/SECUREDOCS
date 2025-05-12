import { createClient } from '@supabase/supabase-js';

// Get Supabase credentials from environment variables
const supabaseUrl = import.meta.env.VITE_SUPABASE_URL;
const supabaseKey = import.meta.env.VITE_SUPABASE_KEY;
const supabase = createClient(supabaseUrl, supabaseKey);

supabase.auth.onAuthStateChange((_event, session) => {
    if (session?.user) {
        window.userId = session.user.id;
    }
});

// Wait for DOM to load before attaching event listeners
document.addEventListener('DOMContentLoaded', () => {
    // Get elements
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

    // Show modal function
    function showUploadModal() {
        if (!uploadModal) return;
        uploadModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    // Hide modal function
    function hideUploadModal() {
        if (!uploadModal) return;
        uploadModal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        
        // Reset modal state
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

    // Add event listeners
    if (newBtn) {
        newBtn.addEventListener('click', showUploadModal);
    }

    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', hideUploadModal);
    }

    if (cancelUploadBtn) {
        cancelUploadBtn.addEventListener('click', hideUploadModal);
    }

    if (modalBackdrop) {
        modalBackdrop.addEventListener('click', hideUploadModal);
    }

    // Handle file selection
    if (dropZone) {
        dropZone.addEventListener('click', function() {
            if (fileInput) fileInput.click();
        });

        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            if (this) this.classList.add('border-primary');
        });

        dropZone.addEventListener('dragleave', function() {
            if (this) this.classList.remove('border-primary');
        });

        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            if (this) this.classList.remove('border-primary');
            
            if (e.dataTransfer.files.length > 0 && fileList) {
                handleFiles(e.dataTransfer.files);
            }
        });
    }

    if (fileInput) {
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0 && fileList) {
                handleFiles(this.files);
            }
        });
    }

    // Handle file display
    function handleFiles(files) {
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
        
        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) return '🖼️';
        if (['pdf'].includes(extension)) return '📄';
        if (['doc', 'docx'].includes(extension)) return '📝';
        if (['xls', 'xlsx', 'csv'].includes(extension)) return '📊';
        if (['ppt', 'pptx'].includes(extension)) return '🎬';
        if (['zip', 'rar', '7z'].includes(extension)) return '📦';
        return '📄';
    }

    // Handle upload
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
                    .from('files')
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
});
