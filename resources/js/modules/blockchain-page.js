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

        const response = await fetch('/blockchain/files');
        if (!response.ok) throw new Error('Failed to fetch blockchain files');
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to load blockchain files');
        }

        const items = data.files || [];
        displayBlockchainItems(items);
        
    } catch (error) {
        console.error('Error loading blockchain files:', error);
        itemsContainer.innerHTML = `<div class="text-center py-8 text-red-600">Failed to load blockchain files: ${error.message}</div>`;
    }
}

function displayBlockchainItems(items) {
    const itemsContainer = document.getElementById('filesContainer');
    
    if (!items || items.length === 0) {
        itemsContainer.innerHTML = `
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <div class="text-6xl mb-4">🔗</div>
                <h3 class="text-lg font-medium text-white mb-2">No files on blockchain yet</h3>
                <p class="text-gray-400 mb-4">Upload files to IPFS for decentralized storage</p>
                <button onclick="document.getElementById('uploadFileOption').click()" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
                    Upload to Blockchain
                </button>
            </div>
        `;
        return;
    }

    // Transform API data to match the expected format for renderFiles
    const transformedItems = items.map(item => ({
        id: item.id,
        file_name: item.file_name,
        file_size: item.file_size,
        mime_type: item.mime_type,
        created_at: item.created_at,
        updated_at: item.updated_at,
        is_folder: false, // blockchain files are never folders
        is_blockchain_stored: true,
        ipfs_hash: item.ipfs_hash,
        file_path: item.file_path,
        // Add blockchain-specific metadata for enhanced actions
        blockchain_provider: item.provider || 'pinata',
        blockchain_url: item.gateway_url,
        blockchain_metadata: {
            provider: item.provider || 'pinata',
            gateway_url: item.gateway_url,
            encrypted: item.encrypted || false,
            upload_timestamp: item.upload_timestamp,
            pin_status: 'pinned',
            redundancy_level: 3
        }
    }));

    // Use the same renderFiles function as My Documents for consistency
    renderFiles(transformedItems);
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

// Expose functions globally for onclick handlers
window.downloadFromBlockchain = downloadFromBlockchain;
window.removeFromBlockchain = removeFromBlockchain;
