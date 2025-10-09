/**
 * Client-Side Arweave Upload Modal
 * Direct user uploads to Arweave via their own MetaMask wallet
 */

// Using the new bundlr-wallet-widget.js instead of old client-side-bundlr.js
// All Bundlr functionality now comes from the navigation widget

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
                // Check if Bundlr widget is ready
                if (!window.isWalletReady || !window.isWalletReady()) {
                    showError('Please initialize Bundlr first using the B button in navigation');
                    return;
                }
                
                const balance = window.getCurrentBalance();
                
                if (balance >= 0.005) {
                    console.log('✅ Sufficient balance, proceeding to upload');
                    showStep('upload');
                } else {
                    console.log('❌ Insufficient balance:', balance, 'MATIC (need ≥0.005)');
                    showError(`Insufficient balance: ${balance.toFixed(6)} MATIC. Please fund your Bundlr account first (need ≥0.005 MATIC).`);
                    return; // Block upload
                }
            } catch (error) {
                console.error('❌ Error checking balance:', error);
                showError('Failed to check balance: ' + error.message);
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
        
        // Load live balance if Bundlr is ready
        loadLiveBalance();
        
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
    updateUploadCost('0');
    
    // Clear file name in upload step
    const uploadFileNameEl = document.getElementById('uploadFileName');
    if (uploadFileNameEl) {
        uploadFileNameEl.textContent = 'No file selected';
    }
    
    showStep('fileSelection');
}

/**
 * Load live balance from Bundlr widget
 */
function loadLiveBalance() {
    try {
        if (window.isWalletReady && window.isWalletReady()) {
            const balance = window.getCurrentBalance();
            console.log('🔄 Loading live Bundlr balance:', balance, 'MATIC');
            updateBalance(balance);
            updateBalanceSufficiency(balance >= 0.005, balance);
        } else {
            console.log('⚠️ Bundlr widget not ready, showing default balance');
            updateBalance(0);
            updateBalanceSufficiency(false, 0);
        }
    } catch (error) {
        console.warn('⚠️ Failed to load live balance:', error);
        updateBalance(0);
        updateBalanceSufficiency(false, 0);
    }
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
    
    // Update file name in upload step
    const uploadFileNameEl = document.getElementById('uploadFileName');
    if (uploadFileNameEl) {
        uploadFileNameEl.textContent = file.name;
    }
    
    // Calculate real upload cost
    const estimatedCostMatic = Math.max(0.005, file.size / 1000000 * 0.005); // ~0.005 MATIC per MB
    uploadCost = estimatedCostMatic;
    updateUploadCost(estimatedCostMatic.toFixed(6));
    
    // Update upload cost in final step
    const uploadCostFinalEl = document.getElementById('uploadCostFinal');
    if (uploadCostFinalEl) {
        uploadCostFinalEl.textContent = `~${estimatedCostMatic.toFixed(6)} MATIC`;
    }
    
    console.log('📄 File selected:', file.name, 'Size:', formatFileSize(file.size), 'Estimated cost:', estimatedCostMatic.toFixed(6), 'MATIC');
    
    showStep('walletConnection');
}

/**
 * Handle wallet connection - Use existing Bundlr widget
 */
