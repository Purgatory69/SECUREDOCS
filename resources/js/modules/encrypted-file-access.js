/**
 * Encrypted File Access System
 * Handles password verification and decryption of Arweave files
 */

import FileEncryptionService from './file-encryption.js';

class EncryptedFileAccess {
    constructor() {
        this.encryptionService = new FileEncryptionService();
        this.currentFile = null;
        this.decryptionData = null;
    }

    /**
     * Initialize the access system
     */
    init() {
        if (!this.encryptionService.isSupported()) {
            console.error('❌ Web Crypto API not supported');
            return false;
        }

        this.setupEventListeners();
        console.log('✅ Encrypted file access system initialized');
        return true;
    }

    /**
     * Set up event listeners
     */
    setupEventListeners() {
        // Listen for encrypted file access requests
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-encrypted-file]')) {
                e.preventDefault();
                const fileId = e.target.dataset.encryptedFile;
                const fileName = e.target.dataset.fileName || 'Unknown File';
                this.requestFileAccess(fileId, fileName);
            }
        });
    }

    /**
     * Request access to an encrypted file
     */
    async requestFileAccess(fileId, fileName) {
        try {
            console.log('🔐 Requesting access to encrypted file:', fileName);
            
            // Show password modal
            const password = await this.showPasswordModal(fileName);
            
            if (!password) {
                console.log('❌ Access cancelled by user');
                return;
            }

            // Verify password with backend
            const verificationResult = await this.verifyPassword(fileId, password);
            
            if (!verificationResult.success) {
                throw new Error('Invalid password');
            }

            // Download and decrypt file
            await this.downloadAndDecryptFile(verificationResult.decryption_data, password);
            
        } catch (error) {
            console.error('❌ File access failed:', error);
            this.showError(error.message);
        }
    }

    /**
     * Show password input modal
     */
    showPasswordModal(fileName) {
        return new Promise((resolve) => {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-lg p-6 max-w-md mx-4 w-full">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                            🔐
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold">Protected File</h3>
                            <p class="text-sm text-gray-600">${fileName}</p>
                        </div>
                    </div>
                    
                    <p class="text-sm text-gray-700 mb-4">
                        This file is encrypted. Please enter the password to access it.
                    </p>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Password
                        </label>
                        <input 
                            type="password" 
                            id="fileAccessPassword" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Enter file password"
                            autocomplete="off"
                        >
                        <div id="passwordError" class="text-red-500 text-sm mt-1 hidden"></div>
                    </div>
                    
                    <div class="flex space-x-3">
                        <button id="accessFileBtn" class="flex-1 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 disabled:opacity-50">
                            🔓 Access File
                        </button>
                        <button id="cancelAccessBtn" class="flex-1 bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                            Cancel
                        </button>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);

            const passwordInput = modal.querySelector('#fileAccessPassword');
            const accessBtn = modal.querySelector('#accessFileBtn');
            const cancelBtn = modal.querySelector('#cancelAccessBtn');
            const errorDiv = modal.querySelector('#passwordError');

            // Focus password input
            passwordInput.focus();

            // Handle enter key
            passwordInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    accessBtn.click();
                }
            });

            // Access button
            accessBtn.addEventListener('click', () => {
                const password = passwordInput.value.trim();
                
                if (!password) {
                    this.showPasswordError(errorDiv, 'Please enter a password');
                    return;
                }

                document.body.removeChild(modal);
                resolve(password);
            });

            // Cancel button
            cancelBtn.addEventListener('click', () => {
                document.body.removeChild(modal);
                resolve(null);
            });

            // Close on backdrop click
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    document.body.removeChild(modal);
                    resolve(null);
                }
            });
        });
    }

    /**
     * Show password error
     */
    showPasswordError(errorDiv, message) {
        errorDiv.textContent = message;
        errorDiv.classList.remove('hidden');
        
        setTimeout(() => {
            errorDiv.classList.add('hidden');
        }, 3000);
    }

    /**
     * Verify password with backend
     */
    async verifyPassword(fileId, password) {
        try {
            const response = await fetch(`/arweave-client/files/${fileId}/verify-access`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ password })
            });

            const result = await response.json();
            
            if (!response.ok) {
                throw new Error(result.message || 'Password verification failed');
            }

            return result;

        } catch (error) {
            console.error('❌ Password verification failed:', error);
            throw error;
        }
    }

    /**
     * Download and decrypt file
     */
    async downloadAndDecryptFile(decryptionData, password) {
        try {
            console.log('📥 Downloading encrypted file from Arweave...');
            
            // Show loading indicator
            this.showLoadingModal('Downloading and decrypting file...');

            // Download encrypted file from Arweave
            const response = await fetch(decryptionData.url);
            
            if (!response.ok) {
                throw new Error('Failed to download file from Arweave');
            }

            const encryptedData = new Uint8Array(await response.arrayBuffer());
            
            console.log('🔓 Decrypting file...');

            // Decrypt the file
            const decryptedData = await this.encryptionService.decryptFile(
                encryptedData,
                password,
                decryptionData.salt,
                decryptionData.iv
            );

            // Create download blob
            const blob = this.encryptionService.createDownloadBlob(decryptedData);
            
            // Trigger download
            this.triggerDownload(blob, decryptionData.file_name);
            
            console.log('✅ File decrypted and downloaded successfully');
            
            // Hide loading modal
            this.hideLoadingModal();
            
            // Show success message
            this.showSuccess('File decrypted and downloaded successfully!');

        } catch (error) {
            console.error('❌ Download/decryption failed:', error);
            this.hideLoadingModal();
            throw error;
        }
    }

    /**
     * Show loading modal
     */
    showLoadingModal(message) {
        const modal = document.createElement('div');
        modal.id = 'decryptionLoadingModal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white rounded-lg p-6 max-w-sm mx-4">
                <div class="text-center">
                    <div class="animate-spin text-4xl mb-4">⏳</div>
                    <p class="text-gray-700">${message}</p>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
    }

    /**
     * Hide loading modal
     */
    hideLoadingModal() {
        const modal = document.getElementById('decryptionLoadingModal');
        if (modal) {
            document.body.removeChild(modal);
        }
    }

    /**
     * Trigger file download
     */
    triggerDownload(blob, fileName) {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = fileName;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    /**
     * Show success message
     */
    showSuccess(message) {
        // Create temporary success notification
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
        notification.innerHTML = `
            <div class="flex items-center">
                <span class="mr-2">✅</span>
                <span>${message}</span>
            </div>
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            document.body.removeChild(notification);
        }, 3000);
    }

    /**
     * Show error message
     */
    showError(message) {
        // Create temporary error notification
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
        notification.innerHTML = `
            <div class="flex items-center">
                <span class="mr-2">❌</span>
                <span>${message}</span>
            </div>
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            document.body.removeChild(notification);
        }, 5000);
    }
}

// Export for use
window.EncryptedFileAccess = EncryptedFileAccess;
export default EncryptedFileAccess;
