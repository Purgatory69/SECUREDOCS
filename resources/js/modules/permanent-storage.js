/**
 * Permanent Storage Upload Module
 * Handles crypto payments and Arweave uploads for premium users
 */

let currentFile = null;
let paymentRequest = null;
let paymentCheckInterval = null;

export function initializePermanentStorageModal() {
    // Prevent duplicate initialization
    if (window.permanentStorageInitialized) {
        console.log('Permanent storage modal already initialized');
        return;
    }
    window.permanentStorageInitialized = true;
    
    const modal = document.getElementById('permanentStorageModal');
    const openBtn = document.getElementById('openPermanentStorageBtn');
    const closeBtn = document.getElementById('closePermanentStorageBtn');
    const modalBackdrop = document.getElementById('permanentStorageBackdrop');
    
    if (!modal || !openBtn) {
        console.log('Permanent storage modal elements not found');
        return;
    }
    // Open modal
    openBtn.addEventListener('click', () => {
        openPermanentStorageModal();
    });

    // Close modal
    closeBtn?.addEventListener('click', () => {
        closePermanentStorageModal();
    });

    modalBackdrop?.addEventListener('click', () => {
        closePermanentStorageModal();
    });

    // Initialize file drop zone
    initializeDropZone();
    
    // Initialize wallet connection
    initializeWalletConnection();
    
    console.log('Permanent storage modal initialized');
}

function openPermanentStorageModal() {
    const modal = document.getElementById('permanentStorageModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        resetModalState();
    }
}

function closePermanentStorageModal() {
    const modal = document.getElementById('permanentStorageModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        resetModalState();
        clearPaymentCheck();
    }
}

function resetModalState() {
    currentFile = null;
    paymentRequest = null;
    
    // Reset UI states
    document.getElementById('fileSelectionStep')?.classList.remove('hidden');
    document.getElementById('costCalculationStep')?.classList.add('hidden');
    document.getElementById('walletConnectionStep')?.classList.add('hidden');
    document.getElementById('paymentStep')?.classList.add('hidden');
    document.getElementById('uploadingStep')?.classList.add('hidden');
    document.getElementById('successStep')?.classList.add('hidden');
    
    // Clear file info
    document.getElementById('selectedFileInfo').innerHTML = '';
    document.getElementById('costBreakdown').innerHTML = '';
    document.getElementById('paymentDetails').innerHTML = '';
}

function initializeDropZone() {
    const dropZone = document.getElementById('permanentStorageDropZone');
    const fileInput = document.getElementById('permanentStorageFileInput');
    
    if (!dropZone || !fileInput) return;

    // Drag and drop handlers
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('border-blue-400', 'bg-blue-50');
    });

    dropZone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        dropZone.classList.remove('border-blue-400', 'bg-blue-50');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('border-blue-400', 'bg-blue-50');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFileSelection(files[0]);
        }
    });

    // Click to browse
    dropZone.addEventListener('click', () => {
        fileInput.click();
    });

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            handleFileSelection(e.target.files[0]);
        }
    });
}

async function handleFileSelection(file) {
    currentFile = file;
    
    // Show file info
    displayFileInfo(file);
    
    // Calculate costs
    await calculateStorageCosts(file);
    
    // Move to cost calculation step
    document.getElementById('fileSelectionStep').classList.add('hidden');
    document.getElementById('costCalculationStep').classList.remove('hidden');
}

function displayFileInfo(file) {
    const fileInfo = document.getElementById('selectedFileInfo');
    const fileSize = formatFileSize(file.size);
    const fileType = file.type || 'Unknown';
    
    fileInfo.innerHTML = `
        <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg">
            <div class="text-4xl">📄</div>
            <div class="flex-1">
                <div class="font-medium text-gray-900">${file.name}</div>
                <div class="text-sm text-gray-600">${fileSize} • ${fileType}</div>
            </div>
        </div>
    `;
}

async function calculateStorageCosts(file) {
    try {
        showLoadingState('costBreakdown', 'Calculating storage costs...');
        
        const response = await fetch('/permanent-storage/calculate-cost', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                file_size: file.size,
                file_name: file.name
            })
        });

        const result = await response.json();
        
        if (result.success) {
            displayCostBreakdown(result.cost_breakdown);
        } else {
            throw new Error(result.message || 'Cost calculation failed');
        }
    } catch (error) {
        console.error('Cost calculation failed:', error);
    }
}

