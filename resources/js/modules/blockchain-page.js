// Blockchain page functionality - separate from modal-based blockchain.js
import { escapeHtml, formatFileSize, showNotification } from './ui.js';
import { renderFiles } from './file-folder.js';

export async function loadBlockchainItems() {
    const itemsContainer = document.getElementById('filesContainer');
    if (!itemsContainer) {
        console.error('Items container not found');
        return;
    }

    try {
        // Mark container as blockchain view for context-sensitive actions
        itemsContainer.dataset.view = 'blockchain';
        itemsContainer.innerHTML = '<div class="flex justify-center items-center py-8"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div></div>';

        // Fetch Arweave URLs from arweave_urls table
        const response = await fetch('/arweave/urls');
        if (!response.ok) throw new Error('Failed to fetch Arweave files');
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to load Arweave files');
        }

        const items = data.urls || [];
        displayArweaveItems(items);
        
    } catch (error) {
        console.error('Error loading Arweave files:', error);
        itemsContainer.innerHTML = `<div class="text-center py-8 text-red-600">Failed to load Arweave files: ${error.message}</div>`;
    }
}

function displayArweaveItems(items) {
    const itemsContainer = document.getElementById('filesContainer');
    
    if (!items || items.length === 0) {
        itemsContainer.innerHTML = `
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <div class="text-6xl mb-4">🚀</div>
                <h3 class="text-lg font-medium text-white mb-2">No files on Arweave yet</h3>
                <p class="text-gray-400 mb-4">Upload files to Arweave for permanent decentralized storage</p>
                <button id="openClientArweaveBtn" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
                    🚀 Upload to Arweave
                </button>
            </div>
        `;
        return;
    }

    // Transform arweave_urls table data to display format
    let html = '';
    
    items.forEach(item => {
        const fileIcon = '📄'; // Simple file icon
        const uploadDate = new Date(item.created_at).toLocaleDateString();
        
        html += `
            <div class="file-card bg-[#24243B] border-2 border-[#3C3F58] rounded-lg p-4 hover:bg-[#3C3F58] hover:border-[#55597C] transition-colors cursor-pointer">
                <div class="flex flex-col h-full">
                    <!-- File Icon and Name -->
                    <div class="flex items-center mb-3">
                        <div class="text-3xl mr-3">${fileIcon}</div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-medium text-white truncate" title="${item.file_name || 'Untitled'}">
                                ${item.file_name || 'Untitled'}
                            </h3>
                            <p class="text-xs text-gray-400">Uploaded: ${uploadDate}</p>
                        </div>
                    </div>
                    
                    <!-- Arweave Status -->
                    <div class="flex items-center mb-2">
                        <div class="flex items-center">
                            <span class="w-2 h-2 bg-green-400 rounded-full mr-2"></span>
                            <span class="text-xs text-green-400">Permanently Stored</span>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="mt-auto flex gap-2">
                        <button onclick="window.open('${item.url}', '_blank')" 
                                class="flex-1 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded transition-colors">
                            🌐 View
                        </button>
                        <button onclick="copyArweaveUrl('${item.url}')" 
                                class="px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white text-xs rounded transition-colors">
                            📋 Copy URL
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    itemsContainer.innerHTML = html;
}

// Helper function to get file icon based on mime type
function getFileIcon(mimeType) {
    if (!mimeType) return '📄';
    
    if (mimeType.startsWith('image/')) return '🖼️';
    if (mimeType.startsWith('video/')) return '🎥';
    if (mimeType.startsWith('audio/')) return '🎵';
    if (mimeType === 'application/pdf') return '📕';
    if (mimeType.includes('text/') || mimeType.includes('document')) return '📝';
    if (mimeType.includes('zip') || mimeType.includes('archive')) return '🗜️';
    
    return '📄';
}

// Copy Arweave URL to clipboard
function copyArweaveUrl(url) {
    navigator.clipboard.writeText(url).then(() => {
        showNotification('Arweave URL copied to clipboard!', 'success');
    }).catch(err => {
        console.error('Failed to copy URL:', err);
        showNotification('Failed to copy URL', 'error');
    });
}

// Blockchain action functions
export async function downloadFromBlockchain(fileId) {
    try {
        const response = await fetch(`/files/${fileId}/download-from-blockchain`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'Content-Type': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('File downloaded to Supabase storage successfully', 'success');
        } else {
            showNotification(data.message || 'Failed to download file', 'error');
        }
    } catch (error) {
        console.error('Error downloading from blockchain:', error);
        showNotification('Failed to download file', 'error');
    }
}

export async function removeFromBlockchain(fileId) {
    if (!confirm('Remove this file from blockchain storage? This action cannot be undone.')) {
        return;
    }
    
    try {
        const response = await fetch(`/files/${fileId}/remove-from-blockchain`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'Content-Type': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('File removed from blockchain storage', 'success');
            loadBlockchainItems(); // Refresh the list
        } else {
            showNotification(data.message || 'Failed to remove file', 'error');
        }
    } catch (error) {
        console.error('Error removing from blockchain:', error);
        showNotification('Failed to remove file', 'error');
    }
}

export async function enablePermanentStorage(fileId) {
    // Check if user is premium to customize the message
    let message = 'Enable permanent storage for this file? This will make the file undeletable.';
    
    // For non-premium users, mention potential fees
    try {
        const response = await fetch('/files/processing-options');
        const data = await response.json();
        if (data.success && !data.user_is_premium) {
            message = 'Enable permanent storage for this file? This will make the file undeletable and may incur additional fees.';
        }
    } catch (e) {
        // Fallback to generic message if we can't check premium status
        console.warn('Could not check premium status:', e);
    }
    
    if (!confirm(message)) {
        return;
    }
    
    try {
        const response = await fetch(`/files/${fileId}/enable-permanent-storage`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'Content-Type': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Permanent storage enabled successfully', 'success');
            loadBlockchainItems(); // Refresh the list
        } else {
            showNotification(data.message || 'Failed to enable permanent storage', 'error');
        }
    } catch (error) {
        console.error('Error enabling permanent storage:', error);
        showNotification('Failed to enable permanent storage', 'error');
    }
}

// Expose functions globally for onclick handlers
window.downloadFromBlockchain = downloadFromBlockchain;
window.removeFromBlockchain = removeFromBlockchain;
window.enablePermanentStorage = enablePermanentStorage;
window.copyArweaveUrl = copyArweaveUrl;
