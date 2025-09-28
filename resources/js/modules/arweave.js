// Arweave permanent storage module
import { showNotification } from './ui.js';

let walletInfo = null;

/**
 * Initialize Arweave functionality
 */
export function initArweave() {
    loadWalletInfo();
    setupArweaveEventListeners();
}

/**
 * Setup event listeners for Arweave functionality
 */
function setupArweaveEventListeners() {
    // Create wallet button
    document.addEventListener('click', async (e) => {
        if (e.target.matches('[data-action="create-arweave-wallet"]')) {
            e.preventDefault();
            await createArweaveWallet();
        }
    });

    // Upload to Arweave button
    document.addEventListener('click', async (e) => {
        if (e.target.matches('[data-action="upload-to-arweave"]')) {
            e.preventDefault();
            const fileId = e.target.dataset.fileId;
            if (fileId) {
                await uploadToArweave(fileId);
            }
        }
    });

    // Check transaction status
    document.addEventListener('click', async (e) => {
        if (e.target.matches('[data-action="check-arweave-status"]')) {
            e.preventDefault();
            const txId = e.target.dataset.txId;
            if (txId) {
                await checkTransactionStatus(txId);
            }
        }
    });
}

/**
 * Load user's Arweave wallet information
 */
async function loadWalletInfo() {
    try {
        const response = await fetch('/arweave/wallet/info');
        const data = await response.json();
        
        if (data.success && data.has_wallet) {
            walletInfo = data.wallet;
            updateWalletDisplay(data.wallet);
        } else {
            showCreateWalletPrompt();
        }
    } catch (error) {
        console.error('Failed to load wallet info:', error);
    }
}

/**
 * Create new Arweave wallet
 */
