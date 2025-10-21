/**
 * Blockchain Upload Module
 * Handles uploading existing user files to blockchain storage with preflight validation
 */

class BlockchainUpload {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadStorageInfo();
    }

    bindEvents() {
        // Bind file row blockchain upload buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-blockchain-upload]')) {
                const fileId = parseInt(e.target.dataset.blockchainUpload);
                this.showUploadModal(fileId);
            }
        });

        // Bind bulk upload button
        const bulkUploadBtn = document.getElementById('bulkBlockchainUpload');
        if (bulkUploadBtn) {
        }
    }

    async loadStorageInfo() {
        try {
            // Skip old blockchain storage info - using new Bundlr approach
            // console.log('📱 Using new Bundlr approach - skipping old storage info');
            
            // Set dummy storage info for compatibility
            this.storageInfo = {
                success: true,
                requirements: {
                    max_file_size: 100 * 1024 * 1024, // 100MB
                    supported_types: ['pdf', 'doc', 'docx', 'txt', 'jpg', 'png'],
                    max_files_per_user: 1000
                },
                current_stats: {
                    total_files: 0,
                    total_size: 0
                },
                eligible_files_count: 0,
                user_premium: true
            };
            
            this.renderStorageInfo();
            
        } catch (error) {
            // console.log('📱 Blockchain storage info skipped (using new Bundlr approach)');
        }
    }

    updateStorageDisplay(data) {
        const storageInfo = document.getElementById('blockchainStorageInfo');
        if (!storageInfo) return;

        const requirements = data.requirements;
        const stats = data.current_stats;

        storageInfo.innerHTML = `
            <div class="bg-[#1a1a2e] rounded-lg p-4 border border-[#333]">
                <h3 class="text-white font-medium mb-3 flex items-center">
                    <span class="mr-2">🔗</span>
                    Blockchain Storage
                </h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <div class="text-gray-400">Provider</div>
                        <div class="text-white">${requirements.provider || 'Not configured'}</div>
                    </div>
                    <div>
                        <div class="text-gray-400">Max File Size</div>
                        <div class="text-white">${requirements.max_file_size_human || 'N/A'}</div>
                    </div>
                    <div>
                        <div class="text-gray-400">Files Stored</div>
                        <div class="text-white">${stats.total_blockchain_files || 0}</div>
                    </div>
                    <div>
                        <div class="text-gray-400">Eligible Files</div>
                        <div class="text-white">${data.eligible_files_count || 0}</div>
                    </div>
                </div>
                ${!data.user_premium ? `
                    <div class="mt-3 p-3 bg-yellow-900/30 border border-yellow-600 rounded text-yellow-300 text-xs">
                        Premium subscription required for blockchain storage
                    </div>
                ` : ''}
            </div>
        `;
    }

    async showUploadModal(fileId) {
        // First, run preflight validation
        const validation = await this.runPreflightValidation(fileId);
        
        const modal = this.createUploadModal(fileId, validation);
        document.body.appendChild(modal);
        
        // Show modal
        setTimeout(() => {
            modal.classList.remove('opacity-0', 'scale-95');
            modal.classList.add('opacity-100', 'scale-100');
        }, 10);
    }

    async runPreflightValidation(fileId, provider = null) {
        try {
            const formData = new FormData();
            formData.append('file_id', fileId);
            if (provider) formData.append('provider', provider);

            const response = await fetch('/blockchain/preflight-validation', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'include'
            });

            return await response.json();
        } catch (error) {
            console.error('Preflight validation failed:', error);
            return {
                success: false,
                validation: { errors: ['Network error during validation'] }
            };
        }
    }

    createUploadModal(fileId, validationData) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 z-50 flex items-center justify-center opacity-0 scale-95 transition-all duration-200';
        modal.innerHTML = `
            <div class="fixed inset-0 bg-black bg-opacity-50" onclick="this.parentElement.remove()"></div>
            <div class="bg-[#0D0E2F] rounded-lg shadow-xl w-full max-w-lg p-6 relative z-10 text-white border border-[#333]">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-medium flex items-center">
                        <span class="mr-2">🔗</span>
                        Upload to Blockchain
                    </h3>
                    <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-white text-2xl">
                        &times;
                    </button>
                </div>

                <div class="space-y-4">
                    ${this.renderValidationResults(validationData)}
                    ${this.renderFileInfo(validationData)}
                    ${this.renderProviderInfo(validationData)}
                    ${this.renderUploadActions(fileId, validationData)}
                </div>
            </div>
        `;
        return modal;
    }

    renderValidationResults(data) {
        if (!data.validation) return '';

        const { errors, warnings } = data.validation;
        let html = '';

        if (errors && errors.length > 0) {
            html += `
                <div class="p-3 bg-red-900/30 border border-red-600 rounded">
                    <div class="font-medium text-red-300 mb-2">❌ Upload Blocked</div>
                    <ul class="text-sm text-red-200 space-y-1">
                        ${errors.map(error => `<li>• ${error}</li>`).join('')}
                    </ul>
                </div>
            `;
        }

        if (warnings && warnings.length > 0) {
            html += `
                <div class="p-3 bg-yellow-900/30 border border-yellow-600 rounded">
                    <div class="font-medium text-yellow-300 mb-2">⚠️ Warnings</div>
                    <ul class="text-sm text-yellow-200 space-y-1">
                        ${warnings.map(warning => `<li>• ${warning}</li>`).join('')}
                    </ul>
                </div>
            `;
        }

        if (data.success) {
            html += `
                <div class="p-3 bg-green-900/30 border border-green-600 rounded">
                    <div class="font-medium text-green-300">✅ Ready for Upload</div>
                    <div class="text-sm text-green-200 mt-1">All validation checks passed</div>
                </div>
            `;
        }

        return html;
    }

    renderFileInfo(data) {
        const fileInfo = data.validation?.file_info;
        if (!fileInfo) return '';

        return `
            <div class="bg-[#1a1a2e] rounded-lg p-4 border border-[#333]">
                <div class="font-medium mb-2">File Information</div>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div class="text-gray-400">Size:</div>
                    <div>${fileInfo.size_human}</div>
                    <div class="text-gray-400">Type:</div>
                    <div>${fileInfo.type || 'Unknown'}</div>
                    <div class="text-gray-400">Extension:</div>
                    <div>.${fileInfo.extension || 'unknown'}</div>
                </div>
            </div>
        `;
    }

    renderProviderInfo(data) {
        const providerInfo = data.provider_info;
        if (!providerInfo) return '';

        return `
            <div class="bg-[#1a1a2e] rounded-lg p-4 border border-[#333]">
                <div class="font-medium mb-2">Provider Information</div>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div class="text-gray-400">Provider:</div>
                    <div>${providerInfo.name}</div>
                    <div class="text-gray-400">Max Size:</div>
                    <div>${providerInfo.max_file_size_human}</div>
                </div>
            </div>
        `;
    }

    renderUploadActions(fileId, data) {
        const canUpload = data.success;
        const hasWarnings = data.validation?.warnings?.length > 0;

        return `
            <div class="flex justify-end space-x-3 pt-4 border-t border-[#333]">
                <button onclick="this.closest('.fixed').remove()" 
                        class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition-colors">
                    Cancel
                </button>
                <button onclick="blockchainUpload.uploadFile(${fileId}, ${hasWarnings})" 
                        class="px-4 py-2 ${canUpload ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-500 cursor-not-allowed'} text-white rounded transition-colors"
                        ${!canUpload ? 'disabled' : ''}>
                    ${hasWarnings ? 'Upload Anyway' : 'Upload to Blockchain'}
                </button>
            </div>
        `;
    }

    async uploadFile(fileId, force = false) {
        const modal = document.querySelector('.fixed.inset-0.z-50');
        const uploadBtn = modal.querySelector('button[onclick*="uploadFile"]');
        
        // Update button state
        uploadBtn.disabled = true;
        uploadBtn.textContent = 'Uploading...';
        uploadBtn.className = uploadBtn.className.replace('bg-blue-600', 'bg-gray-500');

        try {
            const formData = new FormData();
            formData.append('file_id', fileId);
            if (force) formData.append('force', '1');

            const response = await fetch('/blockchain/upload-existing', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'include'
            });

            const result = await response.json();

            if (result.success) {
                this.showSuccessMessage(result);
                modal.remove();
                this.refreshFileList();
                this.loadStorageInfo();
            } else {
                this.showErrorMessage(result.message || 'Upload failed');
                uploadBtn.disabled = false;
                uploadBtn.textContent = 'Upload to Blockchain';
                uploadBtn.className = uploadBtn.className.replace('bg-gray-500', 'bg-blue-600');
            }
        } catch (error) {
            console.error('Upload failed:', error);
            this.showErrorMessage('Network error during upload');
            uploadBtn.disabled = false;
            uploadBtn.textContent = 'Upload to Blockchain';
            uploadBtn.className = uploadBtn.className.replace('bg-gray-500', 'bg-blue-600');
        }
    }

    showSuccessMessage(result) {
        const message = document.createElement('div');
        message.className = 'fixed top-4 right-4 z-50 bg-green-600 text-white p-4 rounded-lg shadow-lg';
        message.innerHTML = `
            <div class="flex items-center">
                <span class="mr-2">✅</span>
                <div>
                    <div class="font-medium">Upload Successful!</div>
                    <div class="text-sm opacity-90">IPFS Hash: ${result.ipfs_hash}</div>
                </div>
            </div>
        `;
        document.body.appendChild(message);

        setTimeout(() => message.remove(), 5000);
    }

    showErrorMessage(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'fixed top-4 right-4 z-50 bg-red-600 text-white p-4 rounded-lg shadow-lg';
        errorDiv.innerHTML = `
            <div class="flex items-center">
                <span class="mr-2">❌</span>
                <div>
                    <div class="font-medium">Upload Failed</div>
                    <div class="text-sm opacity-90">${message}</div>
                </div>
            </div>
        `;
        document.body.appendChild(errorDiv);

        setTimeout(() => errorDiv.remove(), 5000);
    }

    refreshFileList() {
        // Trigger file list refresh
        if (window.fileManager && window.fileManager.loadFiles) {
            window.fileManager.loadFiles();
        }
    }

    showBulkUploadModal() {
        // TODO: Implement bulk upload functionality
        alert('Bulk upload feature coming soon!');
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.blockchainUpload = new BlockchainUpload();
});