function displayCostBreakdown(costs) {
    const costBreakdown = document.getElementById('costBreakdown');
    const selectedCurrency = document.getElementById('currencySelector').value;
    const currencySymbols = {
        'USD': '$',
        'PHP': '₱',
        'EUR': '€',
        'GBP': '£',
        'JPY': '¥'
    };
    
    // Convert costs to selected currency (mock conversion rates)
    const conversionRates = {
        'USD': 1,
        'PHP': 56.5,
        'EUR': 0.92,
        'GBP': 0.79,
        'JPY': 149.8
    };
    
    const rate = conversionRates[selectedCurrency];
    const symbol = currencySymbols[selectedCurrency];
    
    const convertedCosts = {
        arweave: (costs.arweave_cost_usd * rate).toFixed(selectedCurrency === 'JPY' ? 0 : 2),
        service: (costs.service_fee_usd * rate).toFixed(selectedCurrency === 'JPY' ? 0 : 2),
        processing: (costs.processing_fee_usd * rate).toFixed(selectedCurrency === 'JPY' ? 0 : 2),
        total: (costs.total_usd * rate).toFixed(selectedCurrency === 'JPY' ? 0 : 2)
    };
    
    costBreakdown.innerHTML = `
        <div class="bg-[#1F2235] rounded-lg p-4 border border-[#3C3F58] space-y-4">
            <h4 class="font-medium text-white">Cost Breakdown</h4>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-400">Arweave Storage:</span>
                    <span class="font-medium text-white">${symbol}${convertedCosts.arweave}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Service Fee:</span>
                    <span class="font-medium text-white">${symbol}${convertedCosts.service}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Processing Fee:</span>
                    <span class="font-medium text-white">${symbol}${convertedCosts.processing}</span>
                </div>
                <hr class="border-[#3C3F58]">
                <div class="flex justify-between font-semibold">
                    <span class="text-white">Total:</span>
                    <span class="text-[#f89c00]">${symbol}${convertedCosts.total}</span>
                </div>
                <div class="text-center text-sm text-gray-400 mt-2">
                    ≈ ${costs.total_crypto} ${costs.recommended_token}
                </div>
            </div>
            <div class="mt-4">
                <button onclick="proceedToWalletConnection()" 
                        class="w-full bg-[#f89c00] text-white py-2 px-4 rounded-lg hover:bg-[#e88900] transition-colors font-medium">
                    Proceed to Payment
                </button>
            </div>
        </div>
    `;
    
    // Add currency change listener
    document.getElementById('currencySelector').addEventListener('change', () => {
        displayCostBreakdown(costs);
    });
}

function proceedToWalletConnection() {
    document.getElementById('costCalculationStep').classList.add('hidden');
    document.getElementById('walletConnectionStep').classList.remove('hidden');
}

// Make function global
window.proceedToWalletConnection = proceedToWalletConnection;

function initializeWalletConnection() {
    // MetaMask connection
    const connectMetaMaskBtn = document.getElementById('connectMetaMaskBtn');
    connectMetaMaskBtn?.addEventListener('click', () => {
        connectWallet('metamask');
    });
    
    // Ronin connection
    const connectRoninBtn = document.getElementById('connectRoninBtn');
    connectRoninBtn?.addEventListener('click', () => {
        connectWallet('ronin');
    });
    
    // WalletConnect
    const connectWalletConnectBtn = document.getElementById('connectWalletConnectBtn');
    connectWalletConnectBtn?.addEventListener('click', () => {
        connectWallet('walletconnect');
    });
}

async function connectWallet(walletType) {
    try {
        let walletAddress = null;
        
        switch (walletType) {
            case 'metamask':
                walletAddress = await connectMetaMask();
                break;
            case 'ronin':
                walletAddress = await connectRonin();
                break;
            case 'walletconnect':
                walletAddress = await connectWalletConnect();
                break;
            default:
                throw new Error('Unsupported wallet type');
        }
        
        if (walletAddress) {
            console.log(`${walletType} wallet connected:`, walletAddress);
            await createPaymentRequest(walletAddress, walletType);
        }
    } catch (error) {
        console.error('Wallet connection failed:', error);
        showError('Failed to connect wallet: ' + error.message);
    }
}

async function connectMetaMask() {
    if (typeof window.ethereum === 'undefined') {
        throw new Error('MetaMask is not installed. Please install MetaMask to continue.');
    }
    
    const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
    return accounts[0];
}

