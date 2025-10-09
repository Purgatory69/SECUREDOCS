/**
 * AI File Categorization Management
 * Provides tamper-resistant loading screen and progress tracking
 */

class AICategorization {
    constructor() {
        this.isActive = false;
        this.statusInterval = null;
        this.overlay = null;
        this.apiBase = '/api';
        this.authHeaderName = 'Authorization';
        this.skipAuthUntil = Date.now() + 365 * 24 * 60 * 60 * 1000; // Force public-only polling
        this.completedHandled = false; // prevents duplicate completion notifications
        
        // Only initialize on user dashboard page with delay to improve page load
        if (this.shouldInitialize()) {
            setTimeout(() => {
                console.log('Initializing AI categorization (delayed)...');
                this.init();
            }, 30000); // 10 second delay
        }
    }

    /**
     * Check if we should initialize on this page
     */
    shouldInitialize() {
        // Only run on user dashboard page
        const currentPath = window.location.pathname;
        const isDashboard = currentPath === '/user-dashboard' || 
                           currentPath === '/dashboard' ||
                           currentPath.includes('user-dashboard');
        
        // Also check for dashboard-specific elements
        const hasDashboardElements = document.querySelector('[data-page="user-dashboard"]') ||
                                   document.querySelector('.user-dashboard') ||
                                   document.querySelector('#file-manager');
        
        return isDashboard || hasDashboardElements;
    }

    init() {
        this.createOverlay();
        this.attachEventListeners();
        this.checkInitialStatus();
        this.startAmbientWatcher();
    }

