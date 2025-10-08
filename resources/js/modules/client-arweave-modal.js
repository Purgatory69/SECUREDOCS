/**
 * Client-Side Arweave Upload Modal
 * Direct user uploads to Arweave via their own MetaMask wallet
 */

import { 
    initializeClientBundlr, 
    fetchUserBalance, 
    fundUserBundlr, 
    uploadToArweaveClientSide, 
    calculateUploadCost,
    isBundlrInitialized 
} from './client-side-bundlr.js';

let currentFile = null;
let uploadCost = 0;

/**
 * Initialize the client-side Arweave modal
 */
export function initializeClientArweaveModal() {

    // Get modal element
    const modal = document.getElementById('clientArweaveModal');
    
    if (!modal) {
        console.warn('Client Arweave modal not found in DOM');
        return;
    }

    // Make openClientArweaveModal available globally
    window.openClientArweaveModal = openClientArweaveModal;

    // Set up event listeners
    setupEventListeners();
    

    

}

/**
 * Set up all event listeners for the modal
 */
function setupEventListeners() {
    // File input
    const fileInput = document.getElementById('clientArweaveFile');
    if (fileInput) {
        fileInput.addEventListener('change', handleFileSelection);
    }

    // Connect wallet button
    const connectBtn = document.getElementById('connectWalletBtn');
    if (connectBtn) {
        connectBtn.addEventListener('click', handleConnectWallet);
    }

    // Fund Bundlr button
    const fundBtn = document.getElementById('fundBundlrBtn');
    if (fundBtn) {
        fundBtn.addEventListener('click', handleFundBundlr);
    }

    // Upload button
    const uploadBtn = document.getElementById('uploadToArweaveBtn');
    if (uploadBtn) {
        uploadBtn.addEventListener('click', handleUploadToArweave);
    }

    // Balance check buttons
    const checkBalanceBtn = document.getElementById('checkBalanceBtn');
    if (checkBalanceBtn) {
        checkBalanceBtn.addEventListener('click', handleCheckBalance);
    }

    const refreshBalanceBtn = document.getElementById('refreshBalanceBtn');
    if (refreshBalanceBtn) {
        refreshBalanceBtn.addEventListener('click', handleRefreshBalance);
    }

    // Wallet details button
    const viewWalletDetailsBtn = document.getElementById('viewWalletDetailsBtn');
    if (viewWalletDetailsBtn) {
        viewWalletDetailsBtn.addEventListener('click', toggleWalletDetails);
    }

    // Continue to balance button
    const continueToBalanceBtn = document.getElementById('continueToBalanceBtn');
    if (continueToBalanceBtn) {
        continueToBalanceBtn.addEventListener('click', () => {
            console.log('🔄 Manual continue to balance check');
            showStep('balanceCheck');
        });
    }

    // Fund Bundlr Account Button (YouTube Style)
    const fundBundlrAccountBtn = document.getElementById('fundBundlrAccountBtn');
    if (fundBundlrAccountBtn) {
        fundBundlrAccountBtn.addEventListener('click', handleFundBundlrAccount);
    }

    // Refresh Bundlr Balance Button
    const refreshBundlrBalanceBtn = document.getElementById('refreshBundlrBalanceBtn');
    if (refreshBundlrBalanceBtn) {
        refreshBundlrBalanceBtn.addEventListener('click', handleRefreshBundlrBalance);
    }

    // Proceed from balance button (check balance first)
    const proceedFromBalanceBtn = document.getElementById('proceedFromBalanceBtn');
    if (proceedFromBalanceBtn) {
        proceedFromBalanceBtn.addEventListener('click', async () => {
            console.log('🚀 Checking balance before upload...');
            
            if (!currentFile) {
                showError('Please select a file first');
                return;
            }
            
            try {
                // Check if we have sufficient balance
                const bundlr = await initializeClientBundlr();
                const balance = await fetchUserBalance(bundlr);
                
                if (balance >= 0.005) {
                    console.log('✅ Sufficient balance, proceeding to upload');
                    showStep('upload');
                } else {
                    console.log('💳 Insufficient balance, need to fund');
                    showStep('funding');
                }
            } catch (error) {
                console.error('❌ Error checking balance:', error);
                showStep('upload'); // Proceed anyway
            }
        });
    }

    // Close button
    const closeBtn = document.getElementById('clientArweaveCloseBtn');
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }
}

