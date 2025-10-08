/**
 * Client-Side Bundlr Implementation (REAL - Exact React App Copy)
 * User pays and uploads directly to Arweave from their own wallet
 * Uses actual Bundlr WebBundlr library like YouTube video
 */

// Import ethers for Web3 provider (loaded globally)
let bundlrInstance = null;
let bundlrRef = null;
let currentBalance = null;

/**
 * Initialize Bundlr (Exact copy from React app)
 */
export async function initializeClientBundlr() {
    try {
        console.log('🚀 Initializing Bundlr (Real Implementation)...');
        
        // Enable MetaMask (exact React app code)
        await window.ethereum.enable();
        
        // Create Web3 provider (exact React app code) 
        const provider = new ethers.providers.Web3Provider(window.ethereum);
        await provider._ready();
        
        // Initialize Bundlr client (exact React app code)
        const bundlr = new WebBundlr("https://node1.bundlr.network", "matic", provider);
        await bundlr.ready();
        
        // Store instances (like React app)
        bundlrInstance = bundlr;
        bundlrRef = bundlr;
        window.bundlrInstance = bundlr;
        window.bundlrRef = bundlr;
        
        // Fetch real balance (like React app)
        await fetchUserBalance();
        
        console.log('✅ Bundlr initialized successfully (REAL)');
        console.log('💰 Real Balance:', currentBalance, 'MATIC');
        
        return {
            success: true,
            bundlr: bundlr,
            balance: currentBalance
        };

    } catch (error) {
        console.error('❌ Failed to initialize Bundlr:', error);
        return {
            success: false,
            error: error.message
        };
    }
}

/**
 * Fetch real balance (Exact copy from React app)
 */
export async function fetchUserBalance() {
    if (!bundlrRef) {
        throw new Error('Bundlr not initialized. Call initializeClientBundlr() first.');
    }

    try {
        // Get real balance (exact React app code)
        const bal = await bundlrRef.getLoadedBalance();
        console.log('bal: ', ethers.utils.formatEther(bal.toString()));
        
        // Format to MATIC (exact React app code)
        currentBalance = ethers.utils.formatEther(bal.toString());
        
        console.log('💰 Real Bundlr balance:', currentBalance, 'MATIC');
        
        return parseFloat(currentBalance);
    } catch (error) {
        console.error('❌ Failed to fetch balance:', error);
        throw error;
    }
}

/**
 * Fund user's Bundlr balance (Real implementation like React app)
 * @param {number} amount - Amount in MATIC to fund
 */
export async function fundUserBundlr(amount) {
    if (!bundlrInstance) {
        throw new Error('Bundlr not initialized');
    }

    try {
        console.log(`💸 Funding Bundlr with ${amount} MATIC...`);

        if (!amount || amount <= 0) {
            throw new Error('Amount too small or invalid');
        }

        // Parse input (exact React app code)
        const conv = new BigNumber(amount).multipliedBy(bundlrInstance.currencyConfig.base[1]);
        if (conv.isLessThan(1)) {
            throw new Error('Value too small');
        }

        // Fund wallet (exact React app code)
        const response = await bundlrInstance.fund(conv);
        console.log('Wallet funded: ', response);
        
        // Fetch new balance
        await fetchUserBalance();

        console.log('✅ Bundlr funded successfully (REAL)');

        return {
            success: true,
            transaction: response,
            newBalance: parseFloat(currentBalance)
        };

    } catch (error) {
        console.error('❌ Failed to fund Bundlr:', error);
        return {
            success: false,
            error: error.message
        };
    }
}

/**
 * Upload file to Arweave (Real implementation like React app)
 * @param {File} file - File object to upload
 * @param {Object} metadata - Additional metadata
 */
export async function uploadToArweaveClientSide(file, metadata = {}) {
    if (!bundlrInstance) {
        throw new Error('Bundlr not initialized');
    }

    try {
        console.log('📤 Uploading to Arweave (REAL)...', file.name);

        // Read file as buffer
        const fileBuffer = await readFileAsBuffer(file);
        
        // Upload to Arweave (exact React app code)
        const tx = await bundlrInstance.uploader.upload(fileBuffer, [
            { name: "Content-Type", value: file.type || "application/octet-stream" }
        ]);
        
        console.log('tx: ', tx);
        
        // Generate Arweave URL (exact React app code)
        const arweaveUrl = `http://arweave.net/${tx.data.id}`;
        
        console.log('✅ File uploaded successfully (REAL)!');
        console.log('Transaction ID:', tx.data.id);
        console.log('URL:', arweaveUrl);

        return {
            success: true,
            id: tx.data.id,
            url: arweaveUrl,
            remainingBalance: parseFloat(currentBalance)
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
 * Calculate upload cost for a file (demo version)
 * @param {number} fileSize - File size in bytes
 */
export async function calculateUploadCost(fileSize) {
    if (!userWallet) {
        throw new Error('Wallet not connected');
    }

    try {
        // Demo: Calculate approximate cost
        // In real implementation, this would call Bundlr API
        const costPerMB = 0.005; // MATIC per MB
        const fileSizeMB = fileSize / (1024 * 1024);
        const costInMatic = Math.max(0.001, fileSizeMB * costPerMB); // Minimum 0.001 MATIC

        console.log(`💰 Upload cost for ${fileSize} bytes: ${costInMatic.toFixed(6)} MATIC`);

        return {
            bytes: fileSize,
            costMatic: costInMatic.toFixed(6),
            costAtomic: (costInMatic * 1e18).toString()
        };

    } catch (error) {
        console.error('❌ Failed to calculate cost:', error);
        throw error;
    }
}

/**
 * Read file as array buffer (for demo)
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
 * Check if wallet is connected
 */
export function isBundlrInitialized() {
    return bundlrInstance !== null;
}

/**
 * Disconnect wallet
 */
export function disconnectWallet() {
    bundlrInstance = null;
    bundlrRef = null;
    currentBalance = null;
    console.log('💔 Wallet disconnected');
}

/**
 * Get current wallet address
 */
export function getUserWallet() {
    return window.ethereum?.selectedAddress;
}

/**
 * Get current balance
 */
export function getUserBalance() {
    return parseFloat(currentBalance) || 0;
}

// Export all functions
export default {
    initializeClientBundlr,
    fetchUserBalance,
    fundUserBundlr,
    uploadToArweaveClientSide,
    calculateUploadCost,
    getUserWallet,
    getUserBalance,
    isBundlrInitialized,
    disconnectWallet
};
