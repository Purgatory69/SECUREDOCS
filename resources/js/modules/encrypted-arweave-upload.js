/**
 * Enhanced Arweave Upload with Encryption Support
 * Integrates with existing Bundlr wallet widget and adds file encryption
 */

import FileEncryptionService from './file-encryption.js';

class EncryptedArweaveUpload {
    constructor() {
        this.encryptionService = new FileEncryptionService();
        this.currentFile = null;
        this.isEncrypted = false;
        this.encryptionPassword = null;
    }

    /**
     * Initialize the enhanced upload system
     */
    init() {
        if (!this.encryptionService.isSupported()) {
            console.error('❌ Web Crypto API not supported in this browser');
            return false;
        }

        this.setupEventListeners();
        console.log('✅ Encrypted Arweave upload system initialized');
        return true;
    }

    /**
     * Set up event listeners for encryption controls
     */
    setupEventListeners() {
        // Privacy toggle
        const privacyToggle = document.getElementById('filePrivacyToggle');
        if (privacyToggle) {
            privacyToggle.addEventListener('change', (e) => {
                this.handlePrivacyToggle(e.target.checked);
            });
        }

        // Password input
        const passwordInput = document.getElementById('encryptionPassword');
        if (passwordInput) {
            passwordInput.addEventListener('input', (e) => {
                this.encryptionPassword = e.target.value;
                this.validatePassword();
            });
        }

        // Generate password button
        const generateBtn = document.getElementById('generatePasswordBtn');
        if (generateBtn) {
            generateBtn.addEventListener('click', () => {
                this.generateSecurePassword();
            });
        }

        // Enhanced upload button
        const uploadBtn = document.getElementById('uploadToArweaveBtn');
        if (uploadBtn) {
            // Remove existing listeners and add our enhanced one
            uploadBtn.replaceWith(uploadBtn.cloneNode(true));
            const newUploadBtn = document.getElementById('uploadToArweaveBtn');
            newUploadBtn.addEventListener('click', () => {
                this.handleEnhancedUpload();
            });
        }
    }

    /**
     * Handle privacy toggle change
     */
    handlePrivacyToggle(isPrivate) {
        this.isEncrypted = isPrivate;
        const passwordSection = document.getElementById('passwordSection');
        
        if (passwordSection) {
            if (isPrivate) {
                passwordSection.classList.remove('hidden');
                passwordSection.classList.add('animate-fadeIn');
            } else {
                passwordSection.classList.add('hidden');
                passwordSection.classList.remove('animate-fadeIn');
                this.encryptionPassword = null;
            }
        }

        this.updateUploadButtonText();
    }

    /**
     * Generate a secure password
     */
    generateSecurePassword() {
        const password = this.encryptionService.generateSecurePassword(16);
        const passwordInput = document.getElementById('encryptionPassword');
        
        if (passwordInput) {
            passwordInput.value = password;
            this.encryptionPassword = password;
            this.validatePassword();
        }

        // Show password in a modal for user to copy
        this.showGeneratedPassword(password);
    }

