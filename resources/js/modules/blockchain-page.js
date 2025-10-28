// Enhanced Blockchain page functionality with list/grid views and cost tracking
import { escapeHtml, formatFileSize, showNotification } from './ui.js';
import { renderFiles, hideTrashBanner } from './file-folder.js';

let currentViewMode = 'grid'; // 'grid' or 'list'
let blockchainFiles = [];
let blockchainStats = {};

/**
 * Clean up blockchain-specific UI elements
 */
export function cleanupBlockchainUI() {
    const headerContainer = document.querySelector('.files-header');
    if (headerContainer) {
        headerContainer.remove();
    }
    
    // Reset container classes that might have been set by blockchain view
    const itemsContainer = document.getElementById('filesContainer');
    if (itemsContainer) {
        itemsContainer.className = '';
        itemsContainer.removeAttribute('data-view');
    }
}

export async function loadBlockchainItems() {
    const itemsContainer = document.getElementById('filesContainer');
    if (!itemsContainer) {
        console.error('Items container not found');
        return;
    }

    try {
        // Mark container as blockchain view for context-sensitive actions
        itemsContainer.dataset.view = 'blockchain';
        // Hide trash banner when not in trash view
        hideTrashBanner();
        itemsContainer.innerHTML = '<div class="flex justify-center items-center py-8"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div></div>';

        // Fetch Arweave URLs and stats
        const [filesResponse, statsResponse] = await Promise.all([
            fetch('/arweave/urls'),
            fetch('/arweave-client/stats')
        ]);

        if (!filesResponse.ok) throw new Error('Failed to fetch Arweave files');
        if (!statsResponse.ok) throw new Error('Failed to fetch stats');
        
        const filesData = await filesResponse.json();
        const statsData = await statsResponse.json();
        
        if (!filesData.success) {
            throw new Error(filesData.message || 'Failed to load Arweave files');
        }

        blockchainFiles = filesData.urls || [];
        blockchainStats = statsData.stats || {};
        
        // Initialize blockchain header with stats and view controls
        initializeBlockchainHeader();
        displayArweaveItems(blockchainFiles);
        
    } catch (error) {
        console.error('Error loading Arweave files:', error);
        itemsContainer.innerHTML = `<div class="text-center py-8 text-red-600">Failed to load Arweave files: ${error.message}</div>`;
    }
}

/**
 * Initialize blockchain header with stats and view controls
 */
function initializeBlockchainHeader() {
    const headerContainer = document.querySelector('.files-header') || createBlockchainHeader();
    
    headerContainer.innerHTML = `
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
            <!-- Stats Section -->
            <div class="flex flex-wrap gap-4">
                <div class="bg-[#1F2235] rounded-lg px-4 py-2 border border-[#3C3F58]">
                    <div class="text-sm text-gray-400">Total Files</div>
                    <div class="text-lg font-semibold text-white">${blockchainStats.total_files || 0}</div>
                </div>
                <div class="bg-[#1F2235] rounded-lg px-4 py-2 border border-[#3C3F58]">
                    <div class="text-sm text-gray-400">Total Cost</div>
                    <div class="text-lg font-semibold text-yellow-400">${blockchainStats.total_cost_matic || 0} MATIC</div>
                </div>
                <div class="bg-[#1F2235] rounded-lg px-4 py-2 border border-[#3C3F58]">
                    <div class="text-sm text-gray-400">Total Size</div>
                    <div class="text-lg font-semibold text-blue-400">${formatFileSize(blockchainStats.total_size_bytes || 0)}</div>
                </div>
                <div class="bg-[#1F2235] rounded-lg px-4 py-2 border border-[#3C3F58]">
                    <div class="text-sm text-gray-400">Encrypted</div>
                    <div class="text-lg font-semibold text-green-400">${blockchainStats.encrypted_files || 0}</div>
                </div>
            </div>
            
            <!-- View Controls -->
            <div class="flex items-center gap-3">
                <div class="flex bg-[#1F2235] rounded-lg border border-[#3C3F58] overflow-hidden">
                    <button id="gridViewBtn" 
                            class="px-3 py-2 text-sm transition-colors relative group ${currentViewMode === 'grid' ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white'}"
                            title="Grid View">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-black text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">
                            Grid View
                        </div>
                    </button>
                    <button id="listViewBtn" 
                            class="px-3 py-2 text-sm transition-colors relative group ${currentViewMode === 'list' ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white'}"
                            title="List View">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 8a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 12a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 16a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"/>
                        </svg>
                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-black text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">
                            List View
                        </div>
                    </button>
                </div>
                
                <button id="refreshBlockchainBtn" 
                        class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors relative group"
                        title="Refresh Files">
                    🔄 Refresh
                    <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-black text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">
                        Refresh Files
                    </div>
                </button>
                

            </div>
        </div>
    `;
    
    // Add event listeners
    setupHeaderEventListeners();
}

