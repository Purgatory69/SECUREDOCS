import { showNotification } from './ui.js';

// --- Upload Modal ---

let currentUploadFile = null;
let isPremiumUser = null; // cached premium status
let fetchedPremiumOnce = false;

async function ensurePremiumStatus() {
    if (fetchedPremiumOnce && isPremiumUser !== null) return isPremiumUser;
    try {
        const res = await fetch('/blockchain/storage-info');
        const data = await res.json();
        isPremiumUser = !!data?.user_premium;
    } catch (e) {
        // If unavailable, assume non-premium to be safe (UI will still validate on submit)
        isPremiumUser = false;
    } finally {
        fetchedPremiumOnce = true;
    }
    return isPremiumUser;
}

function applyPremiumUX() {
    const premiumCards = document.querySelectorAll('label[data-premium-option="true"]');
    const mappings = [
        { badge: 'badgeBlockchain', desc: 'descBlockchain', text: 'Store on IPFS via Pinata' },
        { badge: 'badgeVectorize', desc: 'descVectorize', text: 'Process with AI for advanced search' },
        { badge: 'badgeHybrid', desc: 'descHybrid', text: 'Store on IPFS and vectorize' },
    ];

    if (isPremiumUser) {
        // Hide premium badges and remove "Premium required" phrasing
        mappings.forEach(({ badge, desc, text }) => {
            const b = document.getElementById(badge);
            const d = document.getElementById(desc);
            if (b) b.classList.add('hidden');
            if (d) d.textContent = text;
        });

        premiumCards.forEach(label => {
            const input = label.querySelector('input[type="radio"]');
            const card = label.querySelector('.rounded-lg');
            if (input) input.disabled = false;
            if (card) card.classList.remove('opacity-60', 'cursor-not-allowed');
        });
    } else {
        // Keep badges visible and disable selection of premium-only options
        mappings.forEach(({ badge, desc }) => {
            const b = document.getElementById(badge);
            const d = document.getElementById(desc);
            if (b) b.classList.remove('hidden');
            if (d && !/Premium required/i.test(d.textContent)) {
                // Ensure the copy indicates premium-only
                d.textContent = d.textContent + ' (Premium required)';
            }
        });

        premiumCards.forEach(label => {
            const input = label.querySelector('input[type="radio"]');
            const card = label.querySelector('.rounded-lg');
            if (input) input.disabled = true;
            if (card) card.classList.add('opacity-60', 'cursor-not-allowed');

            if (!label.dataset.listenerAttached) {
                label.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const std = document.getElementById('standardUpload');
                    if (std) std.checked = true;
                    showNotification('Subscribe to Premium to avail this feature.', 'warning');
                });
                label.dataset.listenerAttached = 'true';
            }
        });
    }
}

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
    const processingOptions = document.getElementById('processingOptions');
    
    if (fileInput) fileInput.value = '';
    if (uploadProgress) uploadProgress.classList.add('hidden');
    if (uploadBtn) uploadBtn.disabled = true;
    if (dropZone) {
        dropZone.classList.remove('border-primary', 'ring-2', 'ring-primary/40');
        const dropZoneContent = document.getElementById('dropZoneContent');
        if (dropZoneContent) {
            dropZoneContent.innerHTML = `
                <div class="text-4xl mb-3">📄</div>
                <p class="mb-1 text-sm">Drag and drop files here or click to browse</p>
                <p class="text-xs text-text-secondary">Maximum file size: 100MB</p>
            `;
        }
    }
    if (processingOptions) {
        processingOptions.style.display = 'none';
        document.getElementById('standardUpload').checked = true;
    }
    currentUploadFile = null;
}

function handleDragOver(e) {
    e.preventDefault();
    document.getElementById('dropZone').classList.add('border-primary', 'ring-2', 'ring-primary/40');
}

function handleDragLeave() {
    document.getElementById('dropZone').classList.remove('border-primary', 'ring-2', 'ring-primary/40');
}

function handleDrop(e) {
    e.preventDefault();
    document.getElementById('dropZone').classList.remove('border-primary', 'ring-2', 'ring-primary/40');
    const files = e.dataTransfer.files;
    if (files.length) {
        document.getElementById('fileInput').files = files;
        handleFiles(files);
    }
}