/**
 * Open the client-side Arweave modal
 */
export function openClientArweaveModal() {
    console.log('🚀 openClientArweaveModal() called!');
    
    const modal = document.getElementById('clientArweaveModal');
    console.log('📍 Modal element:', modal);
    
    if (modal) {
        console.log('✅ Modal found! Opening...');
        
        // Show modal (has flex class in HTML, just remove hidden and set display)
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
        
        console.log('🎨 Modal display set to:', modal.style.display);
        console.log('🎨 Modal classes:', modal.className);
        
        // Reset to initial state
        showStep('fileSelection');
        resetModalState();
        
        console.log('✅ Modal opened successfully!');
    } else {
        console.error('❌ Modal element not found!');
    }
}

/**
 * Close the modal
 */
function closeModal() {
    const modal = document.getElementById('clientArweaveModal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.add('hidden');
        resetModalState();
    }
}

/**
 * Reset modal to initial state
 */
function resetModalState() {
    currentFile = null;
    uploadCost = 0;
    
    // Clear file input
    const fileInput = document.getElementById('clientArweaveFile');
    if (fileInput) fileInput.value = '';
    
    // Reset displays
    updateFileInfo('');
    updateBalance('0');
    updateUploadCost('0');
    
    showStep('fileSelection');
}

/**
 * Handle file selection
 */
async function handleFileSelection(event) {
    const file = event.target.files[0];
    
    if (!file) {
        currentFile = null;
        updateFileInfo('');
        return;
    }
    
    currentFile = file;
    updateFileInfo(`${file.name} (${formatFileSize(file.size)})`);
    
    // Calculate upload cost if Bundlr is initialized
    if (isBundlrInitialized()) {
        try {
            const cost = await calculateUploadCost(file.size);
            uploadCost = parseFloat(cost.costMatic);
            updateUploadCost(cost.costMatic);
        } catch (error) {
            console.warn('Failed to calculate cost:', error);
            updateUploadCost('~0.005');
        }
    }
    
    showStep('walletConnection');
}

/**
 * Handle wallet connection
 */
async function handleConnectWallet() {
    try {
        showLoading('connectWalletBtn', 'Connecting...');
        
        const result = await initializeClientBundlr();
        
        if (result.success) {
            console.log('✅ Wallet connected successfully');
            
            // Update balance display
            updateBalance(result.balance);
            
            // Show wallet as connected
            const walletAddress = window.ethereum.selectedAddress;
            showWalletConnected(walletAddress, result.balance);
            
            // Save wallet info to backend
            await saveWalletInfo();
            
            // Always go to balance check step first
            showStep('balanceCheck');
            
            // Update the balance display in step 3 (YouTube style)
            const balanceDisplay = document.getElementById('currentBundlrBalance');
            if (balanceDisplay) {
                balanceDisplay.textContent = parseFloat(result.balance).toFixed(6);
            }
            
            // Check if balance is sufficient for 0.005 MATIC uploads
            const sufficientDisplay = document.getElementById('balanceSufficient');
            if (sufficientDisplay) {
                if (parseFloat(result.balance) >= 0.005) {
                    sufficientDisplay.innerHTML = '✅ Yes';
                    sufficientDisplay.className = 'text-green-400';
                } else {
                    sufficientDisplay.innerHTML = '❌ No (Need ≥0.005)';
                    sufficientDisplay.className = 'text-red-400';
                }
            }
            
        } else {
            throw new Error(result.error);
        }
        
    } catch (error) {
        console.error('❌ Failed to connect wallet:', error);
        showError('Failed to connect wallet: ' + error.message);
    } finally {
        hideLoading('connectWalletBtn', 'Connect MetaMask');
    }
}

/**
 * Handle Bundlr funding
 */
async function handleFundBundlr() {
    try {
        const amountInput = document.getElementById('fundAmount');
        const amount = parseFloat(amountInput.value);
        
        if (!amount || amount <= 0) {
            throw new Error('Please enter a valid amount');
        }
        
        showLoading('fundBundlrBtn', 'Funding...');
        
        const result = await fundUserBundlr(amount);
        
        if (result.success) {
            console.log('✅ Bundlr funded successfully');
            updateBalance(result.newBalance);
            
            // Update backend balance
            await updateBackendBalance(result.newBalance);
            
            showStep('upload');
            showSuccess('Bundlr funded successfully! New balance: ' + result.newBalance + ' MATIC');
            
        } else {
            throw new Error(result.error);
        }
        
    } catch (error) {
        console.error('❌ Failed to fund Bundlr:', error);
        showError('Failed to fund Bundlr: ' + error.message);
    } finally {
        hideLoading('fundBundlrBtn', 'Fund Bundlr');
    }
}

