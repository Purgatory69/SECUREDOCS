// Storage usage indicator module
import { showNotification } from './ui.js';

let storageData = null;
let updateInterval = null;

/**
 * Initialize storage indicator
 */
export function initStorageIndicator() {
    createStorageIndicatorHTML();
    loadStorageUsage();
    
    // Update every 30 seconds
    updateInterval = setInterval(loadStorageUsage, 30000);
}

/**
 * Create the storage indicator HTML element
 */
function createStorageIndicatorHTML() {
    // Check if indicator already exists
    if (document.getElementById('storage-indicator')) {
        return;
    }

    const indicator = document.createElement('div');
    indicator.id = 'storage-indicator';
    indicator.className = 'fixed bottom-4 left-4 bg-[#1F2235] border border-[#4A4D6A] rounded-lg p-3 shadow-lg z-40 min-w-[280px]';
    indicator.innerHTML = `
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-200">Storage Usage</span>
            <button id="storage-indicator-close" class="text-gray-400 hover:text-gray-200 text-xs">×</button>
        </div>
        <div id="storage-progress-container">
            <div class="flex justify-between text-xs text-gray-400 mb-1">
                <span id="storage-used">Loading...</span>
                <span id="storage-limit">Loading...</span>
            </div>
            <div class="w-full bg-gray-700 rounded-full h-2 mb-2">
                <div id="storage-progress-bar" class="bg-blue-500 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
            <div class="text-xs text-gray-400">
                <div class="flex justify-between">
                    <span>📁 Supabase: <span id="storage-supabase">-</span></span>
                    <span>🔗 Blockchain: <span id="storage-blockchain">-</span></span>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(indicator);

    // Add close button functionality
    document.getElementById('storage-indicator-close').addEventListener('click', () => {
        indicator.style.display = 'none';
        localStorage.setItem('storage-indicator-hidden', 'true');
    });

    // Check if user previously hid the indicator
    if (localStorage.getItem('storage-indicator-hidden') === 'true') {
        indicator.style.display = 'none';
    }
}

/**
 * Load storage usage from API
 */
async function loadStorageUsage() {
    try {
        const response = await fetch('/files/storage-usage');
        if (!response.ok) {
            throw new Error('Failed to fetch storage usage');
        }

        const data = await response.json();
        if (data.success) {
            storageData = data.storage;
            updateStorageDisplay();
        }
    } catch (error) {
        console.error('Error loading storage usage:', error);
        // Don't show notification for storage errors to avoid spam
    }
}

/**
 * Update the storage display with current data
 */
function updateStorageDisplay() {
    if (!storageData) return;

    const usedElement = document.getElementById('storage-used');
    const limitElement = document.getElementById('storage-limit');
    const progressBar = document.getElementById('storage-progress-bar');
    const supabaseElement = document.getElementById('storage-supabase');
    const blockchainElement = document.getElementById('storage-blockchain');

    if (usedElement) usedElement.textContent = storageData.formatted.used;
    if (limitElement) limitElement.textContent = storageData.formatted.limit;
    if (supabaseElement) supabaseElement.textContent = storageData.formatted.supabase;
    if (blockchainElement) blockchainElement.textContent = storageData.formatted.blockchain;

    if (progressBar) {
        const percentage = Math.min(storageData.usage_percentage, 100);
        progressBar.style.width = `${percentage}%`;
        
        // Change color based on usage
        if (percentage >= 90) {
            progressBar.className = 'bg-red-500 h-2 rounded-full transition-all duration-300';
        } else if (percentage >= 75) {
            progressBar.className = 'bg-yellow-500 h-2 rounded-full transition-all duration-300';
        } else {
            progressBar.className = 'bg-blue-500 h-2 rounded-full transition-all duration-300';
        }

        // Show warning if near limit
        if (percentage >= 90 && !localStorage.getItem('storage-warning-shown')) {
            showNotification(
                `Storage almost full! Using ${storageData.formatted.used} of ${storageData.formatted.limit}`,
                'warning'
            );
            localStorage.setItem('storage-warning-shown', 'true');
        }
    }
}

/**
 * Show storage indicator (if hidden)
 */
export function showStorageIndicator() {
    const indicator = document.getElementById('storage-indicator');
    if (indicator) {
        indicator.style.display = 'block';
        localStorage.removeItem('storage-indicator-hidden');
    }
}

/**
 * Hide storage indicator
 */
export function hideStorageIndicator() {
    const indicator = document.getElementById('storage-indicator');
    if (indicator) {
        indicator.style.display = 'none';
        localStorage.setItem('storage-indicator-hidden', 'true');
    }
}

/**
 * Refresh storage usage (call after file operations)
 */
export function refreshStorageUsage() {
    loadStorageUsage();
}

/**
 * Get current storage data
 */
export function getStorageData() {
    return storageData;
}

/**
 * Cleanup function
 */
export function destroyStorageIndicator() {
    if (updateInterval) {
        clearInterval(updateInterval);
        updateInterval = null;
    }
    
    const indicator = document.getElementById('storage-indicator');
    if (indicator) {
        indicator.remove();
    }
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initStorageIndicator);
} else {
    initStorageIndicator();
}