async function connectRonin() {
    // Check multiple possible Ronin wallet injection points
    let roninProvider = null;
    
    // First check for dedicated Ronin provider
    if (typeof window.ronin !== 'undefined') {
        roninProvider = window.ronin;
        console.log('Using window.ronin provider');
    } 
    // Check if ethereum provider is Ronin Wallet
    else if (typeof window.ethereum !== 'undefined') {
        // Check for Ronin-specific properties
        if (window.ethereum.isRonin || 
            (window.ethereum._metamask && window.ethereum._metamask.isUnlocked === undefined) ||
            window.ethereum.isRoninWallet ||
            (window.ethereum.providers && window.ethereum.providers.some(p => p.isRonin))) {
            roninProvider = window.ethereum;
            console.log('Using window.ethereum with Ronin detection');
        }
        // If no specific Ronin detection, assume it might be Ronin if user selected it
        else {
            roninProvider = window.ethereum;
            console.log('Using window.ethereum (assuming Ronin based on user selection)');
        }
    }
    
    if (!roninProvider) {
        throw new Error('Ronin Wallet is not detected. Please make sure Ronin Wallet extension is installed and active.');
    }
    
    try {
        // Try different connection methods for Ronin
        let accounts = null;
        
        if (roninProvider.request) {
            // Standard EIP-1193 method
            accounts = await roninProvider.request({ 
                method: 'eth_requestAccounts' 
            });
        } else if (roninProvider.provider && roninProvider.provider.request) {
            // Provider nested method
            accounts = await roninProvider.provider.request({ 
                method: 'eth_requestAccounts' 
            });
        } else if (roninProvider.enable) {
            // Legacy enable method
            accounts = await roninProvider.enable();
        } else {
            throw new Error('Ronin Wallet API not recognized');
        }
        
        if (!accounts || accounts.length === 0) {
            throw new Error('No accounts found in Ronin Wallet');
        }
        
        console.log('Ronin wallet connected:', accounts[0]);
        return accounts[0];
        
    } catch (error) {
        if (error.code === 4001) {
            throw new Error('User rejected the connection request');
        }
        console.error('Ronin connection error:', error);
        throw new Error('Failed to connect to Ronin Wallet: ' + error.message);
    }
}

async function connectWalletConnect() {
    // Check for WalletConnect compatible providers
    if (typeof window.ethereum !== 'undefined' && window.ethereum.isWalletConnect) {
        // Use WalletConnect through ethereum provider
        try {
            const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
            return accounts[0];
        } catch (error) {
            throw new Error('WalletConnect connection failed: ' + error.message);
        }
    }
    
    // Check for other WalletConnect implementations
    if (typeof window.walletConnectProvider !== 'undefined') {
        try {
            await window.walletConnectProvider.enable();
            return window.walletConnectProvider.accounts[0];
        } catch (error) {
            throw new Error('WalletConnect connection failed: ' + error.message);
        }
    }
    
    // Fallback message
    throw new Error('WalletConnect not detected. Please:\n1. Use a WalletConnect compatible mobile wallet\n2. Or install a WalletConnect browser extension\n3. Or use MetaMask/Ronin Wallet instead');
}

async function createPaymentRequest(walletAddress, walletType) {
    try {
        showLoadingState('paymentDetails', 'Creating payment request...');
        
        const response = await fetch('/permanent-storage/create-payment', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({
                file_name: currentFile.name,
                file_size: currentFile.size,
                wallet_address: walletAddress,
                wallet_type: walletType
            })
        });

        const result = await response.json();
        
        if (result.success) {
            paymentRequest = result.payment_request;
            displayPaymentRequest(result);
            
            // Move to payment step
            document.getElementById('walletConnectionStep').classList.add('hidden');
            document.getElementById('paymentStep').classList.remove('hidden');
            
            // Start payment monitoring
            startPaymentMonitoring(result.payment_id);
        } else {
            throw new Error(result.message || 'Payment request creation failed');
        }
    } catch (error) {
        console.error('Payment request creation failed:', error);
        showErrorState('paymentDetails', 'Failed to create payment request. Please try again.');
    }
}