async function createArweaveWallet() {
    try {
        showNotification('Creating Arweave wallet...', 'info');
        
        const response = await fetch('/arweave/wallet/create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const data = await response.json();

        if (data.success) {
            walletInfo = {
                address: data.wallet_address,
                balance: data.balance
            };
            
            showNotification('Arweave wallet created successfully!', 'success');
            updateWalletDisplay(walletInfo);
            showFundingInstructions(data.funding_instructions);
        } else {
            showNotification(`Failed to create wallet: ${data.message}`, 'error');
        }
    } catch (error) {
        console.error('Error creating wallet:', error);
        showNotification('Error creating wallet', 'error');
    }
}

/**
 * Upload file to Arweave
 */
async function uploadToArweave(fileId) {
    try {
        // First get cost estimate
        const costResponse = await fetch(`/arweave/files/${fileId}/cost-estimate`);
        const costData = await costResponse.json();

        if (!costData.success) {
            showNotification(`Error: ${costData.message}`, 'error');
            return;
        }

        // Show cost confirmation
        const confirmed = await showCostConfirmation(costData.cost_estimate, costData.file_info);
        if (!confirmed) return;

        // Proceed with upload
        showNotification('Uploading to Arweave...', 'info');
        
        const response = await fetch(`/arweave/files/${fileId}/upload`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const data = await response.json();

        if (data.success) {
            showNotification(
                `File uploaded to Arweave successfully! TX: ${data.tx_id.substring(0, 8)}...`, 
                'success'
            );
            
            // Show transaction details
            showTransactionDetails(data);
            
            // Refresh file list if available
            if (typeof window.loadUserFiles === 'function') {
                window.loadUserFiles();
            }
        } else {
            if (data.action_required === 'create_wallet') {
                showCreateWalletPrompt();
            } else if (data.action_required === 'fund_wallet') {
                showFundingRequired(data.cost_estimate, data.current_balance);
            } else {
                showNotification(`Upload failed: ${data.message}`, 'error');
            }
        }
    } catch (error) {
        console.error('Error uploading to Arweave:', error);
        showNotification('Error uploading to Arweave', 'error');
    }
}

/**
 * Check transaction status
 */
async function checkTransactionStatus(txId) {
    try {
        const response = await fetch(`/arweave/transactions/${txId}/status`);
        const data = await response.json();

        if (data.success) {
            const tx = data.transaction;
            const status = tx.confirmed ? 'Confirmed' : 'Pending';
            const message = `Transaction ${status}: ${tx.confirmations} confirmations`;
            
            showNotification(message, tx.confirmed ? 'success' : 'info');
            
            if (tx.confirmed) {
                updateFileArweaveStatus(txId, tx);
            }
        } else {
            showNotification('Transaction not found', 'error');
        }
    } catch (error) {
        console.error('Error checking transaction status:', error);
        showNotification('Error checking transaction status', 'error');
    }
}

/**
 * Show cost confirmation dialog
 */
async function showCostConfirmation(costEstimate, fileInfo) {
    const message = `
        Upload "${fileInfo.name}" (${fileInfo.size_formatted}) to Arweave?
        
        Cost: ${costEstimate.ar} AR (~$${costEstimate.usd})
        
        This is a ONE-TIME payment for PERMANENT storage.
        The file will be stored for 200+ years and cannot be deleted.
        
        Continue?
    `;
    
    return window.confirm(message);
}

/**
 * Show transaction details modal
 */
function showTransactionDetails(data) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.innerHTML = `
        <div class="bg-[#1F2235] border border-[#4A4D6A] rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold text-gray-200 mb-4">Arweave Upload Successful</h3>
            <div class="space-y-3 text-sm">
                <div>
                    <span class="text-gray-400">Transaction ID:</span>
                    <div class="font-mono text-blue-400 break-all">${data.tx_id}</div>
                </div>
                <div>
                    <span class="text-gray-400">Cost:</span>
                    <span class="text-green-400">${data.cost.ar} AR (~$${data.cost.usd})</span>
                </div>
                <div>
                    <span class="text-gray-400">Status:</span>
                    <span class="text-yellow-400">Pending confirmation</span>
                </div>
                <div>
                    <span class="text-gray-400">Estimated confirmation:</span>
                    <span class="text-gray-300">${data.estimated_confirmation}</span>
                </div>
                <div class="pt-2">
                    <a href="${data.arweave_url}" target="_blank" 
                       class="text-blue-400 hover:text-blue-300 underline">
                        View on Arweave →
                    </a>
                </div>
            </div>
            <div class="flex justify-end mt-6">
                <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg" 
                        onclick="this.closest('.fixed').remove()">
                    Close
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

/**
 * Show create wallet prompt
 */
function showCreateWalletPrompt() {
    const message = `
        You need an Arweave wallet to use permanent storage.
        
        Create wallet now?
    `;
    
    if (window.confirm(message)) {
        createArweaveWallet();
    }
}

/**
 * Show funding required message
 */
function showFundingRequired(costEstimate, currentBalance) {
    const needed = (costEstimate.ar - currentBalance.ar).toFixed(6);
    
    showNotification(
        `Insufficient AR balance. Need ${needed} more AR (~$${(needed * costEstimate.ar_usd_rate).toFixed(2)})`, 
        'warning'
    );
}

/**
 * Update wallet display in UI
 */
function updateWalletDisplay(wallet) {
    const walletElements = document.querySelectorAll('[data-wallet-info]');
    walletElements.forEach(element => {
        element.innerHTML = `
            <div class="text-xs text-gray-400">Arweave Wallet</div>
            <div class="font-mono text-sm">${wallet.address.substring(0, 8)}...${wallet.address.slice(-8)}</div>
            <div class="text-green-400">${wallet.balance.ar} AR (~$${wallet.balance.usd})</div>
        `;
    });
}

/**
 * Show funding instructions
 */
function showFundingInstructions(instructions) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.innerHTML = `
        <div class="bg-[#1F2235] border border-[#4A4D6A] rounded-lg p-6 max-w-lg w-full mx-4">
            <h3 class="text-lg font-semibold text-gray-200 mb-4">Fund Your Arweave Wallet</h3>
            <div class="space-y-4 text-sm">
                <p class="text-gray-300">${instructions.message}</p>
                <div>
                    <label class="text-gray-400">Wallet Address:</label>
                    <div class="font-mono text-blue-400 bg-gray-800 p-2 rounded mt-1 break-all">
                        ${instructions.address}
                    </div>
                </div>
                <div>
                    <span class="text-gray-400">Minimum amount:</span>
                    <span class="text-green-400">${instructions.min_amount}</span>
                </div>
                <div>
                    <span class="text-gray-400">Available on:</span>
                    <span class="text-gray-300">${instructions.exchanges.join(', ')}</span>
                </div>
            </div>
            <div class="flex justify-end mt-6">
                <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg" 
                        onclick="this.closest('.fixed').remove()">
                    Got it
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

/**
 * Update file status after Arweave confirmation
 */
function updateFileArweaveStatus(txId, transaction) {
    const fileElements = document.querySelectorAll(`[data-arweave-tx="${txId}"]`);
    fileElements.forEach(element => {
        element.innerHTML = `
            <span class="text-green-400">✓ Permanent</span>
            <a href="${transaction.gateway_url}" target="_blank" class="text-blue-400 hover:text-blue-300 ml-2">
                View →
            </a>
        `;
    });
}

/**
 * Get wallet info
 */
export function getWalletInfo() {
    return walletInfo;
}

/**
 * Check if user has wallet
 */
export function hasWallet() {
    return walletInfo !== null;
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initArweave);
} else {
    initArweave();
}
