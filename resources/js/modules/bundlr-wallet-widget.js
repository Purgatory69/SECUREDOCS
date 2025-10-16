/**
 * Bundlr Wallet Widget - Navigation Integration
 * Real balance fetching like React app from YouTube video
 */

// Global state
let isInitialized = false;
let currentBalance = 0;

// Import functions dynamically to avoid dependency issues

/**
 * Initialize the wallet widget
 */
export function initializeBundlrWalletWidget() {
    // Check if widget exists (only for premium users)
    const walletBtn = document.getElementById('bundlrWalletBtn');
    if (!walletBtn) {
        console.log('📱 Bundlr wallet widget not found (non-premium user) - skipping initialization');
        return;
    }
    
    console.log('🔧 Initializing Bundlr Wallet Widget...');
    
    // Check library availability 
    console.log('📚 Checking libraries:');
    console.log('- Ethers:', typeof ethers !== 'undefined' ? '✅' : '❌');
    console.log('- WebBundlr:', typeof WebBundlr !== 'undefined' ? '✅' : '❌');  
    console.log('- Bundlr:', typeof Bundlr !== 'undefined' ? '✅' : '❌');
    console.log('- window.Bundlr:', typeof window.Bundlr !== 'undefined' ? '✅' : '❌');
    console.log('- BigNumber:', typeof BigNumber !== 'undefined' ? '✅' : '❌');
    console.log('- MetaMask:', typeof window.ethereum !== 'undefined' ? '✅' : '❌');
    
    // Debug what Bundlr actually contains
    if (typeof window.Bundlr !== 'undefined') {
        console.log('🔍 window.Bundlr type:', typeof window.Bundlr);
        console.log('🔍 window.Bundlr keys:', Object.keys(window.Bundlr));
        console.log('🔍 window.Bundlr.WebBundlr:', typeof window.Bundlr.WebBundlr);
        console.log('🔍 window.Bundlr.default:', typeof window.Bundlr.default);
    }
    
    // Set up event listeners
    setupWalletWidgetListeners();
    
    // Update UI state
    updateWalletWidgetUI();
    
    console.log('✅ Bundlr Wallet Widget initialized');
}

/**
 * Set up all event listeners for the wallet widget
 */
function setupWalletWidgetListeners() {
    console.log('🔧 Setting up wallet widget listeners...');
    
    // Use direct onclick for better compatibility
    const walletBtn = document.getElementById('bundlrWalletBtn');
    if (walletBtn) {
        console.log('✅ Found bundlr wallet button, setting up click handler');
        walletBtn.onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('🔘 Bundlr wallet button clicked!');
            toggleWalletDropdown();
        };
    } else {
        console.error('❌ Bundlr wallet button not found!');
    }
    
    // Set up other buttons with onclick
    setTimeout(() => {
        const initBtn = document.getElementById('initializeBundlrBtn');
        if (initBtn) {
            initBtn.onclick = handleInitializeBundlr;
        }
        
        const fundBtn = document.getElementById('fundBundlrBtn');
        if (fundBtn) {
            fundBtn.onclick = handleFundBundlr;
        }
        
        const refreshBtn = document.getElementById('refreshBalanceBtn');
        if (refreshBtn) {
            refreshBtn.onclick = handleRefreshBalance;
        }
    }, 500);
    
    // Note: Global click handler is managed by ui.js
    // Individual dropdown closing is handled by closeAllDropdowns()
}

/**
 * Toggle wallet dropdown
 */
function toggleWalletDropdown() {
    console.log('🔄 Toggling wallet dropdown...');
    const dropdown = document.getElementById('bundlrWalletDropdown');
    if (dropdown) {
        const isHidden = dropdown.classList.contains('opacity-0') || 
                         dropdown.classList.contains('invisible');
        console.log('Dropdown currently hidden:', isHidden);
        
        if (isHidden) {
            // Close all other dropdowns first (except language which is nested)
            if (window.closeAllDropdowns) {
                window.closeAllDropdowns(['bundlrWallet', 'language']);
            }
            
            // Show dropdown with animation
            dropdown.classList.remove('opacity-0', 'invisible', 'translate-y-[-10px]', 'scale-95');
            dropdown.classList.add('opacity-100', 'visible', 'translate-y-0', 'scale-100');
            
            // Update balance when opening and if initialized
            if (isInitialized) {
                console.log('Updating balance display...');
                updateBalanceDisplay();
            }
        } else {
            // Hide dropdown with animation
            dropdown.classList.add('opacity-0', 'invisible', 'translate-y-[-10px]', 'scale-95');
            dropdown.classList.remove('opacity-100', 'visible', 'translate-y-0', 'scale-100');
        }
        
        console.log('Dropdown now hidden:', dropdown.classList.contains('opacity-0'));
    } else {
        console.error('❌ Dropdown element not found!');
    }
}