function displayPaymentRequest(paymentData) {
    const paymentDetails = document.getElementById('paymentDetails');
    const { payment_request, cost_breakdown, expires_in_minutes } = paymentData;
    
    paymentDetails.innerHTML = `
        <div class="space-y-6">
            <div class="text-center">
                <h4 class="text-lg font-medium text-white mb-2">Send Payment</h4>
                <p class="text-sm text-gray-400">Send exactly the amount below to complete your permanent storage purchase</p>
            </div>
            
            <div class="bg-[#1F2235] border border-[#f89c00] rounded-lg p-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-[#f89c00] mb-1">
                        ${payment_request.amount} ${payment_request.token}
                    </div>
                    <div class="text-sm text-gray-400">
                        on ${payment_request.network} network
                    </div>
                </div>
            </div>
            
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Send to Address:</label>
                    <div class="flex items-center gap-2">
                        <input type="text" value="${payment_request.to_address}" readonly 
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-sm font-mono">
                        <button onclick="copyToClipboard('${payment_request.to_address}')" 
                                class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                            Copy
                        </button>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount:</label>
                    <div class="flex items-center gap-2">
                        <input type="text" value="${payment_request.amount}" readonly 
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-sm font-mono">
                        <button onclick="copyToClipboard('${payment_request.amount}')" 
                                class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                            Copy
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="text-center">
                <div class="inline-flex items-center gap-2 text-sm text-orange-600 bg-orange-50 px-3 py-2 rounded-lg">
                    <span>⏰</span>
                    <span>Payment expires in <span id="paymentTimer">${expires_in_minutes}</span> minutes</span>
                </div>
            </div>
            
            <div class="text-center">
                <div class="text-sm text-gray-600 mb-3">
                    Waiting for payment confirmation...
                </div>
                <div class="animate-spin w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full mx-auto"></div>
            </div>
        </div>
    `;
    
    // Start countdown timer
    startPaymentTimer(expires_in_minutes);
}

function startPaymentTimer(minutes) {
    let timeLeft = minutes * 60; // Convert to seconds
    const timerElement = document.getElementById('paymentTimer');
    
    const timer = setInterval(() => {
        const mins = Math.floor(timeLeft / 60);
        const secs = timeLeft % 60;
        
        if (timerElement) {
            timerElement.textContent = `${mins}:${secs.toString().padStart(2, '0')}`;
        }
        
        timeLeft--;
        
        if (timeLeft < 0) {
            clearInterval(timer);
            handlePaymentTimeout();
        }
    }, 1000);
}

function startPaymentMonitoring(paymentId) {
    paymentCheckInterval = setInterval(async () => {
        try {
            const response = await fetch(`/permanent-storage/payment-status/${paymentId}`);
            const result = await response.json();
            
            if (result.success) {
                if (result.status === 'confirmed' || result.status === 'completed') {
                    clearPaymentCheck();
                    handlePaymentSuccess(result);
                } else if (result.status === 'expired' || result.status === 'failed') {
                    clearPaymentCheck();
                    handlePaymentTimeout();
                }
                console.log('Payment status:', result.status, result.message);
            }
        } catch (error) {
            console.error('Payment status check failed:', error);
        }
    }, 5000); // Check every 5 seconds
}

function clearPaymentCheck() {
    if (paymentCheckInterval) {
        clearInterval(paymentCheckInterval);
        paymentCheckInterval = null;
    }
}

async function handlePaymentSuccess(paymentResult) {
    // Move to uploading step
    document.getElementById('paymentStep').classList.add('hidden');
    document.getElementById('uploadingStep').classList.remove('hidden');
    
    // Start file upload to Arweave
    try {
        await uploadFileToArweave(paymentResult);
    } catch (error) {
        console.error('File upload failed:', error);
        showError('File upload failed: ' + error.message);
    }
}

async function uploadFileToArweave(paymentResult) {
    const progressBar = document.getElementById('uploadProgress');
    const progressText = document.getElementById('uploadProgressText');
    
    try {
        // Convert file to base64
        const fileContent = await fileToBase64(currentFile);
        
        // Update progress
        updateProgress(progressBar, progressText, 25, 'Preparing file for Arweave...');
        
        const response = await fetch('/permanent-storage/upload', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({
                payment_id: paymentResult.payment_id,
                file_name: currentFile.name,
                file_content: fileContent,
                file_size: currentFile.size,
                mime_type: currentFile.type
            })
        });

        updateProgress(progressBar, progressText, 75, 'Uploading to Arweave blockchain...');
        
        const result = await response.json();
        
        if (result.success) {
            updateProgress(progressBar, progressText, 100, 'Upload complete!');
            
            setTimeout(() => {
                handleUploadSuccess(result);
            }, 1000);
        } else {
            throw new Error(result.message || 'Upload failed');
        }
    } catch (error) {
        console.error('Arweave upload failed:', error);
        showError('Upload failed: ' + error.message);
    }
}