/**
 * Handle upload to Arweave
 */
async function handleUploadToArweave() {
    if (!currentFile) {
        showError('Please select a file first');
        return;
    }
    
    try {
        showLoading('uploadToArweaveBtn', 'Uploading...');
        
        // Get current balance before upload
        const balanceBefore = await fetchUserBalance();
        
        // Upload file
        const result = await uploadToArweaveClientSide(currentFile, {
            userId: window.Laravel?.user?.id
        });
        
        if (result.success) {
            console.log('✅ File uploaded successfully');
            
            // Save upload record to backend
            await saveUploadRecord({
                arweave_tx_id: result.transactionId,
                arweave_url: result.url,
                file_name: currentFile.name,
                file_size: currentFile.size,
                mime_type: currentFile.type,
                upload_cost: uploadCost,
                bundlr_balance_before: balanceBefore,
                bundlr_balance_after: result.remainingBalance
            });
            
            showStep('success');
            
            // Show success info
            document.getElementById('arweaveSuccessUrl').href = result.url;
            document.getElementById('arweaveSuccessUrl').textContent = result.url;
            document.getElementById('arweaveSuccessBalance').textContent = result.remainingBalance + ' MATIC';
            
        } else {
            throw new Error(result.error);
        }
        
    } catch (error) {
        console.error('❌ Failed to upload file:', error);
        showError('Failed to upload file: ' + error.message);
    } finally {
        hideLoading('uploadToArweaveBtn', 'Upload to Arweave');
    }
}

/**
 * Show specific step in the modal
 */
function showStep(stepName) {
    const steps = ['fileSelection', 'walletConnection', 'balanceCheck', 'funding', 'upload', 'success'];
    
    steps.forEach(step => {
        const element = document.getElementById(`step${step.charAt(0).toUpperCase() + step.slice(1)}`);
        if (element) {
            if (step === stepName) {
                element.classList.remove('hidden');
            } else {
                element.classList.add('hidden');
            }
        }
    });
}

/**
 * Save wallet info to backend
 */
async function saveWalletInfo() {
    try {
        const walletAddress = window.ethereum.selectedAddress;
        
        const response = await fetch('/arweave-client/wallet-info', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                wallet_address: walletAddress
            })
        });
        
        const data = await response.json();
        
        if (!data.success) {
            console.warn('Failed to save wallet info:', data.message);
        }
        
    } catch (error) {
        console.warn('Failed to save wallet info:', error);
    }
}

/**
 * Update backend balance
 */
async function updateBackendBalance(balance) {
    try {
        const walletAddress = window.ethereum.selectedAddress;
        
        const response = await fetch('/arweave-client/update-balance', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                wallet_address: walletAddress,
                balance_ar: balance
            })
        });
        
        const data = await response.json();
        
        if (!data.success) {
            console.warn('Failed to update balance:', data.message);
        }
        
    } catch (error) {
        console.warn('Failed to update balance:', error);
    }
}

/**
 * Save upload record to backend (simplified - optional)
 */
async function saveUploadRecord(uploadData) {
    try {
        const saveOption = document.querySelector('input[name="saveOption"]:checked')?.value || 'save_url';
        
        if (saveOption === 'skip_save') {
            console.log('📋 Skipping database save - user chose URL only');
            return;
        }
        
        // Try to save, but don't fail the whole upload if this fails
        const walletAddress = window.ethereum.selectedAddress;
        
        const saveResponse = await fetch('/arweave-client/save-upload', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                arweave_url: uploadData.arweave_url,
                file_name: uploadData.file_name
            })
        });

        if (saveResponse.ok) {
            console.log('✅ Upload record saved to database');
        } else {
            console.warn('⚠️ Failed to save upload record, but upload succeeded');
        }
        
    } catch (error) {
        console.warn('⚠️ Failed to save upload record, but upload succeeded:', error.message);
    }
}

