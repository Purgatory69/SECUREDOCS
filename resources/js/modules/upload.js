import { showNotification } from './ui.js';

// --- Upload Modal ---

let currentUploadFile = null;

function showUploadModal() {
    document.getElementById('uploadModal').classList.remove('hidden');
}

function hideUploadModal() {
    document.getElementById('uploadModal').classList.add('hidden');
    resetUploadForm();
}

function resetUploadForm() {
    const fileInput = document.getElementById('fileInput');
    const uploadProgress = document.getElementById('uploadProgress');
    const uploadBtn = document.getElementById('uploadBtn');
    const dropZone = document.getElementById('dropZone');
    
    if (fileInput) fileInput.value = '';
    if (uploadProgress) uploadProgress.classList.add('hidden');
    if (uploadBtn) uploadBtn.disabled = true;
    if (dropZone) {
        dropZone.classList.remove('border-green-500', 'bg-green-50');
        dropZone.innerHTML = `
            <span class="text-2xl">📁</span>
            <p class="mt-2">Drag & drop files here, or click to select files</p>
            <p class="text-xs text-gray-400">Maximum file size: 100MB</p>
        `;
    }
    currentUploadFile = null;
}

function handleDragOver(e) {
    e.preventDefault();
    document.getElementById('dropZone').classList.add('border-green-500', 'bg-green-50');
}

function handleDragLeave() {
    document.getElementById('dropZone').classList.remove('border-green-500', 'bg-green-50');
}

function handleDrop(e) {
    e.preventDefault();
    document.getElementById('dropZone').classList.remove('border-green-500', 'bg-green-50');
    const files = e.dataTransfer.files;
    if (files.length) {
        document.getElementById('fileInput').files = files;
        handleFiles(files);
    }
}

function handleFiles(files) {
    if (files.length > 0) {
        const file = files[0];
        currentUploadFile = file;
        const dropZone = document.getElementById('dropZone');
        dropZone.innerHTML = `
            <span class="text-2xl">📄</span>
            <p class="mt-2">${file.name}</p>
            <p class="text-xs text-gray-400">${(file.size / 1024 / 1024).toFixed(2)} MB</p>
        `;
        document.getElementById('uploadBtn').disabled = false;
    } else {
        resetUploadForm();
    }
}

async function handleUpload() {
    if (!currentUploadFile) return;

    const uploadBtn = document.getElementById('uploadBtn');
    const progressContainer = document.getElementById('uploadProgress');
    const progressBar = document.getElementById('progressBar');
    const progressPercentage = document.getElementById('progressPercentage');

    uploadBtn.disabled = true;
    progressContainer.classList.remove('hidden');

    try {
        const onProgress = (event) => {
            const percentCompleted = Math.round((event.loaded * 100) / event.total);
            progressBar.style.width = percentCompleted + '%';
            progressPercentage.textContent = percentCompleted + '%';
        };

        const filePath = await window.uploadFileToSupabase(currentUploadFile, onProgress);
        await saveFileMetadata(currentUploadFile, filePath);

        showNotification('File uploaded successfully!', 'success');
        hideUploadModal();
        // Refresh file list in current folder context
        if (typeof window.loadUserFiles === 'function') {
            const parentId = document.getElementById('currentFolderId')?.value || null;
            window.loadUserFiles('', 1, parentId);
        }
    } catch (error) {
        console.error('Upload failed:', error);
        showNotification(`Upload failed: ${error.message}`, 'error');
        uploadBtn.disabled = false;
    }
}

async function saveFileMetadata(file, filePath) {
    const currentFolderIdEl = document.getElementById('currentFolderId');
    let parentId = currentFolderIdEl?.value || null;
    
    // Normalize parent_id: convert 'null' string to null, parse numbers
    if (parentId === 'null' || parentId === '' || parentId === 'undefined') {
        parentId = null;
    } else if (parentId !== null) {
        const parsed = parseInt(parentId, 10);
        if (!isNaN(parsed)) {
            parentId = parsed;
        } else {
            parentId = null;
        }
    }

    const payload = {
        file_name: file.name,
        file_path: filePath,
        file_size: file.size,
        file_type: 'file',
        mime_type: file.type || 'application/octet-stream',
        parent_id: parentId,
        is_folder: false
    };

    console.log('Saving file metadata:', payload);

    try {
        const response = await fetch('/files/upload', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        });

        const responseText = await response.text();
        console.log('Server response:', response.status, responseText);

        if (!response.ok) {
            let errorData;
            try {
                errorData = JSON.parse(responseText);
            } catch (e) {
                errorData = { message: responseText || 'Unknown server error' };
            }
            throw new Error(errorData.message || `Server error: ${response.status}`);
        }

        const result = JSON.parse(responseText);
        console.log('File metadata saved successfully:', result);
        return result;

    } catch (error) {
        console.error('Failed to save file metadata:', error);
        throw error; // Re-throw to be caught by handleUpload
    }
}

export function initializeUploadModal() {
    const uploadModal = document.getElementById('uploadModal');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const uploadBtn = document.getElementById('uploadBtn');
    const cancelUploadBtn = document.getElementById('cancelUploadBtn');

    if (!uploadModal) return;

    // Event Listeners
    closeModalBtn.addEventListener('click', hideUploadModal);
    cancelUploadBtn?.addEventListener('click', hideUploadModal);
    dropZone.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', () => handleFiles(fileInput.files));
    dropZone.addEventListener('dragover', handleDragOver);
    dropZone.addEventListener('dragleave', handleDragLeave);
    dropZone.addEventListener('drop', handleDrop);
    uploadBtn.addEventListener('click', handleUpload);

    // Expose showUploadModal globally for other parts of the app to use
    window.showUploadModal = showUploadModal;
}