    /**
     * Build headers, conditionally adding Authorization if a token exists.
     */
    buildAuthHeaders(extra = {}) {
        const headers = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...extra,
        };
        try {
            const token = localStorage.getItem('auth_token');
            if (token && typeof token === 'string' && token.trim().length > 0) {
                headers[this.authHeaderName] = `Bearer ${token}`;
            }
        } catch (_) {
            // ignore storage errors
        }
        return headers;
    }

    /**
     * Create tamper-resistant overlay
     */
    createOverlay() {
        // Create overlay with high z-index and body lock
        this.overlay = document.createElement('div');
        this.overlay.id = 'ai-categorization-overlay';
        this.overlay.innerHTML = `
            <div class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[10000] flex items-center justify-center">
                <div class="bg-[#1F2235] border border-[#4A4D6A] rounded-xl p-8 max-w-md w-full mx-4 shadow-2xl">
                    <div class="text-center">
                        <div class="w-16 h-16 mx-auto mb-4 relative">
                            <div class="w-16 h-16 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <svg class="w-8 h-8 text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-xl font-semibold text-white mb-2">AI Categorizing Your Files</h3>
                        <p class="text-gray-300 mb-4" id="categorization-message">Starting AI categorization...</p>
                        
                        <div class="w-full bg-[#2A2D47] rounded-full h-2 mb-4">
                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                                 id="categorization-progress" style="width: 0%"></div>
                        </div>
                        
                        <div class="text-sm text-gray-400" id="categorization-details">
                            Progress: <span id="categorization-percentage">0</span>%
                        </div>
                        
                        <div class="mt-6 text-xs text-gray-500 text-center">
                            <p>🔒 This process cannot be interrupted for data integrity</p>
                            <p class="mt-1">Please keep this window open</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Hide initially
        this.overlay.style.display = 'none';
        document.body.appendChild(this.overlay);
    }

    /**
     * Show tamper-resistant overlay
     */
    showOverlay() {
        if (!this.overlay) {
            console.error('❌ Cannot show overlay - overlay element not found');
            return;
        }
        
        this.overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        document.body.style.pointerEvents = 'none';
        this.overlay.style.pointerEvents = 'auto';
        
        // Prevent common bypass attempts
        this.preventTampering();
        
        this.isActive = true;
        this.completedHandled = false; // reset when a new run starts
        this.startStatusPolling();
    }

    /**
     * Hide overlay
     */
    hideOverlay() {
        if (!this.overlay) return;
        
        this.overlay.style.display = 'none';
        document.body.style.overflow = '';
        document.body.style.pointerEvents = '';
        
        this.isActive = false;
        this.stopStatusPolling();
    }

    /**
     * Prevent tampering attempts
     */
    preventTampering() {
        // Disable right-click context menu during categorization
        const preventContext = (e) => {
            if (this.isActive) {
                e.preventDefault();
                return false;
            }
        };
        
        // Disable F12, Ctrl+Shift+I, etc.
        const preventDevTools = (e) => {
            if (this.isActive) {
                if (e.key === 'F12' || 
                    (e.ctrlKey && e.shiftKey && e.key === 'I') ||
                    (e.ctrlKey && e.shiftKey && e.key === 'C') ||
                    (e.ctrlKey && e.key === 'u')) {
                    e.preventDefault();
                    return false;
                }
            }
        };
        
        document.addEventListener('contextmenu', preventContext);
        document.addEventListener('keydown', preventDevTools);
        
        // Store references to remove later
        this.tamperPreventions = { preventContext, preventDevTools };
        
        // Re-check status every second to detect backend state
        this.tamperCheckInterval = setInterval(() => {
            if (this.isActive) {
                this.checkServerStatus();
            }
        }, 1000);
    }

    /**
     * Check server status to prevent client-side bypass
     */
    async checkServerStatus() {
        try {
            let response;
            // If we're in cooldown, skip private endpoint
            if (Date.now() < this.skipAuthUntil) {
                const userId = window.userId || localStorage.getItem('user_id');
                const publicUrl = userId ? `${this.apiBase}/ai/categorization-status-public?user_id=${userId}` : `${this.apiBase}/ai/categorization-status-public`;
                response = await fetch(publicUrl, {
                    method: 'GET',
                    headers: this.buildAuthHeaders(),
                    credentials: 'include'
                });
            } else {
                response = await fetch(`${this.apiBase}/ai/categorization-status`, {
                method: 'GET',
                headers: this.buildAuthHeaders(),
                credentials: 'include'
                });
            }
            
            if (response.status === 401) {
                // Fallback to public status if not authenticated
                response = await fetch(`${this.apiBase}/ai/categorization-status-public`, {
                    method: 'GET',
                    headers: this.buildAuthHeaders(),
                    credentials: 'include'
                });
            }

            if (response.ok) {
                const data = await response.json();
                // minimal debug to verify frontend sees state
                if (window && window.console) {
                    console.debug('AI status (server check):', data?.status);
                }
                
                // If server says no categorization is running but overlay is active
                if (data.status.status === 'idle' && this.isActive) {
                    this.hideOverlay();
                    this.removeTamperPrevention();
                }
                // If server says categorization is running but overlay is hidden
                else if (data.status.status === 'in_progress' && !this.isActive) {
                    this.showOverlay();
                    this.updateProgress(data.status);
                }
            }
        } catch (error) {
            console.error('Status check failed:', error);
        }
    }

    /**
     * Ambient watcher that runs even when overlay is hidden.
     * If server reports in_progress, it will show the overlay.
     */
    startAmbientWatcher() {
        if (this.ambientInterval) return;
        this.ambientInterval = setInterval(async () => {
            try {
                if (this.isActive) return; // active polling handles it
                
                // Always use public endpoint since we set skipAuthUntil to far future
                const userId = window.userId || localStorage.getItem('user_id');
                const publicUrl = userId ? `${this.apiBase}/ai/categorization-status-public?user_id=${userId}` : `${this.apiBase}/ai/categorization-status-public`;
                const resp = await fetch(publicUrl, {
                    method: 'GET',
                    headers: this.buildAuthHeaders(),
                    credentials: 'include'
                });
                if (!resp.ok) return;
                const data = await resp.json();
                const status = data.status;
                if (status?.status === 'in_progress' && !this.isActive) {
                    this.showOverlay();
                    this.updateProgress(status);
                } else if (status?.status === 'completed' && this.isActive && !this.completedHandled) {
                    this.completedHandled = true;
                    setTimeout(() => {
                        this.hideOverlay();
                        this.removeTamperPrevention();
                        this.showCompletionNotification();
                        if (window.fileManager && window.fileManager.refreshFiles) {
                            window.fileManager.refreshFiles();
                        }
                    }, 2000);
                }
            } catch (e) {
                console.error('❌ Ambient watcher error:', e);
            }
        }, 3000); // every 3s for faster detection
    }

    /**
     * Remove tamper prevention when categorization is done
     */
    removeTamperPrevention() {
        if (this.tamperPreventions) {
            document.removeEventListener('contextmenu', this.tamperPreventions.preventContext);
            document.removeEventListener('keydown', this.tamperPreventions.preventDevTools);
        }
        
        if (this.tamperCheckInterval) {
            clearInterval(this.tamperCheckInterval);
        }
    }

    /**
     * Start AI categorization via chat interface
     */
    async startCategorization() {
        try {
            // Check if N8N chat is available
            if (!window.n8nChat) {
                this.showChatUnavailableError();
                return;
            }

            // Send initial message to AI for categorization
            const message = "Help me organize my files. Please analyze my file structure and ask me questions about how I'd like them categorized.";
            
            // Trigger chat with categorization request
            window.n8nChat.sendMessage(message);
            
            // Show notification that chat is starting
            if (window.notifications) {
                window.notifications.show({
                    title: 'AI File Organizer Started',
                    message: 'The AI will analyze your files and ask clarifying questions',
                    type: 'info'
                });
            }

            // Focus on chat interface
            this.focusOnChat();
            
        } catch (error) {
            console.error('Failed to start AI categorization:', error);
            alert('Failed to start AI categorization. Please ensure the chat interface is available.');
        }
    }

    /**
     * Focus on the N8N chat interface
     */
    focusOnChat() {
        // Scroll to chat or make it visible
        const chatContainer = document.getElementById('n8n-chat-container');
        if (chatContainer) {
            chatContainer.scrollIntoView({ behavior: 'smooth' });
            
            // Add highlighting effect
            chatContainer.style.boxShadow = '0 0 20px rgba(59, 130, 246, 0.5)';
            setTimeout(() => {
                chatContainer.style.boxShadow = '';
            }, 3000);
        }
    }

    /**
     * Show error when chat is not available
     */
    showChatUnavailableError() {
        if (window.notifications) {
            window.notifications.show({
                title: 'Chat Unavailable',
                message: 'The AI chat interface is not loaded. Please refresh the page.',
                type: 'error'
            });
        } else {
            alert('The AI chat interface is not available. Please refresh the page and try again.');
        }
    }

    /**
     * Start polling for status updates
     */
    startStatusPolling() {
        this.stopStatusPolling(); // Clear any existing interval
        
        this.statusInterval = setInterval(async () => {
            await this.checkStatus();
        }, 2000); // Check every 2 seconds
    }

    /**
     * Stop status polling
     */
    stopStatusPolling() {
        if (this.statusInterval) {
            clearInterval(this.statusInterval);
            this.statusInterval = null;
        }
    }

    /**
     * Check categorization status
     */
    async checkStatus() {
        try {
            let response;
            // Respect cooldown to avoid spamming private endpoint with 401s
            if (Date.now() < this.skipAuthUntil) {
                const userId = window.userId || localStorage.getItem('user_id');
                const publicUrl = userId 
                    ? `${this.apiBase}/ai/categorization-status-public?user_id=${userId}`
                    : `${this.apiBase}/ai/categorization-status-public`;
                response = await fetch(publicUrl, {
                    method: 'GET',
                    headers: this.buildAuthHeaders(),
                    credentials: 'include'
                });
            } else {
                response = await fetch(`${this.apiBase}/ai/categorization-status`, {
                    method: 'GET',
                    headers: this.buildAuthHeaders(),
                    credentials: 'include'
                });
            }
            
            if (response.status === 401) {
                this.skipAuthUntil = Date.now() + 5 * 60 * 1000; // enter cooldown
                // Fallback to public endpoint with user context so we get per-user status
                const userId = window.userId || localStorage.getItem('user_id');
                const publicUrl = userId 
                    ? `${this.apiBase}/ai/categorization-status-public?user_id=${userId}`
                    : `${this.apiBase}/ai/categorization-status-public`;
                response = await fetch(publicUrl, {
                    method: 'GET',
                    headers: this.buildAuthHeaders(),
                    credentials: 'include'
                });
            }
            if (response.ok) {
                const data = await response.json();
                this.updateProgress(data.status);
                
                // Handle completion
                if (data.status.status === 'completed' && !this.completedHandled) {
                    this.completedHandled = true;
                    setTimeout(() => {
                        this.hideOverlay();
                        this.removeTamperPrevention();
                        this.showCompletionNotification();
                        
                        // Refresh file list
                        if (window.fileManager && window.fileManager.refreshFiles) {
                            window.fileManager.refreshFiles();
                        }
                    }, 2000);
                } else if (data.status.status === 'failed') {
                    setTimeout(() => {
                        this.hideOverlay();
                        this.removeTamperPrevention();
                        this.showErrorNotification(data.status.message);
                    }, 1000);
                }
            }
        } catch (error) {
            console.error('Status check failed:', error);
        }
    }

    /**
     * Update progress display
     */
    updateProgress(status) {
        if (!this.overlay) return;
        
        const messageEl = this.overlay.querySelector('#categorization-message');
        const progressEl = this.overlay.querySelector('#categorization-progress');
        const percentageEl = this.overlay.querySelector('#categorization-percentage');
        
        if (messageEl) messageEl.textContent = status.message || 'Processing...';
        if (progressEl) progressEl.style.width = `${status.progress || 0}%`;
        if (percentageEl) percentageEl.textContent = status.progress || 0;
        if (status?.status === 'in_progress') {
            this.completedHandled = false; // allow completion handling for this run
        }
    }

    /**
     * Check initial status on page load
     */
    async checkInitialStatus() {
        try {
            // console.log('🔄 Checking initial AI categorization status');
            // Always use public endpoint since we force public-only polling
            const userId = window.userId || localStorage.getItem('user_id');
            const publicUrl = userId ? 
                `${this.apiBase}/ai/categorization-status-public?user_id=${userId}` : 
                `${this.apiBase}/ai/categorization-status-public`;
            const response = await fetch(publicUrl, {
                method: 'GET',
                headers: this.buildAuthHeaders(),
                credentials: 'include'
            });
            // console.log('🌐 Public status response:', response.status, response.statusText);
            
            if (response.ok) {
                const data = await response.json();
                // console.log('📊 Initial status data:', data);
                
                // If categorization is in progress, show overlay
                if (data.status.status === 'in_progress') {
                    console.log('🚀 Showing overlay from initial check');
                    this.showOverlay();
                    this.updateProgress(data.status);
                } else {
                    console.log('✅ No active categorization found');
                }
            }
        } catch (error) {
            console.error('❌ Initial status check failed:', error);
        }
    }

    /**
     * Show completion notification
     */
    showCompletionNotification() {
        if (window.notifications) {
            window.notifications.show({
                title: 'AI Categorization Complete',
                message: 'Your files have been successfully organized',
                type: 'success'
            });
        } else {
            alert('AI categorization completed successfully!');
        }
    }

    /**
     * Show error notification
     */
    showErrorNotification(message) {
        if (window.notifications) {
            window.notifications.show({
                title: 'Categorization Failed',
                message: message || 'An error occurred during categorization',
                type: 'error'
            });
        } else {
            alert(`Categorization failed: ${message || 'Unknown error'}`);
        }
    }

    /**
     * Attach event listeners
     */
    attachEventListeners() {
        // Listen for categorization trigger button
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="ai-categorize"]') || 
                e.target.closest('[data-action="ai-categorize"]')) {
                e.preventDefault();
                this.startCategorization();
            }
        });
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.aiCategorization = new AICategorization();
    });
} else {
    window.aiCategorization = new AICategorization();
}

export default AICategorization;