/**
 * Create blockchain header container if it doesn't exist
 */
function createBlockchainHeader() {
    const container = document.getElementById('filesContainer');
    const header = document.createElement('div');
    header.className = 'files-header';
    container.parentNode.insertBefore(header, container);
    return header;
}

/**
 * Setup event listeners for header controls
 */
function setupHeaderEventListeners() {
    // View mode buttons
    document.getElementById('gridViewBtn')?.addEventListener('click', () => {
        currentViewMode = 'grid';
        displayArweaveItems(blockchainFiles);
        updateViewButtons();
    });
    
    document.getElementById('listViewBtn')?.addEventListener('click', () => {
        currentViewMode = 'list';
        displayArweaveItems(blockchainFiles);
        updateViewButtons();
    });
    
    // Refresh button
    document.getElementById('refreshBlockchainBtn')?.addEventListener('click', () => {
        loadBlockchainItems();
    });
    
    // Upload button
    document.getElementById('openClientArweaveBtn')?.addEventListener('click', () => {
        if (window.openClientArweaveModal) {
            window.openClientArweaveModal();
        }
    });
}

/**
 * Update view button states
 */
function updateViewButtons() {
    const gridBtn = document.getElementById('gridViewBtn');
    const listBtn = document.getElementById('listViewBtn');
    
    if (gridBtn && listBtn) {
        if (currentViewMode === 'grid') {
            gridBtn.className = 'px-3 py-2 text-sm transition-colors bg-blue-600 text-white';
            listBtn.className = 'px-3 py-2 text-sm transition-colors text-gray-400 hover:text-white';
        } else {
            gridBtn.className = 'px-3 py-2 text-sm transition-colors text-gray-400 hover:text-white';
            listBtn.className = 'px-3 py-2 text-sm transition-colors bg-blue-600 text-white';
        }
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
                <button onclick="window.openClientArweaveModal && window.openClientArweaveModal()" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
                    🚀 Upload to Arweave
                </button>
            </div>
        `;
        return;
    }

    // Set container class based on view mode
    if (currentViewMode === 'grid') {
        itemsContainer.className = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4';
        itemsContainer.innerHTML = items.map(item => createGridItemHTML(item)).join('');
    } else {
        itemsContainer.className = 'space-y-2';
        itemsContainer.innerHTML = items.map(item => createListItemHTML(item)).join('');
    }
}

/**
 * Create grid view HTML for an item
 */
function createGridItemHTML(item) {
    const fileIcon = getFileIcon(item.mime_type);
    const uploadDate = new Date(item.created_at).toLocaleDateString();
    const fileSize = item.file_size_bytes ? formatFileSize(item.file_size_bytes) : 'Unknown';
    const cost = item.upload_cost_matic ? `${parseFloat(item.upload_cost_matic).toFixed(6)} MATIC` : 'Free';
    const isEncrypted = item.is_encrypted;
    
    return `
        <div class="file-card bg-[#24243B] border-2 border-[#3C3F58] rounded-lg p-4 hover:bg-[#3C3F58] hover:border-[#55597C] transition-colors cursor-pointer">
            <div class="flex flex-col h-full">
                <!-- File Icon and Name -->
                <div class="flex items-center mb-3">
                    <div class="text-3xl mr-3 relative">
                        ${fileIcon}
                        ${isEncrypted ? '<span class="absolute -top-1 -right-1 text-xs">🔒</span>' : ''}
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-medium text-white truncate" title="${escapeHtml(item.file_name || 'Untitled')}">
                            ${escapeHtml(item.file_name || 'Untitled')}
                        </h3>
                        <p class="text-xs text-gray-400">${fileSize} • ${uploadDate}</p>
                    </div>
                </div>
                
                <!-- Status and Cost -->
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center">
                        <span class="w-2 h-2 bg-green-400 rounded-full mr-2"></span>
                        <span class="text-xs text-green-400">Permanent</span>
                    </div>
                    <div class="text-xs text-yellow-400">${cost}</div>
                </div>
                
                <!-- Action Buttons -->
                <div class="mt-auto flex gap-2">
                    ${isEncrypted ? 
                        `<button onclick="accessEncryptedFile(${item.id})" 
                                 class="flex-1 px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs rounded transition-colors relative group"
                                 title="Access Encrypted File">
                            🔓 Access
                            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-black text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">
                                Access Encrypted File
                            </div>
                         </button>` :
                        `<button onclick="window.open('${item.url}', '_blank')" 
                                 class="flex-1 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded transition-colors relative group"
                                 title="View File">
                            🌐 View
                            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-black text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">
                                View File
                            </div>
                         </button>`
                    }
                    <button onclick="showFileDetails(${item.id})" 
                            class="px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white text-xs rounded transition-colors relative group"
                            title="File Details">
                        ℹ️
                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-black text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">
                            File Details
                        </div>
                    </button>
                    <button onclick="copyArweaveUrl('${item.url}')" 
                            class="px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white text-xs rounded transition-colors relative group"
                            title="Copy URL">
                        📋
                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-black text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">
                            Copy URL
                        </div>
                    </button>
                </div>
            </div>
        </div>
    `;
}

/**
 * Create list view HTML for an item
 */
function createListItemHTML(item) {
    const fileIcon = getFileIcon(item.mime_type);
    const uploadDate = new Date(item.created_at).toLocaleDateString();
    const fileSize = item.file_size_bytes ? formatFileSize(item.file_size_bytes) : 'Unknown';
    const cost = item.upload_cost_matic ? `${parseFloat(item.upload_cost_matic).toFixed(6)} MATIC` : 'Free';
    const isEncrypted = item.is_encrypted;
    
    return `
        <div class="bg-[#24243B] border border-[#3C3F58] rounded-lg p-4 hover:bg-[#3C3F58] hover:border-[#55597C] transition-colors">
            <div class="flex items-center justify-between">
                <!-- File Info -->
                <div class="flex items-center flex-1 min-w-0">
                    <div class="text-2xl mr-4 relative">
                        ${fileIcon}
                        ${isEncrypted ? '<span class="absolute -top-1 -right-1 text-xs">🔒</span>' : ''}
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-medium text-white truncate" title="${escapeHtml(item.file_name || 'Untitled')}">
                            ${escapeHtml(item.file_name || 'Untitled')}
                        </h3>
                        <div class="flex items-center gap-4 text-xs text-gray-400 mt-1">
                            <span>${fileSize}</span>
                            <span>${uploadDate}</span>
                            <span class="flex items-center">
                                <span class="w-2 h-2 bg-green-400 rounded-full mr-1"></span>
                                Permanent
                            </span>
                            ${isEncrypted ? '<span class="text-purple-400">🔒 Encrypted</span>' : '<span class="text-green-400">🌐 Public</span>'}
                        </div>
                    </div>
                </div>
                
                <!-- Cost -->
                <div class="text-sm text-yellow-400 mx-4">
                    ${cost}
                </div>
                
                <!-- Actions -->
                <div class="flex items-center gap-2">
                    ${isEncrypted ? 
                        `<button onclick="accessEncryptedFile(${item.id})" 
                                 class="px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs rounded transition-colors relative group"
                                 title="Access Encrypted File">
                            🔓 Access
                            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-black text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">
                                Access Encrypted File
                            </div>
                         </button>` :
                        `<button onclick="window.open('${item.url}', '_blank')" 
                                 class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded transition-colors relative group"
                                 title="View File">
                            🌐 View
                            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-black text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">
                                View File
                            </div>
                         </button>`
                    }
                    <button onclick="showFileDetails(${item.id})" 
                            class="px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white text-xs rounded transition-colors relative group"
                            title="File Details">
                        ℹ️ Details
                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-black text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">
                            File Details
                        </div>
                    </button>
                    <button onclick="copyArweaveUrl('${item.url}')" 
                            class="px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white text-xs rounded transition-colors relative group"
                            title="Copy URL">
                        📋 Copy
                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-black text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">
                            Copy URL
                        </div>
                    </button>
                </div>
            </div>
        </div>
    `;
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

/**
 * Show detailed file information modal
 */
async function showFileDetails(fileId) {
    try {
        const response = await fetch(`/arweave-client/files/${fileId}/details`);
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to load file details');
        }
        
        const file = data.file;
        const modal = createFileDetailsModal(file);
        document.body.appendChild(modal);
        
    } catch (error) {
        console.error('Error loading file details:', error);
        showNotification('Failed to load file details: ' + error.message, 'error');
    }
}

/**
 * Create file details modal
 */
function createFileDetailsModal(file) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
    
    const uploadDate = new Date(file.created_at).toLocaleString();
    const lastAccessed = file.last_accessed_at ? new Date(file.last_accessed_at).toLocaleString() : 'Never';
    const fileSize = file.file_size_bytes ? formatFileSize(file.file_size_bytes) : 'Unknown';
    const cost = file.upload_cost_matic ? `${parseFloat(file.upload_cost_matic).toFixed(6)} MATIC` : 'Free';
    const costUSD = file.upload_cost_usd ? `$${parseFloat(file.upload_cost_usd).toFixed(2)}` : 'N/A';
    
    modal.innerHTML = `
        <div class="bg-[#0D0E2F] rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-[#3C3F58]">
                <h3 class="text-xl font-semibold text-white">📄 File Details</h3>
                <button onclick="this.closest('.fixed').remove()" class="text-2xl leading-none hover:text-gray-300 text-white">&times;</button>
            </div>
            
            <!-- Content -->
            <div class="p-6 max-h-[calc(90vh-140px)] overflow-y-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Basic Info -->
                    <div class="space-y-4">
                        <h4 class="text-lg font-medium text-white mb-3">📋 Basic Information</h4>
                        
                        <div class="bg-[#1F2235] rounded-lg p-4 space-y-3">
                            <div>
                                <label class="text-sm text-gray-400">File Name</label>
                                <p class="text-white font-medium">${escapeHtml(file.file_name)}</p>
                            </div>
                            
                            <div>
                                <label class="text-sm text-gray-400">File Size</label>
                                <p class="text-white">${fileSize}</p>
                            </div>
                            
                            <div>
                                <label class="text-sm text-gray-400">MIME Type</label>
                                <p class="text-white">${file.mime_type || 'Unknown'}</p>
                            </div>
                            
                            <div>
                                <label class="text-sm text-gray-400">Privacy</label>
                                <p class="text-white">
                                    ${file.is_encrypted ? 
                                        '<span class="text-purple-400">🔒 Encrypted</span>' : 
                                        '<span class="text-green-400">🌐 Public</span>'
                                    }
                                </p>
                            </div>
                            
                            ${file.is_encrypted ? `
                                <div>
                                    <label class="text-sm text-gray-400">Encryption Method</label>
                                    <p class="text-white">${file.encryption_method || 'AES-256-GCM'}</p>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                    
                    <!-- Arweave Info -->
                    <div class="space-y-4">
                        <h4 class="text-lg font-medium text-white mb-3">🚀 Arweave Information</h4>
                        
                        <div class="bg-[#1F2235] rounded-lg p-4 space-y-3">
                            <div>
                                <label class="text-sm text-gray-400">Upload Cost</label>
                                <p class="text-yellow-400 font-medium">${cost}</p>
                                ${file.upload_cost_usd ? `<p class="text-sm text-gray-400">${costUSD} USD</p>` : ''}
                            </div>
                            
                            <div>
                                <label class="text-sm text-gray-400">Transaction ID</label>
                                <p class="text-white text-sm font-mono break-all">${file.transaction_id || 'N/A'}</p>
                            </div>
                            
                            <div>
                                <label class="text-sm text-gray-400">Upload Date</label>
                                <p class="text-white">${uploadDate}</p>
                            </div>
                            
                            <div>
                                <label class="text-sm text-gray-400">Access Count</label>
                                <p class="text-white">${file.access_count || 0} times</p>
                            </div>
                            
                            <div>
                                <label class="text-sm text-gray-400">Last Accessed</label>
                                <p class="text-white">${lastAccessed}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Arweave URL -->
                <div class="mt-6">
                    <h4 class="text-lg font-medium text-white mb-3">🔗 Arweave URL</h4>
                    <div class="bg-[#1F2235] rounded-lg p-4">
                        <div class="flex items-center gap-2">
                            <input type="text" value="${file.url}" readonly 
                                   class="flex-1 bg-[#0D0E2F] border border-[#3C3F58] rounded px-3 py-2 text-white text-sm font-mono">
                            <button onclick="copyArweaveUrl('${file.url}')" 
                                    class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded transition-colors">
                                📋 Copy
                            </button>
                            ${!file.is_encrypted ? `
                                <button onclick="window.open('${file.url}', '_blank')" 
                                        class="px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm rounded transition-colors">
                                    🌐 Open
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
                
                ${file.gateway_urls ? `
                    <!-- Alternative Gateways -->
                    <div class="mt-6">
                        <h4 class="text-lg font-medium text-white mb-3">🌐 Alternative Gateways</h4>
                        <div class="bg-[#1F2235] rounded-lg p-4 space-y-2">
                            ${Object.entries(file.gateway_urls).map(([name, url]) => `
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-400 capitalize">${name.replace('_', ' ')}</span>
                                    <button onclick="window.open('${url}', '_blank')" 
                                            class="px-2 py-1 bg-gray-600 hover:bg-gray-700 text-white text-xs rounded transition-colors">
                                        Open
                                    </button>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
            </div>
            
            <!-- Footer -->
            <div class="flex justify-end gap-3 p-6 border-t border-[#3C3F58]">
                ${file.is_encrypted ? `
                    <button onclick="accessEncryptedFile(${file.id}); this.closest('.fixed').remove();" 
                            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded transition-colors">
                        🔓 Access File
                    </button>
                ` : ''}
                <button onclick="this.closest('.fixed').remove()" 
                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded transition-colors">
                    Close
                </button>
            </div>
        </div>
    `;
    
    return modal;
}

/**
 * Access encrypted file
 */
function accessEncryptedFile(fileId) {
    // Use the existing encrypted file access system
    if (window.EncryptedFileAccess) {
        const fileAccessManager = new window.EncryptedFileAccess();
        fileAccessManager.init();
        fileAccessManager.requestFileAccess(fileId, 'Encrypted Arweave File');
    } else {
        showNotification('Encrypted file access system not available', 'error');
    }
}

// Expose functions globally for onclick handlers
window.downloadFromBlockchain = downloadFromBlockchain;
window.removeFromBlockchain = removeFromBlockchain;
window.enablePermanentStorage = enablePermanentStorage;
window.copyArweaveUrl = copyArweaveUrl;
window.showFileDetails = showFileDetails;
window.accessEncryptedFile = accessEncryptedFile;
window.cleanupBlockchainUI = cleanupBlockchainUI;