/**
 * Handle Bundlr initialization (Real like React app)
 */
async function handleInitializeBundlr() {
    const initBtn = document.getElementById('initializeBundlrBtn');
    const statusEl = document.getElementById('walletStatus');
    
    try {
        console.log('🚀 Starting Bundlr initialization...');
        
        // Show loading
        if (initBtn) {
            initBtn.textContent = '🔄 Connecting...';
            initBtn.disabled = true;
        }
        
        if (statusEl) {
            statusEl.textContent = 'Connecting to MetaMask...';
        }
        
        // Check if required libraries are loaded
        if (typeof ethers === 'undefined') {
            throw new Error('Ethers.js library not loaded. Please refresh the page.');
        }
        
        // Check for Bundlr in multiple ways (CDN can export it differently)
        let BundlrClass = null;
        
        // Try different possible exports
        if (typeof WebBundlr !== 'undefined') {
            BundlrClass = WebBundlr;
        } else if (typeof window.WebBundlr !== 'undefined') {
            BundlrClass = window.WebBundlr;
        } else if (window.Bundlr && typeof window.Bundlr.WebBundlr !== 'undefined') {
            BundlrClass = window.Bundlr.WebBundlr;
        } else if (window.Bundlr && typeof window.Bundlr.default !== 'undefined') {
            BundlrClass = window.Bundlr.default;
        } else if (typeof Bundlr !== 'undefined') {
            BundlrClass = Bundlr;
        }
        
        if (!BundlrClass || typeof BundlrClass !== 'function') {
            console.error('❌ Bundlr constructor not found.');
            console.error('Available globals:', Object.getOwnPropertyNames(window).filter(name => 
                name.toLowerCase().includes('bundlr')
            ));
            if (window.Bundlr) {
                console.error('window.Bundlr contents:', Object.keys(window.Bundlr));
            }
            throw new Error('Bundlr constructor not found. The library may not be loaded correctly.');
        }
        
        if (typeof BigNumber === 'undefined') {
            throw new Error('BigNumber library not loaded. Please refresh the page.');
        }
        
        console.log('✅ Using Bundlr class:', BundlrClass.name || 'UnnamedClass');
        
        // Check if MetaMask is available
        if (!window.ethereum) {
            throw new Error('MetaMask not found. Please install MetaMask.');
        }
        
        // Enable MetaMask (exact React app code)
        await window.ethereum.enable();
        
        // Create Web3 provider (exact React app code) 
        const provider = new ethers.providers.Web3Provider(window.ethereum);
        await provider._ready();
        
        // Initialize Bundlr client using detected class
        const bundlr = new BundlrClass("https://node1.bundlr.network", "matic", provider);
        await bundlr.ready();
        
        // Store globally
        window.bundlrInstance = bundlr;
        window.bundlrRef = bundlr;
        
        // Fetch real balance (like React app)
        const bal = await bundlr.getLoadedBalance();
        currentBalance = parseFloat(ethers.utils.formatEther(bal.toString()));
        
        console.log('✅ Bundlr initialized successfully');
        console.log('💰 Real Balance:', currentBalance, 'MATIC');
        
        isInitialized = true;
        
        // Update UI
        updateWalletWidgetUI();
        updateBalanceDisplay();
        
        if (statusEl) {
            statusEl.textContent = `Connected - ${currentBalance.toFixed(6)} MATIC`;
        }
        
        // Enable buttons
        enableActionButtons();
        
        showNotification(`Bundlr connected! Balance: ${currentBalance.toFixed(6)} MATIC`, 'success');
        
    } catch (error) {
        console.error('❌ Failed to initialize Bundlr:', error);
        
        if (statusEl) {
            statusEl.textContent = `Error: ${error.message}`;
        }
        
        showNotification('Failed to initialize Bundlr: ' + error.message, 'error');
        
    } finally {
        // Reset button
        if (initBtn) {
            initBtn.textContent = '🚀 Initialize Bundlr';
            initBtn.disabled = false;
        }
    }
}