async function handleFiles(files) {
    if (files.length > 0) {
        const file = files[0];
        currentUploadFile = file;
        const dropZoneContent = document.getElementById('dropZoneContent');
        if (dropZoneContent) {
            dropZoneContent.innerHTML = `
                <div class="text-3xl mb-2">📄</div>
                <p class="text-sm text-white truncate max-w-[90%] mx-auto">${file.name}</p>
                <p class="text-xs text-gray-400">${(file.size / 1024 / 1024).toFixed(2)} MB</p>
            `;
        }
        
        // Show processing options and run validation
        showProcessingOptions();
        await ensurePremiumStatus();
        applyPremiumUX();
        validateProcessingOptions(file);
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
    const processingType = document.querySelector('input[name="processingType"]:checked')?.value || 'standard';

    uploadBtn.disabled = true;
    progressContainer.classList.remove('hidden');

    try {
        const onProgress = (event) => {
            const percentCompleted = Math.round((event.loaded * 100) / event.total);
            progressBar.style.width = percentCompleted + '%';
            progressPercentage.textContent = percentCompleted + '%';
        };

        const filePath = await window.uploadFileToSupabase(currentUploadFile, onProgress);
        await saveFileMetadata(currentUploadFile, filePath, processingType);

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

async function saveFileMetadata(file, filePath, processingType = 'standard') {
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
        is_folder: false,
        processing_type: processingType
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

// Processing Options Functions
function showProcessingOptions() {
    const processingOptions = document.getElementById('processingOptions');
    if (processingOptions) {
        processingOptions.style.display = 'block';
    }
}

async function validateProcessingOptions(file) {
    const blockchainRadio = document.getElementById('blockchainUpload');
    const vectorizeRadio = document.getElementById('vectorizeUpload');
    const hybridRadio = document.getElementById('hybridUpload');
    const validationDiv = document.getElementById('processingValidation');

    // Add event listeners for validation on change
    document.querySelectorAll('input[name="processingType"]').forEach(radio => {
        radio.addEventListener('change', () => validateSelectedOption(file));
    });

    // Initial validation
    await validateSelectedOption(file);
}

async function validateSelectedOption(file) {
    const selectedType = document.querySelector('input[name="processingType"]:checked')?.value;
    const validationDiv = document.getElementById('processingValidation');
    const uploadBtn = document.getElementById('uploadBtn');
    
    if (!selectedType || selectedType === 'standard') {
        validationDiv.classList.add('hidden');
        uploadBtn.disabled = false;
        return;
    }

    // Show validation for premium features
    validationDiv.classList.remove('hidden');
    validationDiv.innerHTML = '<div class="text-yellow-400">Validating...</div>';

    try {
        const validationResults = await validatePremiumProcessing(file, selectedType);
        displayValidationResults(validationResults, selectedType);
        
        // Enable/disable upload button based on validation
        uploadBtn.disabled = !validationResults.canProceed;
    } catch (error) {
        validationDiv.innerHTML = `<div class="text-red-400">Validation failed: ${error.message}</div>`;
        uploadBtn.disabled = true;
    }
}

async function validatePremiumProcessing(file, processingType) {
    const results = {
        canProceed: false,
        errors: [],
        warnings: [],
        info: []
    };

    // Check user premium status and storage info
    try {
        const storageResponse = await fetch('/blockchain/storage-info');
        const storageData = await storageResponse.json();
        
        if (!storageData.success) {
            results.errors.push('Unable to verify premium status');
            return results;
        }

        const isPremium = storageData.user_premium;
        const requirements = storageData.requirements;

        // Validate based on processing type
        if (processingType === 'blockchain' || processingType === 'hybrid') {
            if (!isPremium) {
                results.errors.push('Premium subscription required for blockchain storage');
            } else if (!requirements.configured) {
                results.errors.push('Blockchain provider not configured');
            } else if (file.size > requirements.max_file_size) {
                results.errors.push(`File size (${formatFileSize(file.size)}) exceeds blockchain limit (${requirements.max_file_size_human})`);
            } else {
                results.info.push(`Will upload to ${requirements.provider} blockchain storage`);
            }
        }

        if (processingType === 'vectorize' || processingType === 'hybrid') {
            if (!isPremium) {
                results.errors.push('Premium subscription required for AI vectorization');
            } else {
                results.info.push('Will process file with AI for enhanced search capabilities');
            }
        }

        // Check file type support
        const supportedTypes = requirements.supported_file_types || [];
        const fileExtension = file.name.split('.').pop()?.toLowerCase();
        
        if (supportedTypes.length > 0 && !supportedTypes.includes(fileExtension)) {
            results.warnings.push(`File type '.${fileExtension}' may not be fully supported`);
        }

        results.canProceed = results.errors.length === 0;
        return results;

    } catch (error) {
        results.errors.push('Validation service unavailable');
        return results;
    }
}

function displayValidationResults(results, processingType) {
    const validationDiv = document.getElementById('processingValidation');
    let html = '';

    if (results.errors.length > 0) {
        html += `
            <div class="bg-red-900/30 border border-red-600 rounded p-3 mb-2">
                <div class="font-medium text-red-300 mb-1">❌ Cannot Proceed</div>
                <ul class="text-sm text-red-200 space-y-1">
                    ${results.errors.map(error => `<li>• ${error}</li>`).join('')}
                </ul>
            </div>
        `;
    }

    if (results.warnings.length > 0) {
        html += `
            <div class="bg-yellow-900/30 border border-yellow-600 rounded p-3 mb-2">
                <div class="font-medium text-yellow-300 mb-1">⚠️ Warnings</div>
                <ul class="text-sm text-yellow-200 space-y-1">
                    ${results.warnings.map(warning => `<li>• ${warning}</li>`).join('')}
                </ul>
            </div>
        `;
    }

    if (results.info.length > 0) {
        html += `
            <div class="bg-blue-900/30 border border-blue-600 rounded p-3 mb-2">
                <div class="font-medium text-blue-300 mb-1">ℹ️ Processing Details</div>
                <ul class="text-sm text-blue-200 space-y-1">
                    ${results.info.map(info => `<li>• ${info}</li>`).join('')}
                </ul>
            </div>
        `;
    }

    if (results.canProceed && results.errors.length === 0) {
        html += `
            <div class="bg-green-900/30 border border-green-600 rounded p-3">
                <div class="font-medium text-green-300">✅ Ready to Upload</div>
                <div class="text-sm text-green-200 mt-1">All validation checks passed for ${processingType} processing</div>
            </div>
        `;
    }

    validationDiv.innerHTML = html;
}

function formatFileSize(bytes) {
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes;
    let unitIndex = 0;
    
    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
    }
    
    return `${size.toFixed(1)} ${units[unitIndex]}`;
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