function updateFileInfo(text) {
    const element = document.getElementById('selectedFileInfo');
    if (element) element.textContent = text;
}
function updateBalance(balance) {
    const element = document.getElementById('bundlrBalanceDisplay');
    if (element) element.textContent = balance;
}

function updateUploadCost(cost) {
    const element = document.getElementById('uploadCostDisplay');
    if (element) element.textContent = `~${cost} MATIC (~$${(parseFloat(cost) * 0.7).toFixed(4)})`;
}

/**
 * Show/hide loading states
 */
function showLoading(buttonId, text) {
    const button = document.getElementById(buttonId);
    if (button) {
        button.disabled = true;
        button.innerHTML = `
            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
            ${text}
        `;
    }
}

function hideLoading(buttonId, text) {
    const button = document.getElementById(buttonId);
    if (button) {
        button.disabled = false;
        button.textContent = text;
    }
}

/**
 * Show error/success messages
 */
function showError(message) {
    const errorDiv = document.getElementById('clientArweaveError');
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.classList.remove('hidden');
        setTimeout(() => {
            errorDiv.classList.add('hidden');
        }, 5000);
    }
}

function showSuccess(message) {
    const successDiv = document.getElementById('clientArweaveSuccess');
    if (successDiv) {
        successDiv.textContent = message;
        successDiv.classList.remove('hidden');
        setTimeout(() => {
            successDiv.classList.add('hidden');
        }, 5000);
    }
}

/**
 * Handle balance checking
 */
async function handleCheckBalance() {
    try {
        console.log('🔍 Checking Bundlr balance...');
        showLoading('checkBalanceBtn', 'Checking...');
        
        const bundlr = await initializeClientBundlr();
        if (!bundlr) {
            throw new Error('Failed to initialize Bundlr');
        }
        
        const balance = await fetchUserBalance(bundlr);
        console.log('💰 Current balance:', balance);
        
        // Update display
        updateBalanceDisplay(balance);
        
        hideLoading('checkBalanceBtn', '🔍 Check Balance');
        showSuccess('Balance updated successfully!');
        
    } catch (error) {
        console.error('❌ Failed to check balance:', error);
        hideLoading('checkBalanceBtn', '🔍 Check Balance');
        showError('Failed to check balance: ' + error.message);
        
        // Show error state
        document.getElementById('bundlrBalanceDisplay').innerHTML = 'Error';
        document.getElementById('sufficientBalanceDisplay').innerHTML = '❌ Error';
    }
}

/**
 * Handle balance refresh
 */
async function handleRefreshBalance() {
    await handleCheckBalance(); // Same logic as check balance
}

/**
 * Handle Bundlr Account Funding (YouTube Style)
 */
async function handleFundBundlrAccount() {
    try {
        console.log('💳 Funding Bundlr account...');
        showLoading('fundBundlrAccountBtn', 'Funding...');
        
        // Initialize Bundlr
        const bundlr = await initializeClientBundlr();
        if (!bundlr) {
            throw new Error('Failed to initialize Bundlr');
        }
        
        // Fund with 0.01 MATIC (like YouTube video)
        const fundAmount = 0.01;
        console.log(`💰 Funding Bundlr with ${fundAmount} MATIC...`);
        
        const result = await fundUserBundlr(fundAmount);
        
        if (result.success) {
            console.log('✅ Bundlr funded successfully!');
            
            // Update balance display
            const balanceDisplay = document.getElementById('currentBundlrBalance');
            if (balanceDisplay) {
                balanceDisplay.textContent = result.newBalance.toFixed(6);
            }
            
            showSuccess(`Bundlr funded with ${fundAmount} MATIC! New balance: ${result.newBalance.toFixed(6)} MATIC`);
        } else {
            throw new Error(result.error || 'Funding failed');
        }
        
    } catch (error) {
        console.error('❌ Failed to fund Bundlr:', error);
        showError('Failed to fund Bundlr: ' + error.message);
    } finally {
        hideLoading('fundBundlrAccountBtn', '💳 Fund Account');
    }
}

/**
 * Handle Bundlr Balance Refresh
 */