/**
 * Handle Bundlr funding (Real like React app)
 */
async function handleFundBundlr() {
    const fundBtn = document.getElementById('fundBundlrBtn');
    const amountSelect = document.getElementById('fundAmountSelect');
    
    if (!isInitialized || !window.bundlrInstance) {
        showNotification('Please initialize Bundlr first', 'error');
        return;
    }
    
    try {
        const amount = parseFloat(amountSelect.value);
        console.log(`💸 Funding Bundlr with ${amount} MATIC...`);
        
        // Show loading
        if (fundBtn) {
            fundBtn.textContent = '💸 Funding...';
            fundBtn.disabled = true;
        }
        
        // Check wallet MATIC balance first
        const provider = new ethers.providers.Web3Provider(window.ethereum);
        const signer = provider.getSigner();
        const walletAddress = await signer.getAddress();
        const walletBalance = await provider.getBalance(walletAddress);
        const walletBalanceMatic = parseFloat(ethers.utils.formatEther(walletBalance));
        
        console.log(`💳 Wallet MATIC balance: ${walletBalanceMatic.toFixed(6)} MATIC`);
        
        if (walletBalanceMatic < amount) {
            throw new Error(`Insufficient MATIC in wallet. Need ${amount} MATIC but only have ${walletBalanceMatic.toFixed(6)} MATIC. Please add MATIC to your MetaMask wallet first.`);
        }
        
        // Parse input (exact React app code)
        const conv = new BigNumber(amount).multipliedBy(window.bundlrInstance.currencyConfig.base[1]);
        if (conv.isLessThan(1)) {
            throw new Error('Value too small');
        }

        // Fund wallet (exact React app code)
        const response = await window.bundlrInstance.fund(conv);
        console.log('Wallet funded: ', response);
        
        // Fetch new balance (like React app)
        const bal = await window.bundlrInstance.getLoadedBalance();
        currentBalance = parseFloat(ethers.utils.formatEther(bal.toString()));
        
        console.log('✅ Bundlr funded successfully (REAL)');
        console.log('💰 New Balance:', currentBalance, 'MATIC');
        
        // Update balance display
        updateBalanceDisplay();
        updateWalletWidgetUI();
        
        showNotification(`Bundlr funded with ${amount} MATIC! New balance: ${currentBalance.toFixed(6)} MATIC`, 'success');
        
    } catch (error) {
        console.error('❌ Failed to fund Bundlr:', error);
        showNotification('Failed to fund Bundlr: ' + error.message, 'error');
        
    } finally {
        // Reset button
        if (fundBtn) {
            fundBtn.textContent = '💳 Fund';
            fundBtn.disabled = false;
        }
    }
}

/**
 * Handle balance refresh (Real like React app)
 */
async function handleRefreshBalance() {
    const refreshBtn = document.getElementById('refreshBalanceBtn');
    
    if (!isInitialized || !window.bundlrInstance) {
        showNotification('Please initialize Bundlr first', 'error');
        return;
    }
    
    try {
        console.log('🔄 Refreshing Bundlr balance...');
        
        // Show loading
        if (refreshBtn) {
            refreshBtn.textContent = '🔄 Refreshing...';
            refreshBtn.disabled = true;
        }
        
        // Fetch real balance (like React app)
        const bal = await window.bundlrInstance.getLoadedBalance();
        currentBalance = parseFloat(ethers.utils.formatEther(bal.toString()));
        
        console.log('💰 Refreshed balance:', currentBalance, 'MATIC');
        
        // Update display
        updateBalanceDisplay();
        updateWalletWidgetUI();
        
        showNotification('Balance refreshed successfully!', 'success');
        
    } catch (error) {
        console.error('❌ Failed to refresh balance:', error);
        showNotification('Failed to refresh balance: ' + error.message, 'error');
        
    } finally {
        // Reset button
        if (refreshBtn) {
            refreshBtn.textContent = '🔄 Refresh Balance';
            refreshBtn.disabled = false;
        }
    }
}

