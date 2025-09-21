// Storage Usage Management Module
// Handles storage usage display and premium upgrade prompts

class StorageUsageManager {
    constructor() {
        this.storageData = null;
        this.updateInterval = null;
        this.init();
    }

    init() {
        this.loadStorageUsage();
        this.attachEventListeners();
        // Update storage usage every 30 seconds
        this.updateInterval = setInterval(() => {
            this.loadStorageUsage();
        }, 30000);
    }

    async loadStorageUsage() {
        try {
            const response = await fetch('/files/storage-usage', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            this.storageData = data;
            this.updateDisplay(data);

        } catch (error) {
            console.error('Failed to load storage usage:', error);
            this.showError();
        }
    }

    updateDisplay(data) {
        const progressBar = document.getElementById('storageProgressBar');
        const usageText = document.getElementById('storageUsageText');
        const upgradePrompt = document.getElementById('upgradePrompt');

        if (!progressBar || !usageText) {
            console.warn('Storage usage elements not found');
            return;
        }

        // Extract storage data from response
        const storage = data.storage || data;
        const totalUsed = storage.total_used || 0;
        const totalLimit = storage.total_limit || storage.storage_limit || 0;
        const usagePercentage = storage.usage_percentage || 0;
        const isPremium = storage.is_premium || false;

        // Use formatted values if available, otherwise calculate
        let usageText_content;
        if (storage.formatted && storage.formatted.used && storage.formatted.limit) {
            usageText_content = `${storage.formatted.used} of ${storage.formatted.limit} used`;
        } else {
            // Fallback calculation
            const usedMB = Math.round(totalUsed / (1024 * 1024) * 100) / 100;
            const limitMB = Math.round(totalLimit / (1024 * 1024));
            const usedGB = Math.round(totalUsed / (1024 * 1024 * 1024) * 100) / 100;
            const limitGB = Math.round(totalLimit / (1024 * 1024 * 1024) * 100) / 100;

            if (limitGB >= 1) {
                usageText_content = `${usedGB} GB of ${limitGB} GB used`;
            } else {
                usageText_content = `${usedMB} MB of ${limitMB} MB used`;
            }
        }

        // Update progress bar
        const percentage = Math.min(100, Math.max(0, usagePercentage));
        progressBar.style.width = `${percentage}%`;

        // Color coding based on usage
        if (percentage >= 90) {
            progressBar.style.backgroundColor = '#ef4444'; // red
        } else if (percentage >= 80) {
            progressBar.style.backgroundColor = '#f59e0b'; // yellow
        } else {
            progressBar.style.backgroundColor = '#3C3F58'; // default blue
        }

        // Update usage text
        usageText.textContent = usageText_content;

        // Show upgrade prompt if near or over limit
        if (upgradePrompt) {
            if (percentage >= 80 && !isPremium) {
                upgradePrompt.classList.remove('hidden');
                
                // Update prompt message based on usage
                const promptText = upgradePrompt.querySelector('span');
                if (percentage >= 100) {
                    promptText.textContent = 'Storage limit exceeded!';
                } else if (percentage >= 90) {
                    promptText.textContent = 'Storage almost full!';
                } else {
                    promptText.textContent = 'Storage limit approaching!';
                }
            } else {
                upgradePrompt.classList.add('hidden');
            }
        }
    }

    showError() {
        const usageText = document.getElementById('storageUsageText');
        if (usageText) {
            usageText.textContent = 'Unable to load storage usage';
            usageText.classList.add('text-red-400');
        }
    }

    attachEventListeners() {
        // Upgrade button click handler
        const upgradeBtn = document.getElementById('upgradeBtn');
        if (upgradeBtn) {
            upgradeBtn.addEventListener('click', () => {
                this.showUpgradeModal();
            });
        }

        // Listen for file upload/delete events to refresh usage
        document.addEventListener('fileUploaded', () => {
            setTimeout(() => this.loadStorageUsage(), 1000);
        });

        document.addEventListener('fileDeleted', () => {
            setTimeout(() => this.loadStorageUsage(), 1000);
        });
    }

    showUpgradeModal() {
        // Create upgrade modal
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black/50 backdrop-blur-sm z-[10000] flex items-center justify-center p-4';
        modal.innerHTML = `
            <div class="bg-[#1F2235] border border-[#4A4D6A] rounded-xl max-w-md w-full shadow-2xl">
                <div class="p-6">
                    <div class="text-center">
                        <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-white mb-2">Upgrade to Premium</h3>
                        <p class="text-gray-300 mb-6">Get more storage and unlock premium features</p>
                        
                        <div class="bg-[#2A2D47] rounded-lg p-4 mb-6">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div class="text-center">
                                    <div class="text-gray-400">Current Plan</div>
                                    <div class="text-white font-semibold">${this.storageData?.is_premium ? '100 GB' : '5 GB'}</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-gray-400">Premium Plan</div>
                                    <div class="text-purple-400 font-semibold">100 GB</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-left mb-6 space-y-2">
                            <div class="flex items-center text-sm text-gray-300">
                                <svg class="w-4 h-4 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                100 GB storage space
                            </div>
                            <div class="flex items-center text-sm text-gray-300">
                                <svg class="w-4 h-4 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Blockchain storage
                            </div>
                            <div class="flex items-center text-sm text-gray-300">
                                <svg class="w-4 h-4 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                AI-powered search
                            </div>
                            <div class="flex items-center text-sm text-gray-300">
                                <svg class="w-4 h-4 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Priority support
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex gap-3">
                        <button type="button" class="flex-1 px-4 py-2 text-sm font-medium text-gray-300 hover:text-white border border-[#4A4D6A] rounded-lg hover:bg-[#2A2D47] transition-colors" id="close-upgrade-modal">
                            Maybe Later
                        </button>
                        <button type="button" class="flex-1 px-4 py-2 text-sm font-medium bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg hover:from-purple-700 hover:to-pink-700 transition-colors" id="upgrade-now-btn">
                            Upgrade Now
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Event listeners for modal
        const closeBtn = modal.querySelector('#close-upgrade-modal');
        const upgradeNowBtn = modal.querySelector('#upgrade-now-btn');

        const closeModal = () => {
            modal.remove();
        };

        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        upgradeNowBtn.addEventListener('click', () => {
            // Redirect to premium upgrade page or show payment form
            window.location.href = '/premium/upgrade'; // Adjust URL as needed
        });
    }

    // Check storage before upload
    checkStorageBeforeUpload(fileSize) {
        if (!this.storageData) {
            return { allowed: true, message: '' };
        }

        const storage = this.storageData.storage || this.storageData;
        const totalUsed = storage.total_used || 0;
        const totalLimit = storage.total_limit || storage.storage_limit || 0;
        const isPremium = storage.is_premium || false;

        const availableBytes = totalLimit - totalUsed;
        
        if (fileSize > availableBytes) {
            const availableMB = Math.round(availableBytes / (1024 * 1024) * 100) / 100;
            const fileMB = Math.round(fileSize / (1024 * 1024) * 100) / 100;
            
            return {
                allowed: false,
                message: `Not enough storage space. Available: ${availableMB} MB, Required: ${fileMB} MB`,
                showUpgrade: !isPremium
            };
        }

        return { allowed: true, message: '' };
    }

    destroy() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
        }
    }
}

// Initialize storage usage manager when DOM is ready
let storageManager = null;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        storageManager = new StorageUsageManager();
        window.storageManager = storageManager;
    });
} else {
    storageManager = new StorageUsageManager();
    window.storageManager = storageManager;
}

export default StorageUsageManager;