    /**
     * Show generated password in a modal
     */
    showGeneratedPassword(password) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[10000]';
        modal.innerHTML = `
            <div class="bg-white rounded-lg p-6 max-w-md mx-4">
                <h3 class="text-lg font-semibold mb-4">🔐 Generated Password</h3>
                <p class="text-sm text-gray-600 mb-4">
                    Please copy and save this password securely. You'll need it to access your encrypted file.
                </p>
                <div class="bg-gray-100 p-3 rounded border font-mono text-sm break-all mb-4">
                    ${password}
                </div>
                <div class="flex space-x-3">
                    <button id="copyPasswordBtn" class="flex-1 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        📋 Copy Password
                    </button>
                    <button id="closePasswordModal" class="flex-1 bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                        Close
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Copy button
        modal.querySelector('#copyPasswordBtn').addEventListener('click', () => {
            navigator.clipboard.writeText(password).then(() => {
                alert('Password copied to clipboard!');
            });
        });

        // Close button
        modal.querySelector('#closePasswordModal').addEventListener('click', () => {
            document.body.removeChild(modal);
        });
    }

    /**
     * Validate password strength
     */
    validatePassword() {
        const password = this.encryptionPassword;
        const strengthIndicator = document.getElementById('passwordStrength');
        
        if (!password || !strengthIndicator) return;

        let strength = 0;
        let feedback = '';

        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;

        if (strength < 3) {
            feedback = '🔴 Weak';
            strengthIndicator.className = 'text-red-500 text-sm';
        } else if (strength < 5) {
            feedback = '🟡 Medium';
            strengthIndicator.className = 'text-yellow-500 text-sm';
        } else {
            feedback = '🟢 Strong';
            strengthIndicator.className = 'text-green-500 text-sm';
        }

        strengthIndicator.textContent = feedback;
    }

    /**
     * Update upload button text based on encryption status
     */
    updateUploadButtonText() {
        const uploadBtn = document.getElementById('uploadToArweaveBtn');
        if (uploadBtn) {
            const text = this.isEncrypted ? '🔐 Upload Encrypted to Arweave' : '🚀 Upload to Arweave';
            uploadBtn.innerHTML = text;
        }
    }

    /**
     * Enhanced upload handler with encryption support
     */
    async handleEnhancedUpload() {
        if (!this.currentFile) {
            this.showError('Please select a file first');
            return;
        }

        // Check if wallet widget is initialized
        if (!window.isWalletReady || !window.isWalletReady()) {
            this.showError('Please initialize Bundlr wallet first using the "B" button in the navigation');
            return;
        }

        // Validate encryption settings
        if (this.isEncrypted && (!this.encryptionPassword || this.encryptionPassword.length < 8)) {
            this.showError('Please enter a password of at least 8 characters for encryption');
            return;
        }

        try {
            console.log('🚀 Starting enhanced Arweave upload...', this.currentFile.name);
            
            // Show loading
            this.showLoading('uploadToArweaveBtn', this.isEncrypted ? 'Encrypting & Uploading...' : 'Uploading...');
            
            // Check balance
            const balance = window.getCurrentBalance();
            const uploadCost = 0.005; // Estimated cost in MATIC
            
            if (balance < uploadCost) {
                throw new Error(`Insufficient Bundlr balance (${balance.toFixed(6)} MATIC). Please fund your account using the wallet widget.`);
            }

            let fileToUpload = this.currentFile;
            let encryptionMetadata = null;

            // Encrypt file if privacy is enabled
            if (this.isEncrypted) {
                console.log('🔐 Encrypting file before upload...');
                
                const fileBuffer = await this.encryptionService.readFileAsBuffer(this.currentFile);
                const encryptionResult = await this.encryptionService.encryptFile(fileBuffer, this.encryptionPassword);
                
                // Create new file object with encrypted data
                const encryptedBlob = new Blob([encryptionResult.encryptedData], { 
                    type: 'application/octet-stream' 
                });
                
                fileToUpload = new File([encryptedBlob], this.currentFile.name + '.encrypted', {
                    type: 'application/octet-stream'
                });

                // Prepare encryption metadata for database
                encryptionMetadata = {
                    is_encrypted: true,
                    encryption_method: 'AES-256-GCM',
                    salt: encryptionResult.salt,
                    iv: encryptionResult.iv,
                    password_hash: await this.encryptionService.hashPassword(this.encryptionPassword, encryptionResult.salt)
                };

                console.log('✅ File encrypted successfully');
            }
            
            // Upload using wallet widget
            const result = await window.uploadFileWithBundlr(fileToUpload);
            
            if (!result.success) {
                throw new Error(result.error || 'Upload failed');
            }
            
            // Prepare upload data for saving with cost tracking
            const uploadData = {
                arweave_url: result.url,
                file_name: this.currentFile.name, // Original filename
                file_size_bytes: this.currentFile.size,
                mime_type: this.currentFile.type,
                upload_cost_matic: result.cost_matic || uploadCost,
                upload_cost_usd: result.cost_usd,
                transaction_id: result.transactionId || result.id,
                bundlr_receipt: result.receipt,
                gateway_urls: result.gatewayUrls,
                ...encryptionMetadata
            };
            
            // Save to database
            await this.saveEnhancedUploadRecord(uploadData);
            
            // Show success
            this.showUploadSuccess(result.url, result.remainingBalance, this.isEncrypted);
            
            console.log('✅ Enhanced upload completed successfully');
            console.log('🔗 Arweave URL:', result.url);
            console.log('🔐 Encrypted:', this.isEncrypted);
            
        } catch (error) {
            console.error('❌ Enhanced upload failed:', error);
            this.showError(error.message);
        } finally {
            // Reset button
            this.hideLoading('uploadToArweaveBtn', this.isEncrypted ? '🔐 Upload Encrypted to Arweave' : '🚀 Upload to Arweave');
        }
    }

    /**
     * Save enhanced upload record with encryption metadata
     */
    async saveEnhancedUploadRecord(uploadData) {
        try {
            const response = await fetch('/arweave-client/save-upload', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(uploadData)
            });

            if (response.ok) {
                console.log('✅ Enhanced upload record saved to database');
            } else {
                console.warn('⚠️ Failed to save upload record, but upload succeeded');
            }
            
        } catch (error) {
            console.warn('⚠️ Failed to save upload record, but upload succeeded:', error.message);
        }
    }

    /**
     * Set current file for upload
     */
    setCurrentFile(file) {
        this.currentFile = file;
    }

    /**
     * Show error message
     */
    showError(message) {
        // Use existing error display or create one
        const errorDiv = document.getElementById('uploadError') || this.createErrorDiv();
        errorDiv.textContent = message;
        errorDiv.classList.remove('hidden');
        
        setTimeout(() => {
            errorDiv.classList.add('hidden');
        }, 5000);
    }

    /**
     * Create error div if it doesn't exist
     */
    createErrorDiv() {
        const errorDiv = document.createElement('div');
        errorDiv.id = 'uploadError';
        errorDiv.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 hidden';
        
        const modal = document.getElementById('clientArweaveModal');
        if (modal) {
            modal.querySelector('.modal-content')?.prepend(errorDiv);
        }
        
        return errorDiv;
    }

    /**
     * Show loading state
     */
    showLoading(buttonId, text) {
        const button = document.getElementById(buttonId);
        if (button) {
            button.disabled = true;
            button.innerHTML = `<span class="animate-spin">⏳</span> ${text}`;
        }
    }

    /**
     * Hide loading state
     */
    hideLoading(buttonId, originalText) {
        const button = document.getElementById(buttonId);
        if (button) {
            button.disabled = false;
            button.innerHTML = originalText;
        }
    }

    /**
     * Show upload success with encryption info
     */
    showUploadSuccess(url, remainingBalance, isEncrypted) {
        // Use existing success display or enhance it
        const successMessage = isEncrypted 
            ? `🔐 File encrypted and uploaded successfully! Only you can access it with your password.`
            : `🚀 File uploaded successfully! It's publicly accessible.`;
            
        console.log(successMessage);
        console.log('🔗 URL:', url);
        console.log('💰 Remaining balance:', remainingBalance, 'MATIC');
        
        // Extract transaction ID from URL and start auto status checker
        const transactionId = url.split('/').pop();
        if (transactionId && window.startAutoStatusCheck) {
            console.log('🔍 Starting auto status checker for transaction:', transactionId);
            window.startAutoStatusCheck(transactionId);
        }
        
        // Show success notification
        if (window.showNotification) {
            window.showNotification(successMessage, 'success');
        } else {
            alert(successMessage + `\n\nURL: ${url}`);
        }
    }
}

// Export for use
window.EncryptedArweaveUpload = EncryptedArweaveUpload;
export default EncryptedArweaveUpload;