async function handleRefreshBundlrBalance() {
    try {
        console.log('🔄 Refreshing Bundlr balance...');
        showLoading('refreshBundlrBalanceBtn', 'Refreshing...');
        
        const bundlr = await initializeClientBundlr();
        if (!bundlr) {
            throw new Error('Failed to initialize Bundlr');
        }
        
        const balance = await fetchUserBalance(bundlr);
        console.log('💰 Current balance:', balance);
        
        // Update balance display
        const balanceDisplay = document.getElementById('currentBundlrBalance');
        const sufficientDisplay = document.getElementById('balanceSufficient');
        
        if (balanceDisplay) {
            balanceDisplay.textContent = balance.toFixed(6);
        }
        
        if (sufficientDisplay) {
            if (balance >= 0.005) {
                sufficientDisplay.innerHTML = '✅ Yes';
                sufficientDisplay.className = 'text-green-400';
            } else {
                sufficientDisplay.innerHTML = '❌ No (Need funding)';
                sufficientDisplay.className = 'text-red-400';
            }
        }
        
        showSuccess('Balance refreshed successfully!');
        
    } catch (error) {
        console.error('❌ Failed to refresh balance:', error);
        showError('Failed to refresh balance: ' + error.message);
    } finally {
        hideLoading('refreshBundlrBalanceBtn', '🔄 Refresh');
    }
}

/**
 * Toggle wallet details panel
 */
function toggleWalletDetails() {
    const panel = document.getElementById('walletDetailsPanel');
    const button = document.getElementById('viewWalletDetailsBtn');
    
    if (panel && button) {
        if (panel.classList.contains('hidden')) {
            panel.classList.remove('hidden');
            button.textContent = 'Hide Details';
        } else {
            panel.classList.add('hidden');
            button.textContent = 'View Details';
        }
    }
}

/**
 * Show wallet as connected
 */
function showWalletConnected(walletAddress, balance = null) {
    const statusDisplay = document.getElementById('walletStatusDisplay');
    const connectionPanel = document.getElementById('walletConnectionPanel');
    const addressDisplay = document.getElementById('connectedWalletAddress');
    const balanceDisplay = document.getElementById('walletBundlrBalance');
    
    if (statusDisplay && connectionPanel && addressDisplay) {
        // Show connected status
        statusDisplay.classList.remove('hidden');
        connectionPanel.classList.add('hidden');
        
        // Update address (show first 6 and last 4 characters)
        const shortAddress = `${walletAddress.slice(0, 6)}...${walletAddress.slice(-4)}`;
        addressDisplay.textContent = shortAddress;
        
        // Update balance if provided
        if (balance !== null && balanceDisplay) {
            balanceDisplay.textContent = `${balance.toFixed(6)} MATIC`;
        }
    }
}

/**
 * Update balance display in the UI
 */
function updateBalanceDisplay(balanceInMatic) {
    const balanceDisplay = document.getElementById('bundlrBalanceDisplay');
    const sufficientDisplay = document.getElementById('sufficientBalanceDisplay');
    const statusInfo = document.getElementById('balanceStatusInfo');
    const statusText = document.getElementById('balanceStatusText');
    
    if (balanceDisplay) {
        balanceDisplay.textContent = balanceInMatic.toFixed(6);
    }
    
    // Check if balance is sufficient (assuming 0.005 MATIC per upload)
    const requiredBalance = 0.005;
    const isSufficient = balanceInMatic >= requiredBalance;
    
    if (sufficientDisplay) {
        if (isSufficient) {
            sufficientDisplay.innerHTML = '✅ Yes';
            sufficientDisplay.className = 'text-green-400';
        } else {
            sufficientDisplay.innerHTML = '❌ No (Need to fund)';
            sufficientDisplay.className = 'text-red-400';
        }
    }
    
    // Show status message
    if (statusInfo && statusText) {
        if (isSufficient) {
            statusInfo.className = 'bg-green-50 border border-green-200 rounded-lg p-4';
            statusText.textContent = `You have ${balanceInMatic.toFixed(6)} MATIC. Sufficient for uploads!`;
            statusInfo.classList.remove('hidden');
        } else {
            statusInfo.className = 'bg-yellow-50 border border-yellow-200 rounded-lg p-4';
            statusText.textContent = `You need at least ${requiredBalance} MATIC. Please fund your Bundlr account.`;
            statusInfo.classList.remove('hidden');
        }
    }
}

/**
 * Format file size
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Export functions for global use
window.openClientArweaveModal = openClientArweaveModal;
window.showStep = showStep;

export default {
    initializeClientArweaveModal,
    openClientArweaveModal
};
