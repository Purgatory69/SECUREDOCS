/**
 * Website Refresh Handler
 * Handles real-time website refresh notifications from the API
 */

class WebsiteRefreshHandler {
    constructor() {
        this.refreshEndpoint = '/api/refresh/event';
        this.sseEndpoint = '/api/refresh/sse';
        this.eventSource = null;
        this.refreshInterval = null;
        this.isEnabled = true;
        
        this.init();
    }
    
    /**
     * Initialize the refresh handler
     */
    init() {
        console.log('Website Refresh Handler initialized');
        
        // Set up SSE for real-time refresh events
        this.setupSSE();
        
        // Set up polling as fallback
        this.setupPolling();
        
        // Listen for manual refresh triggers
        this.setupManualRefresh();
    }
    
    /**
     * Set up Server-Sent Events for real-time refresh
     */
    setupSSE() {
        if (!window.EventSource) {
            console.warn('Server-Sent Events not supported, falling back to polling');
            return;
        }
        
        try {
            this.eventSource = new EventSource(this.sseEndpoint);
            
            this.eventSource.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    console.log('Received refresh event:', data);
                    this.handleRefreshEvent(data);
                } catch (error) {
                    console.error('Error parsing SSE data:', error);
                }
            };
            
            this.eventSource.onerror = (error) => {
                console.error('SSE error:', error);
                // Fall back to polling on SSE error
                this.startPolling();
            };
            
            this.eventSource.onopen = () => {
                console.log('SSE connection established');
                // Stop polling when SSE is active
                this.stopPolling();
            };
            
        } catch (error) {
            console.error('Failed to setup SSE:', error);
            this.startPolling();
        }
    }
    
    /**
     * Set up polling as fallback for refresh events
     */
    setupPolling() {
        // Start polling immediately
        this.startPolling();
    }
    
    /**
     * Start polling for refresh events
     */
    startPolling() {
        if (this.refreshInterval) return;
        
        this.refreshInterval = setInterval(() => {
            this.checkForRefresh();
        }, 3000); // Check every 3 seconds
    }
    
    /**
     * Stop polling
     */
    stopPolling() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }
    
    /**
     * Check for refresh events via API
     */
    async checkForRefresh() {
        if (!this.isEnabled) return;
        
        try {
            const response = await fetch(this.refreshEndpoint);
            const data = await response.json();
            
            if (data.data && data.data.refresh_id) {
                this.handleRefreshEvent(data.data);
            }
            
        } catch (error) {
            console.error('Error checking for refresh:', error);
        }
    }
    
    /**
     * Handle refresh event
     */
    handleRefreshEvent(eventData) {
        console.log('Website refresh triggered:', eventData);
        
        // Show refresh notification
        this.showRefreshNotification(eventData);
        
        // Perform the refresh
        this.refreshPage();
    }
    
    /**
     * Show refresh notification
     */
    showRefreshNotification(eventData) {
        // Create a toast notification
        const toast = document.createElement('div');
        toast.className = 'website-refresh-toast';
        toast.innerHTML = `
            <div class="refresh-notification">
                <span>🔄 Website refresh triggered by ${eventData.source}</span>
                <button onclick="this.parentElement.remove()">×</button>
            </div>
        `;
        
        // Add styles
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #4CAF50;
            color: white;
            padding: 12px 20px;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 10000;
            font-family: Arial, sans-serif;
            font-size: 14px;
        `;
        
        document.body.appendChild(toast);
        
        // Remove after 3 seconds
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 3000);
    }
    
    /**
     * Refresh the page
     */
    refreshPage() {
        // Soft refresh - reload without losing state
        if (window.location.reload) {
            window.location.reload();
        }
    }
    
    /**
     * Set up manual refresh trigger
     */
    setupManualRefresh() {
        // Listen for custom events
        document.addEventListener('website-refresh', (event) => {
            console.log('Manual refresh triggered:', event.detail);
            this.handleRefreshEvent(event.detail);
        });
        
        // Add global refresh function
        window.triggerWebsiteRefresh = (source = 'manual', data = {}) => {
            this.handleRefreshEvent({
                source: source,
                data: data,
                timestamp: new Date().toISOString()
            });
        };
    }
    
    /**
     * Enable/disable refresh handling
     */
    setEnabled(enabled) {
        this.isEnabled = enabled;
        
        if (enabled) {
            this.startPolling();
        } else {
            this.stopPolling();
        }
    }
    
    /**
     * Destroy the refresh handler
     */
    destroy() {
        this.stopPolling();
        
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }
    }
}

// Initialize the refresh handler when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.websiteRefreshHandler = new WebsiteRefreshHandler();
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = WebsiteRefreshHandler;
}