function handleUploadSuccess(uploadResult) {
    document.getElementById('uploadingStep').classList.add('hidden');
    document.getElementById('successStep').classList.remove('hidden');
    
    const successDetails = document.getElementById('successDetails');
    successDetails.innerHTML = `
        <div class="space-y-6">
            <div class="text-center">
                <div class="text-6xl mb-4">✅</div>
                <h3 class="text-xl font-semibold text-white mb-2">File Permanently Stored!</h3>
                <p class="text-gray-400">Your file is now permanently stored on the Arweave blockchain</p>
            </div>
            
            <div class="bg-[#1F2235] border border-[#3C3F58] rounded-lg p-4 space-y-3">
                <h4 class="font-medium text-white mb-3">📄 File Information</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-400">File Name:</span>
                        <span class="text-white font-medium">${uploadResult.file_info?.name || currentFile.name}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Size:</span>
                        <span class="text-white">${uploadResult.file_info?.size || 'Unknown'}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Arweave ID:</span>
                        <span class="font-mono text-xs text-[#f89c00] break-all">${uploadResult.arweave_id}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Status:</span>
                        <span class="text-green-400 font-medium">✓ Permanently Stored</span>
                    </div>
                </div>
            </div>

            <div class="bg-[#1F2235] border border-[#3C3F58] rounded-lg p-4 space-y-3">
                <h4 class="font-medium text-white mb-3">🌐 Access Your File Anywhere</h4>
                <p class="text-sm text-gray-400 mb-3">Your file is permanently stored on Arweave blockchain. You can access it anytime using the URL below, even if this website goes offline.</p>
                
                <div class="space-y-2">
                    <div class="flex items-center justify-between bg-[#2A2A3E] rounded p-2">
                        <span class="text-xs text-gray-400">Permanent URL:</span>
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs text-white break-all">${uploadResult.arweave_url}</span>
                            <button onclick="copyToClipboard('${uploadResult.arweave_url}')" 
                                    class="text-[#f89c00] hover:text-[#e88900] text-xs">📋</button>
                        </div>
                    </div>
                    
                    <div class="text-xs text-gray-400">
                        <strong>How to access your file:</strong>
                        <ul class="mt-1 space-y-1 ml-4">
                            <li>• Direct access via arweave.net (primary)</li>
                            <li>• IPFS-style gateways (backup)</li>
                            <li>• Local Arweave node (if running)</li>
                            <li>• Third-party Arweave explorers</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="flex gap-3 justify-center">
                <a href="${uploadResult.arweave_url}" target="_blank" 
                   class="bg-[#f89c00] text-white px-6 py-2 rounded-lg hover:bg-[#e88900] transition-colors font-medium">
                    🔗 Open File
                </a>
                <button onclick="copyToClipboard('${uploadResult.arweave_url}')" 
                        class="bg-[#3C3F58] text-white px-6 py-2 rounded-lg hover:bg-[#4A4F6B] transition-colors">
                    📋 Copy URL
                </button>
                <button onclick="closePermanentStorageModal()" 
                        class="bg-[#2A2A3E] text-white px-6 py-2 rounded-lg hover:bg-[#3A3A4E] transition-colors">
                    ✕ Close
                </button>
            </div>
        </div>
    `;
    
    // Refresh file list
    if (window.loadUserFiles) {
        window.loadUserFiles();
    }
}

function handlePaymentTimeout() {
    showError('Payment expired. Please try again.');
    setTimeout(() => {
        closePermanentStorageModal();
    }, 3000);
}

// Utility functions
function formatFileSize(bytes) {
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes;
    let unitIndex = 0;
    
    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
    }
    
    return `${size.toFixed(1)} ${units[unitIndex]}`;
}

function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = () => {
            const base64 = reader.result.split(',')[1];
            resolve(base64);
        };
        reader.onerror = error => reject(error);
    });
}

function updateProgress(progressBar, progressText, percentage, text) {
    if (progressBar) {
        progressBar.style.width = `${percentage}%`;
    }
    if (progressText) {
        progressText.textContent = text;
    }
}

function showLoadingState(elementId, message) {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = `
            <div class="text-center py-8">
                <div class="animate-spin w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full mx-auto mb-3"></div>
                <div class="text-sm text-gray-600">${message}</div>
            </div>
        `;
    }
}

function showErrorState(elementId, message) {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = `
            <div class="text-center py-8">
                <div class="text-red-500 text-4xl mb-3">❌</div>
                <div class="text-sm text-red-600">${message}</div>
            </div>
        `;
    }
}

function showError(message) {
    // You can implement a toast notification system here
    alert(message);
}

// Global functions
window.copyToClipboard = function(text) {
    navigator.clipboard.writeText(text).then(() => {
        // Show success feedback
        console.log('Copied to clipboard:', text);
    });
};

window.closePermanentStorageModal = closePermanentStorageModal;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializePermanentStorageModal();
});