async function handleConnectWallet() {
    try {
        showLoading('connectWalletBtn', 'Connecting...');
        
        // Check if Bundlr widget is already initialized
        if (window.isWalletReady && window.isWalletReady()) {
            console.log('✅ Using existing Bundlr wallet connection');
            const balance = window.getCurrentBalance();
            console.log('🔍 Real widget balance:', balance, 'MATIC');
            updateBalance(balance);
            
            // Show wallet connected status and proceed to balance check
            showWalletConnected(window.ethereum.selectedAddress, balance);
            showStep('balanceCheck');
            return;
        }
        
        // If not initialized, guide user to use the widget
        showError('Please initialize Bundlr first using the "B" button in the navigation bar, then try again.');
        
    } catch (error) {
        console.error('❌ Failed to connect wallet:', error);
        showError('Please use the Bundlr wallet widget (B button) in the navigation to connect first.');
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
 * Handle upload to Arweave using wallet widget  
 */
async function handleUploadToArweave() {
    if (!currentFile) {
        showError('Please select a file first');
        return;
    }

    // Check if wallet widget is initialized
    if (!window.isWalletReady || !window.isWalletReady()) {
        showError('Please initialize Bundlr wallet first using the "B" button in the navigation');
        return;
    }

    try {
        console.log('🚀 Starting Arweave upload using wallet widget...', currentFile.name);

        // Show loading on upload button
        showLoading('uploadToArweaveBtn', 'Uploading...');
        
        // Check balance
        const balance = window.getCurrentBalance();
        const uploadCost = 0.005; // Estimated cost in MATIC
        
        if (balance < uploadCost) {
            throw new Error(`Insufficient Bundlr balance (${balance.toFixed(6)} MATIC). Please fund your account using the wallet widget.`);
        }
        
        // Upload using wallet widget
        const result = await window.uploadFileWithBundlr(currentFile);
        
        if (!result.success) {
            throw new Error(result.error || 'Upload failed');
        }
        
        // Create simple upload data for saving
        const uploadData = {
            arweave_url: result.url,
            file_name: currentFile.name
        };
        
        // Save to database (optional)
        await saveUploadRecord(uploadData);
        
        // Show success step with URL
        showUploadSuccess(result.url, result.remainingBalance);
        
        console.log('✅ Upload completed successfully via wallet widget');
        console.log('🔗 Arweave URL:', result.url);
        console.log('💰 Remaining balance:', result.remainingBalance, 'MATIC');
        
    } catch (error) {
        console.error('❌ Upload failed:', error);
        showError(error.message);
    } finally {
        // Reset button
        hideLoading('uploadToArweaveBtn', '🚀 Upload to Arweave');
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
    // Ensure balance is a valid number
    const numericBalance = parseFloat(balance) || 0;
    
    // Update all balance displays in the modal
    const bundlrBalanceDisplay = document.getElementById('bundlrBalanceDisplay');
    const currentBundlrBalance = document.getElementById('currentBundlrBalance');
    const uploadBalanceFinal = document.getElementById('uploadBalanceFinal');
    
    if (bundlrBalanceDisplay) {
        bundlrBalanceDisplay.textContent = numericBalance.toFixed(6);
    }
    
    if (currentBundlrBalance) {
        currentBundlrBalance.textContent = numericBalance.toFixed(6);
    }
    
    if (uploadBalanceFinal) {
        uploadBalanceFinal.textContent = numericBalance.toFixed(6) + ' MATIC';
    }
    
    console.log('💰 Updated modal balance displays to:', numericBalance.toFixed(6), 'MATIC');
}

function updateUploadCost(cost) {
    const element = document.getElementById('uploadCostDisplay');
    if (element) element.textContent = `~${cost} MATIC (~$${(parseFloat(cost) * 0.7).toFixed(4)})`;
}

function updateBalanceSufficiency(isSufficient, balance) {
    const sufficientElement = document.getElementById('balanceSufficient');
    
    if (sufficientElement) {
        if (isSufficient) {
            sufficientElement.textContent = '✅ Yes';
            sufficientElement.className = 'text-green-400';
        } else {
            sufficientElement.textContent = '❌ No (Need ≥0.005)';
            sufficientElement.className = 'text-red-400';
        }
    }
    
    console.log('💰 Balance sufficiency:', isSufficient ? '✅ Sufficient' : '❌ Insufficient', `(${balance.toFixed(6)} MATIC)`);
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
 * Show upload success step
 */
function showUploadSuccess(arweaveUrl, remainingBalance) {
    // Update success step elements
    const urlInput = document.getElementById('arweaveSuccessUrlInput');
    const urlLink = document.getElementById('arweaveSuccessUrlLink');
    const balanceSpan = document.getElementById('arweaveSuccessBalance');
    
    if (urlInput) {
        urlInput.value = arweaveUrl;
    }
    
    if (urlLink) {
        urlLink.href = arweaveUrl;
    }
    
    if (balanceSpan && remainingBalance !== undefined) {
        balanceSpan.textContent = `${remainingBalance.toFixed(6)} MATIC`;
    }
    
    // Show success step
    showStep('success');
}

/**
 * Handle balance checking
 */
async function checkBalance() {
    try {
        console.log('🔍 Checking Bundlr balance...');
        showLoading('checkBalanceBtn', 'Checking...');
        
        // Use the real Bundlr widget connection
        if (!window.isWalletReady || !window.isWalletReady()) {
            throw new Error('Bundlr not initialized. Please use the B button in navigation first.');
        }
        
        const balance = window.getCurrentBalance();
        console.log('💰 Current Bundlr balance:', balance, 'MATIC');
        
        updateBalance(balance);
        
        // Check if balance is sufficient for upload
        const sufficientForUpload = balance >= 0.005;
        updateBalanceSufficiency(sufficientForUpload, balance);
        
        if (sufficientForUpload) {
            console.log('✅ Balance sufficient for upload');
            showStep('upload');
        } else {
            console.log('❌ Insufficient balance, showing funding options');
            showStep('funding');
        }
        
    } catch (error) {
        console.error('❌ Failed to check balance:', error);
        showError('Failed to check balance: ' + error.message);
        
        // Show error state
        document.getElementById('bundlrBalanceDisplay').innerHTML = 'Error';
        document.getElementById('sufficientBalanceDisplay').innerHTML = '❌ Error';
    } finally {
        hideLoading('checkBalanceBtn', 'Check Balance');
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
        
        // Check if Bundlr widget is ready
        if (!window.isWalletReady || !window.isWalletReady()) {
            throw new Error('Bundlr not initialized. Please use the B button in navigation first.');
        }
        
        // Fund with 0.01 MATIC (like YouTube video)
        const fundAmount = 0.01;
        console.log(`💰 Funding Bundlr with ${fundAmount} MATIC...`);
        
        // Use the real Bundlr widget instance
        if (!window.bundlrInstance) {
            throw new Error('Bundlr instance not found');
        }
        
        const conv = new BigNumber(fundAmount).multipliedBy(window.bundlrInstance.currencyConfig.base[1]);
        const response = await window.bundlrInstance.fund(conv);
        console.log('Wallet funded: ', response);
        
        // Get updated balance  
        const bal = await window.bundlrInstance.getLoadedBalance();
        const newBalance = parseFloat(ethers.utils.formatEther(bal.toString()));
        
        const result = { success: true, newBalance: newBalance };
        
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
        
        // Better error messages for common MetaMask errors
        let errorMessage = error.message;
        if (error.code === -32603 || error.message.includes('Internal JSON-RPC error')) {
            errorMessage = 'Insufficient MATIC in wallet. Please add MATIC to your MetaMask wallet first.';
        } else if (error.code === 4001) {
            errorMessage = 'Transaction rejected by user';
        }
        
        showError('Failed to fund Bundlr: ' + errorMessage);
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
        
        // Use the real Bundlr widget connection
        if (!window.isWalletReady || !window.isWalletReady()) {
            throw new Error('Bundlr not initialized. Please use the B button in navigation first.');
        }
        
        // Trigger refresh on the widget and get updated balance
        if (window.bundlrInstance) {
            const bal = await window.bundlrInstance.getLoadedBalance();
            const balance = parseFloat(ethers.utils.formatEther(bal.toString()));
            console.log('💰 Refreshed balance:', balance, 'MATIC');
            
            updateBalance(balance);
            updateBalanceSufficiency(balance >= 0.005, balance);
            
            showSuccess('Balance refreshed successfully!');
        } else {
            throw new Error('Bundlr instance not found');
        }
        
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