/**
 * Update wallet widget UI state (main button display)
 */
function updateWalletWidgetUI() {
    const walletBalance = document.getElementById('walletBalance');
    
    if (isInitialized && currentBalance !== null) {
        if (walletBalance) {
            walletBalance.textContent = `${currentBalance.toFixed(6)} MATIC`;
        }
        console.log('💰 Updated main button balance:', currentBalance.toFixed(6));
    } else {
        if (walletBalance) {
            walletBalance.textContent = 'Click to Initialize';
        }
    }
}

/**
 * Update balance display in dropdown
 */
function updateBalanceDisplay() {
    const balanceDetail = document.getElementById('walletBalanceDetail');
    
    if (isInitialized && currentBalance !== null) {
        if (balanceDetail) {
            balanceDetail.textContent = `${currentBalance.toFixed(6)} MATIC`;
        }
        console.log('💰 Updated dropdown balance:', currentBalance.toFixed(6));
    } else {
        if (balanceDetail) {
            balanceDetail.textContent = '-- MATIC';
        }
    }
}

/**
 * Enable action buttons after initialization
 */
function enableActionButtons() {
    const fundBtn = document.getElementById('fundBundlrBtn');
    const refreshBtn = document.getElementById('refreshBalanceBtn');
    
    if (fundBtn) fundBtn.disabled = false;
    if (refreshBtn) refreshBtn.disabled = false;
}

/**
 * Show notification (simple implementation)
 */
function showNotification(message, type = 'info') {
    console.log(`${type.toUpperCase()}: ${message}`);
    
    // You can enhance this with a proper notification system
    if (type === 'error') {
        console.error('❌ ' + message);
        alert('Error: ' + message);
    } else {
        console.log('✅ ' + message);
    }
}

/**
 * Upload file using the initialized Bundlr instance
 */
export async function uploadFileWithBundlr(file) {
    if (!isInitialized || !window.bundlrInstance) {
        throw new Error('Bundlr not initialized. Please initialize Bundlr first.');
    }
    
    try {
        console.log('📤 Uploading to Arweave (REAL)...', file.name);

        // Read file as buffer
        const fileBuffer = await readFileAsBuffer(file);
        
        // Upload to Arweave (exact React app code)
        const tx = await window.bundlrInstance.uploader.upload(fileBuffer, [
            { name: "Content-Type", value: file.type || "application/octet-stream" }
        ]);
        
        console.log('tx: ', tx);
        
        // Generate Arweave URL (exact React app code)
        const arweaveUrl = `https://arweave.net/${tx.data.id}`;
        
        // Update balance after upload
        const bal = await window.bundlrInstance.getLoadedBalance();
        currentBalance = parseFloat(ethers.utils.formatEther(bal.toString()));
        updateWalletWidgetUI();
        
        console.log('✅ File uploaded successfully (REAL)!');
        console.log('Transaction ID:', tx.data.id);
        console.log('URL:', arweaveUrl);

        return {
            success: true,
            id: tx.data.id,
            url: arweaveUrl,
            remainingBalance: currentBalance
        };

    } catch (error) {
        console.error('❌ Upload failed:', error);
        return {
            success: false,
            error: error.message
        };
    }
}

/**
 * Read file as array buffer
 */
function readFileAsBuffer(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        
        reader.onload = function() {
            if (reader.result) {
                resolve(new Uint8Array(reader.result));
            } else {
                reject(new Error('Failed to read file'));
            }
        };
        
        reader.onerror = function() {
            reject(new Error('FileReader error'));
        };
        
        reader.readAsArrayBuffer(file);
    });
}

/**
 * Get current balance (external access)
 */
export function getCurrentBalance() {
    return currentBalance || 0;
}

/**
 * Check if wallet is ready
 */
export function isWalletReady() {
    return isInitialized;
}

// Export for global access
window.initializeBundlrWalletWidget = initializeBundlrWalletWidget;
window.uploadFileWithBundlr = uploadFileWithBundlr;
window.getCurrentBalance = getCurrentBalance;
window.isWalletReady = isWalletReady;

export default {
    initializeBundlrWalletWidget,
    uploadFileWithBundlr,
    getCurrentBalance,
    isWalletReady
};
